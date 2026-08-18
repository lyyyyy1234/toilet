<?php

require_once "db.php";

session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE username = ? AND is_active = 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // Account has no password yet
        if (empty($user["password_hash"])) {

            $_SESSION["setup_user_id"] = $user["id"];

            header("Location: setup_password.php");
            exit;

        }

        // Check password
        if (password_verify($password, $user["password_hash"])) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["role"] = $user["role"];

            if ($user["role"] === "admin") {

                header("Location: admin/dashboard.php");

            } else {

                header("Location: user/dashboard.php");

            }

            exit;

        } else {

            $error = "Invalid username or password.";

        }

    } else {

        $error = "Invalid username or account is inactive.";

    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Toilet Monitoring - Login</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #020202;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-box {
            width: 380px;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
        }

        h1 {
            text-align: center;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 22px;
            border: none;
            border-radius: 6px;
            background: #1769aa;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #12588d;
        }

        .error {
            background: #ffe5e5;
            color: #b00000;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

    </style>

</head>

<body>

<div class="login-box">

    <h1>Toilet Monitoring</h1>

    <div class="subtitle">
        Cleanliness Check-In / Check-Out System
    </div>

    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <label>Username</label>

        <input
            type="text"
            name="username"
            placeholder="Enter username"
            required
        >

        <label>Password</label>

        <input
            type="password"
            name="password"
            placeholder="Enter password"
        >

        <button type="submit">
            Login
        </button>

    </form>

</div>

</body>

</html>