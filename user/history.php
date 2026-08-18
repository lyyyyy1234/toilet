<?php

require_once "../db.php";

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION["user_id"]);

$toilet_id = isset($_GET["toilet_id"])
    ? intval($_GET["toilet_id"])
    : 0;

if ($toilet_id <= 0) {
    header("Location: dashboard.php");
    exit;
}


/* =========================
   CHECK TOILET ACCESS
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

$stmt->bind_param(
    "ii",
    $toilet_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();

    die("You are not assigned to this toilet.");

}

$toilet = $result->fetch_assoc();

$stmt->close();


/* =========================
   GET COMPLETED HISTORY
========================= */

$sql = "SELECT
            ts.id,
            ts.check_in_at,
            ts.check_in_comment,
            ts.check_out_at,
            ts.check_out_comment,
            u.full_name,
            u.username
        FROM toilet_sessions ts
        INNER JOIN users u
            ON ts.user_id = u.id
        WHERE ts.toilet_id = ?
        AND ts.check_out_at IS NOT NULL
        ORDER BY ts.check_in_at DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $toilet_id
);

$stmt->execute();

$history_result = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Toilet History</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;

    font-family: Arial, sans-serif;

    background: #0b0b0b;
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
    max-width: 1100px;

    margin: auto;

    padding: 30px;
}

.toilet-card {
    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 3px 10px rgba(0,0,0,0.08);
}

.toilet-card h2 {
    color: #1769aa;

    margin-top: 0;
}

.session {
    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 3px 10px rgba(0,0,0,0.08);
}

.session h3 {
    color: #1769aa;

    margin-top: 0;
}

.info {
    margin: 8px 0;
}

.comment {
    background: #f5f7f9;

    padding: 12px;

    border-radius: 7px;

    margin-top: 8px;
}

.photo-section {
    margin-top: 20px;
}

.photo-grid {
    display: flex;

    flex-wrap: wrap;

    gap: 12px;

    margin-top: 10px;
}

.photo-grid img {
    width: 180px;

    height: 130px;

    object-fit: cover;

    border-radius: 8px;

    border: 1px solid #ddd;
}

.badge {
    display: inline-block;

    padding: 5px 10px;

    border-radius: 15px;

    background: #e8f5e9;

    color: #236b2b;

    font-size: 14px;

    font-weight: bold;
}

.empty {
    background: white;

    padding: 30px;

    border-radius: 12px;

    text-align: center;

    color: #666;
}

.back {
    display: inline-block;

    padding: 10px 18px;

    background: #777;

    color: white;

    text-decoration: none;

    border-radius: 6px;

    margin-top: 10px;
}

</style>

</head>

<body>


<div class="header">

    <h1>Toilet History</h1>

    <a href="dashboard.php">
        Dashboard
    </a>

</div>


<div class="container">


<div class="toilet-card">

    <h2>
        🚽 <?= htmlspecialchars($toilet["name"]) ?>
    </h2>

    <strong>
        Toilet Number:
    </strong>

    <?= htmlspecialchars($toilet["toilet_number"]) ?>

    <br>

    <a
        class="back"
        href="toilet.php?id=<?= $toilet_id ?>"
    >
        Back to Toilet
    </a>

</div>


<?php if ($history_result->num_rows > 0): ?>


    <?php while ($session = $history_result->fetch_assoc()): ?>


        <div class="session">

            <h3>

                <?= date(
                    "d M Y",
                    strtotime($session["check_in_at"])
                ) ?>

                <span class="badge">
                    Completed
                </span>

            </h3>


            <div class="info">

                <strong>User:</strong>

                <?= htmlspecialchars(
                    $session["full_name"]
                ) ?>

                (<?= htmlspecialchars(
                    $session["username"]
                ) ?>)

            </div>


            <div class="info">

                <strong>Check In:</strong>

                <?= htmlspecialchars(
                    $session["check_in_at"]
                ) ?>

            </div>


            <?php if (
                !empty($session["check_in_comment"])
            ): ?>

                <div class="comment">

                    <strong>
                        Check-In Comment:
                    </strong>

                    <br>

                    <?= nl2br(
                        htmlspecialchars(
                            $session["check_in_comment"]
                        )
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- BEFORE PHOTOS -->

            <?php

            $photo_sql = "SELECT file_path
                          FROM session_photos
                          WHERE session_id = ?
                          AND photo_type = 'before'
                          ORDER BY id ASC";

            $photo_stmt = $conn->prepare(
                $photo_sql
            );

            $photo_stmt->bind_param(
                "i",
                $session["id"]
            );

            $photo_stmt->execute();

            $before_result =
                $photo_stmt->get_result();

            ?>


            <div class="photo-section">

                <strong>
                    Before Photos:
                </strong>


                <div class="photo-grid">

                    <?php while (
                        $photo =
                        $before_result->fetch_assoc()
                    ): ?>

                        <img
                            src="../<?= htmlspecialchars(
                                $photo["file_path"]
                            ) ?>"
                            alt="Before Photo"
                        >

                    <?php endwhile; ?>

                </div>

            </div>


            <?php $photo_stmt->close(); ?>


            <hr>


            <div class="info">

                <strong>Check Out:</strong>

                <?= htmlspecialchars(
                    $session["check_out_at"]
                ) ?>

            </div>


            <?php if (
                !empty($session["check_out_comment"])
            ): ?>

                <div class="comment">

                    <strong>
                        Check-Out Comment:
                    </strong>

                    <br>

                    <?= nl2br(
                        htmlspecialchars(
                            $session["check_out_comment"]
                        )
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- AFTER PHOTOS -->

            <?php

            $photo_sql = "SELECT file_path
                          FROM session_photos
                          WHERE session_id = ?
                          AND photo_type = 'after'
                          ORDER BY id ASC";

            $photo_stmt = $conn->prepare(
                $photo_sql
            );

            $photo_stmt->bind_param(
                "i",
                $session["id"]
            );

            $photo_stmt->execute();

            $after_result =
                $photo_stmt->get_result();

            ?>


            <div class="photo-section">

                <strong>
                    After Photos:
                </strong>


                <div class="photo-grid">

                    <?php while (
                        $photo =
                        $after_result->fetch_assoc()
                    ): ?>

                        <img
                            src="../<?= htmlspecialchars(
                                $photo["file_path"]
                            ) ?>"
                            alt="After Photo"
                        >

                    <?php endwhile; ?>

                </div>

            </div>


            <?php $photo_stmt->close(); ?>


        </div>


    <?php endwhile; ?>


<?php else: ?>


    <div class="empty">

        No completed history for this toilet yet.

    </div>


<?php endif; ?>


</div>

</body>

</html>