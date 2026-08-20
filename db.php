<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'synergy1_yenping');
define('DB_PASSWORD', 'R.zb0ZwEuGZ}*fW2');
define('DB_NAME', 'synergy1_lawlifang_toilet');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>