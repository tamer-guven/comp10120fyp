<?php
require_once 'db.php';

$conn->select_db("hotel_management_db");

// Test staff data
$first_name = "manager";
$last_name = "User";
$role = "manager";
$phone_number = "1234567890";
$email = "admin@example.com";
$password = "admin123";

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO staff (first_name, last_name, role, phone_number, email, password_hash) 
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $first_name, $last_name, $role, $phone_number, $email, $password_hash);

if ($stmt->execute()) {
    echo "Staff user created successfully!<br>";
    echo "Email: " . $email . "<br>";
    echo "Password: " . $password . "<br>";
    echo "<a href='../staff/stafflogin.php'>Go to Staff Login</a>";
} else {
    echo "Error creating staff user: " . $stmt->error;
}

$conn->close();
?>
