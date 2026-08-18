<?php

require_once "../db.php";

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION["user_id"]);
$toilet_id = isset($_GET["toilet_id"]) ? intval($_GET["toilet_id"]) : 0;

if ($toilet_id <= 0) {
    header("Location: dashboard.php");
    exit;
}


/* =========================
   CHECK TOILET ASSIGNMENT
========================= */

$sql = "SELECT
            t.id,
            t.name,
            t.toilet_number
        FROM toilets t
        INNER JOIN user_toilets ut
            ON t.id = ut.toilet_id
        WHERE t.id = ?
        AND ut.user_id = ?
        AND t.is_active = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $toilet_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("You are not assigned to this toilet.");
}

$toilet = $result->fetch_assoc();

$stmt->close();


/* =========================
   CHECK ACTIVE SESSION
========================= */

$sql = "SELECT id
        FROM toilet_sessions
        WHERE user_id = ?
        AND toilet_id = ?
        AND check_out_at IS NULL
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $toilet_id);
$stmt->execute();

$active_result = $stmt->get_result();

if ($active_result->num_rows > 0) {

    $stmt->close();

    header(
        "Location: toilet.php?id=" .
        $toilet_id
    );

    exit;
}

$stmt->close();


$error = "";


/* =========================
   PROCESS CHECK-IN
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $comment = trim($_POST["comment"] ?? "");

    $files = $_FILES["before_photos"] ?? null;


    /* Check photos */

    if (!$files || !isset($files["name"])) {

        $error = "Please upload at least one photo.";

    } else {

        $valid_photo_count = 0;

        foreach ($files["error"] as $error_code) {

            if ($error_code === UPLOAD_ERR_OK) {
                $valid_photo_count++;
            }

        }

        if ($valid_photo_count === 0) {

            $error = "Please upload at least one photo.";

        } elseif ($valid_photo_count > 10) {

            $error = "You can upload a maximum of 10 photos.";

        }

    }


    /* =========================
       SAVE SESSION
    ========================= */

    if ($error === "") {

        $conn->begin_transaction();

        try {

            /*
                Re-check active session inside transaction.
            */

            $sql = "SELECT id
                    FROM toilet_sessions
                    WHERE user_id = ?
                    AND toilet_id = ?
                    AND check_out_at IS NULL
                    LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ii",
                $user_id,
                $toilet_id
            );

            $stmt->execute();

            $active_check = $stmt->get_result();

            if ($active_check->num_rows > 0) {

                throw new Exception(
                    "You already have an active Check-In."
                );

            }

            $stmt->close();


            /*
                Use MySQL server time automatically.
            */

            $sql = "INSERT INTO toilet_sessions
                    (
                        user_id,
                        toilet_id,
                        check_in_at,
                        check_in_comment
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        CURRENT_TIMESTAMP,
                        ?
                    )";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "iis",
                $user_id,
                $toilet_id,
                $comment
            );

            if (!$stmt->execute()) {

                throw new Exception(
                    "Unable to create Check-In session."
                );

            }

            $session_id = $conn->insert_id;

            $stmt->close();


            /* =========================
               CREATE UPLOAD DIRECTORY
            ========================= */

            $upload_dir = __DIR__ .
                          "/../uploads/session_photos/";

            if (!is_dir($upload_dir)) {

                if (!mkdir(
                    $upload_dir,
                    0775,
                    true
                )) {

                    throw new Exception(
                        "Unable to create upload directory."
                    );

                }

            }


            /* =========================
               SAVE PHOTOS
            ========================= */

            foreach ($files["tmp_name"] as $index => $tmp_name) {

                if ($files["error"][$index] !== UPLOAD_ERR_OK) {
                    continue;
                }

                /*
                    Maximum 5 MB per photo.
                */

                if ($files["size"][$index] > 5 * 1024 * 1024) {

                    throw new Exception(
                        "Each photo must be 5 MB or smaller."
                    );

                }


                /*
                    Verify that the uploaded file
                    is actually an image.
                */

                $image_info = getimagesize($tmp_name);

                if ($image_info === false) {

                    throw new Exception(
                        "One of the uploaded files is not a valid image."
                    );

                }


                /*
                    Determine MIME type.
                */

                $mime = $image_info["mime"];

                $allowed_types = [
                    "image/jpeg" => "jpg",
                    "image/png"  => "png",
                    "image/webp" => "webp"
                ];

                if (!isset($allowed_types[$mime])) {

                    throw new Exception(
                        "Only JPG, PNG and WEBP images are allowed."
                    );

                }


                /*
                    Generate a random filename.
                */

                $filename =
                    bin2hex(random_bytes(16))
                    . "."
                    . $allowed_types[$mime];


                $destination =
                    $upload_dir . $filename;


                if (!move_uploaded_file(
                    $tmp_name,
                    $destination
                )) {

                    throw new Exception(
                        "Unable to save uploaded photo."
                    );

                }


                /*
                    Store relative path in database.
                */

                $relative_path =
                    "uploads/session_photos/" .
                    $filename;


                $sql = "INSERT INTO session_photos
                        (
                            session_id,
                            photo_type,
                            file_path
                        )
                        VALUES
                        (
                            ?,
                            'before',
                            ?
                        )";

                $stmt = $conn->prepare($sql);

                $stmt->bind_param(
                    "is",
                    $session_id,
                    $relative_path
                );

                if (!$stmt->execute()) {

                    throw new Exception(
                        "Unable to save photo record."
                    );

                }

                $stmt->close();

            }


            $conn->commit();


            /*
                Check-In successful.
            */

            header(
                "Location: toilet.php?id=" .
                $toilet_id
            );

            exit;


        } catch (Exception $e) {

            $conn->rollback();

            $error = $e->getMessage();

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Check In</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #000000;
}

