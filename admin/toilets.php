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
   ADD TOILET
========================= */

if (isset($_POST["add_toilet"])) {

    $name = trim($_POST["name"]);
    $toilet_number = trim($_POST["toilet_number"]);

    if ($name === "" || $toilet_number === "") {

        $error = "Please fill in all fields.";

    } else {

        $sql = "INSERT INTO toilets
                (name, toilet_number, is_active)
                VALUES (?, ?, 1)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $name, $toilet_number);

        if ($stmt->execute()) {

            $message = "Toilet added successfully.";

        } else {

            $error = "Toilet number already exists or an error occurred.";

        }

        $stmt->close();
    }
}


/* =========================
   EDIT TOILET
========================= */

if (isset($_POST["edit_toilet"])) {

    $id = intval($_POST["id"]);
    $name = trim($_POST["name"]);
    $toilet_number = trim($_POST["toilet_number"]);

    if ($name === "" || $toilet_number === "") {

        $error = "Please fill in all fields.";

    } else {

        $sql = "UPDATE toilets
                SET name = ?, toilet_number = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssi",
            $name,
            $toilet_number,
            $id
        );

        if ($stmt->execute()) {

            $message = "Toilet updated successfully.";

        } else {

            $error = "Unable to update toilet.";

        }

        $stmt->close();
    }
}


/* =========================
   DELETE / DEACTIVATE
========================= */

if (isset($_POST["delete_toilet"])) {

    $id = intval($_POST["id"]);

    /*
       We deactivate instead of physically deleting
       the toilet so old history remains safe.
    */

    $sql = "UPDATE toilets
            SET is_active = 0
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        $message = "Toilet deactivated successfully.";

    } else {

        $error = "Unable to deactivate toilet.";

    }

    $stmt->close();
}


/* =========================
   ACTIVATE TOILET
========================= */

if (isset($_POST["activate_toilet"])) {

    $id = intval($_POST["id"]);

    $sql = "UPDATE toilets
            SET is_active = 1
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {

        $message = "Toilet activated successfully.";

    }

    $stmt->close();
}


/* =========================
   GET TOILET FOR EDIT
========================= */

$edit_toilet = null;

if (isset($_GET["edit"])) {

    $id = intval($_GET["edit"]);

    $sql = "SELECT id, name, toilet_number, is_active
            FROM toilets
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $edit_toilet = $result->fetch_assoc();

    }

    $stmt->close();
}


/* =========================
   GET ALL TOILETS
========================= */

$sql = "SELECT id, name, toilet_number, is_active, created_at
        FROM toilets
        ORDER BY id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Toilets</title>

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

    <h1>Manage Toilets</h1>

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
     ADD / EDIT TOILET
========================= -->

<div class="card">

<?php if ($edit_toilet): ?>

<h2>Edit Toilet</h2>

<form method="POST">

    <input
        type="hidden"
        name="id"
        value="<?= $edit_toilet["id"] ?>"
    >

    <label>
        Toilet Name
    </label>

    <input
        type="text"
        name="name"
        value="<?= htmlspecialchars($edit_toilet["name"]) ?>"
        required
    >

    <label>
        Toilet Number
    </label>

    <input
        type="text"
        name="toilet_number"
        value="<?= htmlspecialchars($edit_toilet["toilet_number"]) ?>"
        required
    >

    <button type="submit" name="edit_toilet">
        Save Changes
    </button>

    <a class="cancel" href="toilets.php">
        Cancel
    </a>

</form>

<?php else: ?>

<h2>Add Toilet</h2>

<form method="POST">

    <label>
        Toilet Name
    </label>

    <input
        type="text"
        name="name"
        placeholder="Example: Male Toilet"
        required
    >

    <label>
        Toilet Number
    </label>

    <input
        type="text"
        name="toilet_number"
        placeholder="Example: T01"
        required
    >

    <button type="submit" name="add_toilet">
        Add Toilet
    </button>

</form>

<?php endif; ?>

</div>


<!-- =========================
     TOILET LIST
========================= -->

<div class="card">

<h2>Toilet List</h2>

<table>

<tr>

    <th>ID</th>

    <th>Toilet Name</th>

    <th>Toilet Number</th>

    <th>Status</th>

    <th>Created</th>

    <th>Actions</th>

</tr>


<?php if ($result->num_rows > 0): ?>

<?php while ($toilet = $result->fetch_assoc()): ?>

<tr>

    <td>
        <?= $toilet["id"] ?>
    </td>

    <td>
        <?= htmlspecialchars($toilet["name"]) ?>
    </td>

    <td>
        <?= htmlspecialchars($toilet["toilet_number"]) ?>
    </td>

    <td>

        <?php if ($toilet["is_active"]): ?>

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
        <?= htmlspecialchars($toilet["created_at"]) ?>
    </td>

    <td>

        <a href="toilets.php?edit=<?= $toilet["id"] ?>">
            Edit
        </a>


        <?php if ($toilet["is_active"]): ?>

        <form
            method="POST"
            class="action-form"
        >

            <input
                type="hidden"
                name="id"
                value="<?= $toilet["id"] ?>"
            >

            <button
                type="submit"
                name="delete_toilet"
                class="delete"
                onclick="return confirm('Deactivate this toilet?')"
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
                value="<?= $toilet["id"] ?>"
            >

            <button
                type="submit"
                name="activate_toilet"
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
        No toilets yet.
    </td>

</tr>

<?php endif; ?>

</table>

</div>


</div>

</body>

</html>