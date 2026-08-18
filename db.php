<?php

$host = "localhost";
$user = "root";
$password = "Whatpass@1113";
$database = "toilet_monitoring";
$port = 3307;

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>