.header {
    background: #1769aa;
    color: white;

    padding: 20px 30px;

    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h1 {
    margin: 0;
}

.header a {
    color: white;
    text-decoration: none;

    background: #555;

    padding: 10px 15px;

    border-radius: 6px;
}

.container {
    max-width: 850px;

    margin: auto;

    padding: 30px;
}

.card {
    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 3px 10px rgba(0,0,0,0.08);
}

.card h2 {
    color: #1769aa;

    margin-top: 0;
}

.toilet-info {
    background: #eef6ff;

    padding: 15px;

    border-radius: 8px;

    margin-bottom: 25px;
}

label {
    display: block;

    margin-top: 18px;
    margin-bottom: 7px;

    font-weight: bold;
}

input[type="file"] {
    width: 100%;

    padding: 12px;

    border: 1px solid #ccc;

    border-radius: 6px;

    background: white;
}

textarea {
    width: 100%;

    min-height: 130px;

    padding: 12px;

    border: 1px solid #ccc;

    border-radius: 6px;

    resize: vertical;

    font-family: Arial, sans-serif;
}

button {
    margin-top: 25px;

    padding: 13px 22px;

    border: none;

    border-radius: 7px;

    background: #1769aa;

    color: white;

    font-size: 16px;

    cursor: pointer;
}

button:hover {
    background: #12588d;
}

.back {
    display: inline-block;

    margin-left: 8px;

    padding: 13px 22px;

    background: #777;

    color: white;

    text-decoration: none;

    border-radius: 7px;
}

.error {
    background: #ffe0e0;

    color: #a00000;

    padding: 12px;

    border-radius: 7px;

    margin-bottom: 20px;
}

.note {
    margin-top: 12px;

    color: #666;

    font-size: 14px;
}

</style>

</head>

<body>


<div class="header">

    <h1>Check In</h1>

    <a href="dashboard.php">
        Dashboard
    </a>

</div>


<div class="container">

<div class="card">

    <h2>
        🚽
        <?= htmlspecialchars($toilet["name"]) ?>
    </h2>


    <div class="toilet-info">

        <strong>
            Toilet Number:
        </strong>

        <?= htmlspecialchars($toilet["toilet_number"]) ?>

        <br><br>

        The Check-In time will be recorded
        automatically when you submit this form.

    </div>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        enctype="multipart/form-data"
    >


        <label>
            Before Cleaning / Use Photos
        </label>

        <input
            type="file"
            name="before_photos[]"
            accept="image/jpeg,image/png,image/webp"
            multiple
            required
        >

        <div class="note">

            You can select multiple photos.
            Maximum 10 photos, 5 MB each.

        </div>


        <label>
            Check-In Comment
        </label>

        <textarea
            name="comment"
            placeholder="Describe the toilet condition..."
        ></textarea>


        <button type="submit">

            Check In & Submit

        </button>


        <a
            class="back"
            href="toilet.php?id=<?= $toilet_id ?>"
        >
            Cancel
        </a>

    </form>

</div>

</div>

</body>

</html>