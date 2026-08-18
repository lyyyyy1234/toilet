<?php

require_once "../db.php";

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION["user_id"]);
$session_id = isset($_GET["session_id"])
    ? intval($_GET["session_id"])
    : 0;

if ($session_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$session = null;


/* =========================
   GET ACTIVE SESSION
========================= */

$sql = "SELECT
            ts.id,
            ts.toilet_id,
            ts.check_in_at,
            ts.check_in_comment,
            t.name,
            t.toilet_number
        FROM toilet_sessions ts
        INNER JOIN toilets t
            ON ts.toilet_id = t.id
        INNER JOIN user_toilets ut
            ON ut.toilet_id = ts.toilet_id
            AND ut.user_id = ts.user_id
        WHERE ts.id = ?
        AND ts.user_id = ?
        AND ts.check_out_at IS NULL
        AND t.is_active = 1
        LIMIT 1";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $session_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();

    die("Invalid or already completed Check-In session.");

}

$session = $result->fetch_assoc();

$stmt->close();


/* =========================
   PROCESS CHECK-OUT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $comment = trim($_POST["comment"] ?? "");

    $files = $_FILES["after_photos"] ?? null;

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
       SAVE CHECK-OUT
    ========================= */

    if ($error === "") {

        $conn->begin_transaction();

        try {

            /*
                Re-check that this session is
                still active.
            */

            $sql = "SELECT id
                    FROM toilet_sessions
                    WHERE id = ?
                    AND user_id = ?
                    AND check_out_at IS NULL
                    LIMIT 1";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ii",
                $session_id,
                $user_id
            );

            $stmt->execute();

            $active_result = $stmt->get_result();

            if ($active_result->num_rows !== 1) {

                throw new Exception(
                    "This session has already been checked out."
                );

            }

            $stmt->close();


            /*
                Check-Out time is generated
                automatically by MySQL.
            */

            $sql = "UPDATE toilet_sessions
                    SET
                        check_out_at = CURRENT_TIMESTAMP,
                        check_out_comment = ?
                    WHERE id = ?
                    AND user_id = ?
                    AND check_out_at IS NULL";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "sii",
                $comment,
                $session_id,
                $user_id
            );

            if (!$stmt->execute()) {

                throw new Exception(
                    "Unable to complete Check-Out."
                );

            }

            if ($stmt->affected_rows !== 1) {

                throw new Exception(
                    "Check-Out session was not updated."
                );

            }

            $stmt->close();


            /* =========================
               UPLOAD DIRECTORY
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
               SAVE AFTER PHOTOS
            ========================= */

            foreach (
                $files["tmp_name"] as $index => $tmp_name
            ) {

                if (
                    $files["error"][$index]
                    !== UPLOAD_ERR_OK
                ) {
                    continue;
                }


                /*
                    Maximum 5 MB per photo.
                */

                if (
                    $files["size"][$index]
                    > 5 * 1024 * 1024
                ) {

                    throw new Exception(
                        "Each photo must be 5 MB or smaller."
                    );

                }


                /*
                    Verify image.
                */

                $image_info = getimagesize(
                    $tmp_name
                );

                if ($image_info === false) {

                    throw new Exception(
                        "One of the uploaded files is not a valid image."
                    );

                }


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
                    Random filename.
                */

                $filename =
                    bin2hex(
                        random_bytes(16)
                    )
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


                $relative_path =
                    "uploads/session_photos/"
                    . $filename;


                /*
                    Save photo information.
                */

                $sql = "INSERT INTO session_photos
                        (
                            session_id,
                            photo_type,
                            file_path
                        )
                        VALUES
                        (
                            ?,
                            'after',
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
                Go back to toilet page.
            */

            header(
                "Location: toilet.php?id="
                . $session["toilet_id"]
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

<title>Check Out</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;

    font-family: Arial, sans-serif;

    background: #060606;
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

.info {
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

    background: #d32f2f;

    color: white;

    font-size: 16px;

    cursor: pointer;
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

    <h1>Check Out</h1>

    <a href="dashboard.php">
        Dashboard
    </a>

</div>


<div class="container">

<div class="card">

    <h2>

        🚽
        <?= htmlspecialchars($session["name"]) ?>

    </h2>


    <div class="info">

        <strong>
            Toilet Number:
        </strong>

        <?= htmlspecialchars(
            $session["toilet_number"]
        ) ?>

        <br><br>

        <strong>
            Check-In Time:
        </strong>

        <?= htmlspecialchars(
            $session["check_in_at"]
        ) ?>

        <br><br>

        Check-Out time will be recorded
        automatically when you submit.

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
            After Cleaning Photos
        </label>

        <input
            type="file"
            name="after_photos[]"
            accept="image/jpeg,image/png,image/webp"
            multiple
            required
        >

        <div class="note">

            You can select multiple photos.
            Maximum 10 photos, 5 MB each.

        </div>


        <label>
            Check-Out Comment
        </label>

        <textarea
            name="comment"
            placeholder="Describe the toilet condition after cleaning..."
        ></textarea>


        <button type="submit">

            Check Out & Submit

        </button>


        <a
            class="back"
            href="toilet.php?id=<?= $session["toilet_id"] ?>"
        >
            Cancel
        </a>

    </form>

</div>

</div>

</body>

</html>