<?php

require_once "../db.php";

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "user") {
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION["user_id"]);

$sql = "SELECT
            t.id,
            t.name,
            t.toilet_number
        FROM toilets t
        INNER JOIN user_toilets ut
            ON t.id = ut.toilet_id
        WHERE ut.user_id = ?
        AND t.is_active = 1
        ORDER BY t.toilet_number";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Dashboard</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #000000;
}
h2{
    color: #fefefe;
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

.logout {
    color: white;
    text-decoration: none;
    background: #d32f2f;
    padding: 10px 15px;
    border-radius: 6px;
}

.container {
    max-width: 1100px;
    margin: auto;
    padding: 30px;
}

.welcome {
    background: white;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 25px;
}

.welcome h2 {
    margin-top: 0;
    color: #1769aa;
}

.toilet-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.toilet-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.toilet-card h2 {
    color: #1769aa;
    margin-top: 0;
}

.toilet-number {
    font-size: 18px;
    font-weight: bold;
    color: #555;
}

.button {
    display: inline-block;
    margin-top: 15px;
    padding: 11px 18px;
    background: #1769aa;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}

.no-toilet {
    background: #fff3cd;
    color: #856404;
    padding: 15px;
    border-radius: 8px;
}

@media (max-width: 700px) {

    .toilet-grid {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>

<div class="header">

    <h1>Student Dashboard</h1>

    <a class="logout" href="../logout.php">
        Logout
    </a>

</div>


<div class="container">

    <div class="welcome">

        <h2>
            Welcome, <?= htmlspecialchars($_SESSION["full_name"]) ?>
        </h2>

        <p>
            These are the toilets assigned to you.
        </p>

    </div>


    <h2>My Assigned Toilets</h2>


    <?php if ($result->num_rows > 0): ?>

        <div class="toilet-grid">

            <?php while ($toilet = $result->fetch_assoc()): ?>

                <div class="toilet-card">

                    <h2>
                        🚽
                        <?= htmlspecialchars($toilet["name"]) ?>
                    </h2>

                    <div class="toilet-number">

                        Toilet Number:
                        <?= htmlspecialchars($toilet["toilet_number"]) ?>

                    </div>

                    <a
                        class="button"
                        href="toilet.php?id=<?= $toilet["id"] ?>"
                    >
                        Open Toilet
                    </a>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="no-toilet">

            No toilets have been assigned to your account yet.

            Please contact the administrator.

        </div>

    <?php endif; ?>

</div>

</body>

</html>