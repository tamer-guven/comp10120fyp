<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel_management_db"; // Matches create_tables.php

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>