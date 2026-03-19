<?php
// Set global timezone to prevent booking discrepancies
date_default_timezone_set('Europe/London');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel_management_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // Log error internally and show generic message to user for security
    error_log("Connection Failed: " . $conn->connect_error);
    die("A system error occurred. Please try again later.");
}

$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>