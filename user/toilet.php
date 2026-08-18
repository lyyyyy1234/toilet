<?php

require_once "../db.php";

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION["user_id"]);
$toilet_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

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
   CHECK ACTIVE SESSION
========================= */

$sql = "SELECT
            id,
            check_in_at,
            check_in_comment
        FROM toilet_sessions
        WHERE user_id = ?
        AND toilet_id = ?
        AND check_out_at IS NULL
        ORDER BY id DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $user_id,
    $toilet_id
);

$stmt->execute();

$active_result = $stmt->get_result();

$active_session = null;

if ($active_result->num_rows === 1) {

    $active_session = $active_result->fetch_assoc();

}

$stmt->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    <?= htmlspecialchars($toilet["toilet_number"]) ?>
    - Toilet Monitoring
</title>

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
    max-width: 1000px;

    margin: auto;

    padding: 30px;
}

.card {
    background: white;

    padding: 30px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 3px 10px rgba(0,0,0,0.08);
}

.card h2 {
    color: #1769aa;

    margin-top: 0;

    font-size: 32px;
}

.toilet-number {
    font-size: 22px;

    font-weight: bold;

    color: #555;

    margin-bottom: 25px;
}

.status {
    padding: 18px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-size: 17px;
}

.status-ready {
    background: #e8f5e9;

    color: #236b2b;
}

.status-active {
    background: #fff3cd;

    color: #856404;
}

.button {
    display: inline-block;

    margin-top: 5px;

    margin-right: 8px;

    padding: 13px 22px;

    background: #1769aa;

    color: white;

    text-decoration: none;

    border-radius: 7px;

    font-size: 16px;
}

.button:hover {
    background: #12588d;
}

.checkout {
    background: #d32f2f;
}

.checkout:hover {
    background: #b71c1c;
}

.history {
    background: #5e35b1;
}

.history:hover {
    background: #45278a;
}

.back {
    background: #777;
}

.back:hover {
    background: #555;
}

</style>

</head>

<body>


<div class="header">

    <h1>Toilet Monitoring</h1>

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


    <div class="toilet-number">

        Toilet Number:
        <?= htmlspecialchars($toilet["toilet_number"]) ?>

    </div>


    <?php if ($active_session): ?>


        <div class="status status-active">

            <strong>
                Active Check-In
            </strong>

            <br><br>

            Check In Time:

            <?= htmlspecialchars(
                $active_session["check_in_at"]
            ) ?>


            <?php if (
                !empty($active_session["check_in_comment"])
            ): ?>

                <br><br>

                Comment:

                <?= htmlspecialchars(
                    $active_session["check_in_comment"]
                ) ?>

            <?php endif; ?>

        </div>


        <a
            class="button checkout"
            href="checkout.php?session_id=<?= $active_session["id"] ?>"
        >
            Check Out
        </a>


    <?php else: ?>


        <div class="status status-ready">

            You are not currently checked in.

            Click <strong>Check In</strong>
            when entering this toilet.

        </div>


        <a
            class="button"
            href="checkin.php?toilet_id=<?= $toilet_id ?>"
        >
            Check In
        </a>


    <?php endif; ?>


    <a
        class="button history"
        href="history.php?toilet_id=<?= $toilet_id ?>"
    >
        View History
    </a>


    <a
        class="button back"
        href="dashboard.php"
    >
        Back
    </a>


</div>


</div>

</body>

</html>