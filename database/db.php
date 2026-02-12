<?php
$servername = "localhost";
$username = "root";
$password = "";
//$dbname = "hotel_management_db";

$conn = new mysqli($servername, $username, $password);


if ($conn -> connect_error) {
    die("Connection Failed: ". $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>
