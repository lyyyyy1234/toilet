<?php

require_once "../db.php";

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Admin Dashboard</title>

    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fcf7f7;
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
            padding: 30px;
        }

        .welcome {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .menu {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-decoration: none;
            color: #222;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .card h2 {
            margin-top: 0;
            color: #1769aa;
        }

        .card p {
            color: #666;
        }

    </style>

</head>

<body>

<div class="header">

    <h1>Admin Dashboard</h1>

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
            You are logged in as Administrator.
        </p>

    </div>

    <div class="menu">

        <a class="card" href="users.php">

            <h2>👨‍🎓 Manage Users</h2>

            <p>
                Add, edit and delete student/user accounts.
            </p>

        </a>

        <a class="card" href="toilets.php">

            <h2>🚽 Manage Toilets</h2>

            <p>
                Add, edit and delete toilet names or numbers.
            </p>

        </a>

        <a class="card" href="assign.php">

            <h2>🔗 Assign Toilets</h2>

            <p>
                Assign one or multiple toilets to students.
            </p>

        </a>

        <a class="card" href="history.php">

            <h2>📋 Toilet History</h2>

            <p>
                View complete check-in and check-out history.
            </p>

        </a>

    </div>

</div>

</body>

</html>