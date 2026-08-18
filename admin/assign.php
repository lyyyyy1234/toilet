<?php

require_once "../db.php";

session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

$selected_user_id = 0;


/* =========================
   SAVE ASSIGNMENTS
========================= */

if (isset($_POST["save_assignment"])) {

    $selected_user_id = intval($_POST["user_id"]);
    $toilet_ids = $_POST["toilet_ids"] ?? [];

    if ($selected_user_id <= 0) {

        $error = "Please select a student.";

    } else {

        /*
           Start transaction so all assignments
           are saved together.
        */

        $conn->begin_transaction();

        try {

            /*
               Remove old assignments first.
            */

            $sql = "DELETE FROM user_toilets
                    WHERE user_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $selected_user_id);
            $stmt->execute();
            $stmt->close();


            /*
               Add the newly selected toilets.
            */

            if (!empty($toilet_ids)) {

                $sql = "INSERT INTO user_toilets
                        (user_id, toilet_id)
                        VALUES (?, ?)";

                $stmt = $conn->prepare($sql);

                foreach ($toilet_ids as $toilet_id) {

                    $toilet_id = intval($toilet_id);

                    $stmt->bind_param(
                        "ii",
                        $selected_user_id,
                        $toilet_id
                    );

                    $stmt->execute();
                }

                $stmt->close();
            }


            $conn->commit();

            $message = "Toilet assignments saved successfully.";

        } catch (Exception $e) {

            $conn->rollback();

            $error = "Unable to save assignments.";

        }
    }
}


/* =========================
   USER SELECTED FROM URL
========================= */

if (isset($_GET["user_id"])) {

    $selected_user_id = intval($_GET["user_id"]);

}


/* =========================
   GET STUDENTS
========================= */

$sql = "SELECT id, username, full_name
        FROM users
        WHERE role = 'user'
        AND is_active = 1
        ORDER BY full_name";

$users_result = $conn->query($sql);


/* =========================
   GET TOILETS
========================= */

$sql = "SELECT id, name, toilet_number
        FROM toilets
        WHERE is_active = 1
        ORDER BY toilet_number";

$toilets_result = $conn->query($sql);


/* =========================
   GET CURRENT ASSIGNMENTS
========================= */

$assigned_toilets = [];

if ($selected_user_id > 0) {

    $sql = "SELECT toilet_id
            FROM user_toilets
            WHERE user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $assigned_toilets[] = intval($row["toilet_id"]);

    }

    $stmt->close();
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Assign Toilets</title>

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
    max-width: 1000px;
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
    margin-bottom: 6px;
    font-weight: bold;
}

select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 16px;
}

.toilet-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 15px;
}

.toilet-item {
    background: #f5f8fb;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 8px;
}

.toilet-item label {
    margin: 0;
    font-weight: normal;
    cursor: pointer;
}

.toilet-item input {
    margin-right: 10px;
}

button {
    margin-top: 20px;
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    background: #1769aa;
    color: white;
    font-size: 16px;
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

.info {
    background: #e8f2ff;
    color: #174a7c;
    padding: 12px;
    border-radius: 6px;
    margin-top: 15px;
}

@media (max-width: 700px) {

    .toilet-list {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>


<div class="header">

    <h1>Assign Toilets</h1>

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


<div class="card">

<h2>Select Student</h2>

<form method="GET">

    <label>
        Student / User
    </label>

    <select
        name="user_id"
        onchange="this.form.submit()"
        required
    >

        <option value="">
            -- Select Student --
        </option>

        <?php while ($user = $users_result->fetch_assoc()): ?>

            <option
                value="<?= $user["id"] ?>"
                <?= ($selected_user_id == $user["id"]) ? "selected" : "" ?>
            >

                <?= htmlspecialchars($user["full_name"]) ?>

                -

                <?= htmlspecialchars($user["username"]) ?>

            </option>

        <?php endwhile; ?>

    </select>

</form>

</div>


<?php if ($selected_user_id > 0): ?>


<div class="card">

<h2>Assign Toilets</h2>

<p>
Select one or more toilets for this student.
</p>


<form method="POST">

    <input
        type="hidden"
        name="user_id"
        value="<?= $selected_user_id ?>"
    >


    <div class="toilet-list">


        <?php if ($toilets_result->num_rows > 0): ?>


            <?php while ($toilet = $toilets_result->fetch_assoc()): ?>


                <div class="toilet-item">

                    <label>

                        <input
                            type="checkbox"
                            name="toilet_ids[]"
                            value="<?= $toilet["id"] ?>"

                            <?= in_array(
                                intval($toilet["id"]),
                                $assigned_toilets
                            ) ? "checked" : "" ?>

                        >

                        <strong>
                            <?= htmlspecialchars($toilet["toilet_number"]) ?>
                        </strong>

                        -

                        <?= htmlspecialchars($toilet["name"]) ?>

                    </label>

                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <p>
                No active toilets available.
            </p>


        <?php endif; ?>


    </div>


    <button
        type="submit"
        name="save_assignment"
    >

        Save Assignments

    </button>


</form>


<div class="info">

    You can select multiple toilets for one student.

    The same toilet can also be assigned to multiple students.

</div>


</div>


<?php endif; ?>


</div>

</body>

</html>