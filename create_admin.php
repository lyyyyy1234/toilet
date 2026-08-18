<?php

require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $full_name = trim($_POST["full_name"]);
    $password = $_POST["password"];

    if ($username === "" || $full_name === "" || $password === "") {

        $message = "Please fill in all fields.";

    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters.";

    } else {

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users
                (username, full_name, password_hash, role, is_active)
                VALUES (?, ?, ?, 'admin', 1)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sss",
            $username,
            $full_name,
            $password_hash
        );

        if ($stmt->execute()) {

            $message = "Admin account created successfully!";

        } else {

            $message = "Error: " . $stmt->error;

        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Create Admin</title>

</head>

<body>

<h2>Create Admin Account</h2>

<?php if ($message !== ""): ?>

    <p>
        <?= htmlspecialchars($message) ?>
    </p>

<?php endif; ?>

<form method="POST">

    <p>
        <label>Username</label><br>
        <input type="text" name="username" required>
    </p>

    <p>
        <label>Full Name</label><br>
        <input type="text" name="full_name" required>
    </p>

    <p>
        <label>Password</label><br>
        <input type="password" name="password" minlength="8" required>
    </p>

    <button type="submit">
        Create Admin
    </button>

</form>

</body>

</html>