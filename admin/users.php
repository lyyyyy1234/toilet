<?php

require_once "../db.php";

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

/* =========================
   ADD USER
========================= */

if (isset($_POST["add_user"])) {

    $username = trim($_POST["username"]);
    $full_name = trim($_POST["full_name"]);

    if ($username === "" || $full_name === "") {

        $error = "Please fill in all fields.";

    } else {

        $sql = "INSERT INTO users
                (username, full_name, role, is_active)
                VALUES (?, ?, 'user', 1)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $full_name);

        if ($stmt->execute()) {

            $message = "Student account created successfully.";

        } else {

            $error = "Username already exists or an error occurred.";

        }

        $stmt->close();
    }
}


/* =========================
   DELETE / DEACTIVATE USER
========================= */

if (isset($_POST["delete_user"])) {

    $id = intval($_POST["id"]);

    $sql = "UPDATE users
            SET is_active = 0
            WHERE id = ? AND role = 'user'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        $message = "Student account deactivated successfully.";

    } else {

        $error = "Unable to deactivate account.";

    }

    $stmt->close();
}


/* =========================
   ACTIVATE USER
========================= */

if (isset($_POST["activate_user"])) {

    $id = intval($_POST["id"]);

    $sql = "UPDATE users
            SET is_active = 1
            WHERE id = ? AND role = 'user'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        $message = "Student account activated successfully.";

    }

    $stmt->close();
}


/* =========================
   EDIT USER
========================= */

if (isset($_POST["edit_user"])) {

    $id = intval($_POST["id"]);
    $username = trim($_POST["username"]);
    $full_name = trim($_POST["full_name"]);

    if ($username === "" || $full_name === "") {

        $error = "Please fill in all fields.";

    } else {

        $sql = "UPDATE users
                SET username = ?, full_name = ?
                WHERE id = ? AND role = 'user'";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $username, $full_name, $id);

        if ($stmt->execute()) {

            $message = "Student account updated successfully.";

        } else {

            $error = "Unable to update account.";

        }

        $stmt->close();
    }
}


/* =========================
   GET USER FOR EDIT
========================= */

$edit_user = null;

if (isset($_GET["edit"])) {

    $id = intval($_GET["edit"]);

    $sql = "SELECT id, username, full_name, is_active
            FROM users
            WHERE id = ? AND role = 'user'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $edit_user = $result->fetch_assoc();

    }

    $stmt->close();
}


/* =========================
   GET ALL STUDENTS
========================= */

$sql = "SELECT id, username, full_name, is_active, created_at
        FROM users
        WHERE role = 'user'
        ORDER BY id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Users</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #fffefe;
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
    padding: 30px;
    max-width: 1200px;
    margin: auto;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.card h2 {
    margin-top: 0;
    color: #1769aa;
}

label {
    display: block;
    margin-top: 12px;
    margin-bottom: 5px;
    font-weight: bold;
}

input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

button {
    margin-top: 15px;
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    background: #1769aa;
    color: white;
}

.cancel {
    background: #777;
    text-decoration: none;
    color: white;
    padding: 10px 18px;
    border-radius: 6px;
    display: inline-block;
    margin-left: 5px;
}

.delete {
    background: #d32f2f;
}

.activate {
    background: #2e7d32;
}

.message {
    background: #d9f5df;
    color: #176b2c;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.error {
    background: #ffe0e0;
    color: #a00000;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

th {
    background: #1769aa;
    color: white;
}

.active {
    color: green;
    font-weight: bold;
}

.inactive {
    color: red;
    font-weight: bold;
}

.action-form {
    display: inline;
}

</style>

</head>

<body>


<div class="header">

    <h1>Manage Students</h1>

    <div>

        <a href="dashboard.php">
            Dashboard
        </a>

    </div>

</div>


<div class="container">


<?php if ($message !== ""): ?>

<div class="message">
    <?= htmlspecialchars($message) ?>
</div>

<?php endif; ?>


<?php if ($error !== ""): ?>

<div class="error">
    <?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>


<!-- =========================
     ADD / EDIT USER
========================= -->

<div class="card">

<?php if ($edit_user): ?>

<h2>Edit Student</h2>

<form method="POST">

    <input
        type="hidden"
        name="id"
        value="<?= $edit_user["id"] ?>"
    >

    <label>
        Username
    </label>

    <input
        type="text"
        name="username"
        value="<?= htmlspecialchars($edit_user["username"]) ?>"
        required
    >

    <label>
        Full Name
    </label>

    <input
        type="text"
        name="full_name"
        value="<?= htmlspecialchars($edit_user["full_name"]) ?>"
        required
    >

    <button type="submit" name="edit_user">
        Save Changes
    </button>

    <a class="cancel" href="users.php">
        Cancel
    </a>

</form>

<?php else: ?>

<h2>Add Student</h2>

<form method="POST">

    <label>
        Username
    </label>

    <input
        type="text"
        name="username"
        placeholder="Example: student01"
        required
    >

    <label>
        Full Name
    </label>

    <input
        type="text"
        name="full_name"
        placeholder="Example: Ali"
        required
    >

    <button type="submit" name="add_user">
        Add Student
    </button>

</form>

<?php endif; ?>

</div>


<!-- =========================
     USER LIST
========================= -->

<div class="card">

<h2>Student Accounts</h2>

<table>

<tr>

    <th>ID</th>

    <th>Username</th>

    <th>Full Name</th>

    <th>Status</th>

    <th>Created</th>

    <th>Actions</th>

</tr>


<?php if ($result->num_rows > 0): ?>

<?php while ($user = $result->fetch_assoc()): ?>

<tr>

    <td>
        <?= $user["id"] ?>
    </td>

    <td>
        <?= htmlspecialchars($user["username"]) ?>
    </td>

    <td>
        <?= htmlspecialchars($user["full_name"]) ?>
    </td>

    <td>

        <?php if ($user["is_active"]): ?>

            <span class="active">
                Active
            </span>

        <?php else: ?>

            <span class="inactive">
                Inactive
            </span>

        <?php endif; ?>

    </td>

    <td>
        <?= htmlspecialchars($user["created_at"]) ?>
    </td>

    <td>

        <a href="users.php?edit=<?= $user["id"] ?>">
            Edit
        </a>


        <?php if ($user["is_active"]): ?>

        <form
            method="POST"
            class="action-form"
        >

            <input
                type="hidden"
                name="id"
                value="<?= $user["id"] ?>"
            >

            <button
                type="submit"
                name="delete_user"
                class="delete"
                onclick="return confirm('Deactivate this student account?')"
            >
                Delete
            </button>

        </form>

        <?php else: ?>

        <form
            method="POST"
            class="action-form"
        >

            <input
                type="hidden"
                name="id"
                value="<?= $user["id"] ?>"
            >

            <button
                type="submit"
                name="activate_user"
                class="activate"
            >
                Activate
            </button>

        </form>

        <?php endif; ?>

    </td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

    <td colspan="6">
        No student accounts yet.
    </td>

</tr>

<?php endif; ?>

</table>

</div>


</div>

</body>

</html>