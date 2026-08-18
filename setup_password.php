<?php

require_once "db.php";

session_start();

/*
    Student must arrive here from login.php
    when their account does not have a password yet.
*/

if (!isset($_SESSION["setup_user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = intval($_SESSION["setup_user_id"]);

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($password === "" || $confirm_password === "") {

        $error = "Please fill in all fields.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {

        $password_hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $sql = "UPDATE users
                SET password_hash = ?
                WHERE id = ?
                AND role = 'user'
                AND is_active = 1";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "si",
            $password_hash,
            $user_id
        );

        if ($stmt->execute() && $stmt->affected_rows === 1) {

            /*
                Password has been successfully created.
                Remove temporary setup session.
            */

            unset($_SESSION["setup_user_id"]);

            $message = "Password created successfully!";

            /*
                Redirect to login after 1 second.
            */

            header("Refresh: 1; URL=login.php");

        } else {

            $error = "Unable to create password.";

        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Set Password</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #070707;

    display: flex;
    justify-content: center;
    align-items: center;

    min-height: 100vh;
}

.box {
    width: 420px;
    background: white;
    padding: 35px;

    border-radius: 12px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.12);
}

h1 {
    text-align: center;
    color: #1769aa;
}

.description {
    text-align: center;
    color: #666;
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
    padding: 12px;

    border: 1px solid #ccc;
    border-radius: 6px;

    font-size: 15px;
}

button {
    width: 100%;

    margin-top: 22px;

    padding: 12px;

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
    background: #ffe0e0;
    color: #a00000;

    padding: 10px;

    border-radius: 6px;

    margin-bottom: 15px;
}

.success {
    background: #d9f5df;
    color: #176b2c;

    padding: 10px;

    border-radius: 6px;

    margin-bottom: 15px;
}

.note {
    margin-top: 20px;

    padding: 12px;

    background: #eef6ff;

    border-radius: 6px;

    color: #24557d;

    font-size: 14px;
}

</style>

</head>

<body>

<div class="box">

    <h1>Set Your Password</h1>

    <div class="description">

        This is your first login.
        Please create your own password.

    </div>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <?php if ($message !== ""): ?>

        <div class="success">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <form method="POST">

        <label>
            New Password
        </label>

        <input
            type="password"
            name="password"
            minlength="8"
            required
        >


        <label>
            Confirm Password
        </label>

        <input
            type="password"
            name="confirm_password"
            minlength="8"
            required
        >


        <button type="submit">

            Set Password

        </button>

    </form>


    <div class="note">

        Password must contain at least 8 characters.

    </div>

</div>

</body>

</html>