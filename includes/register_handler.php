<?php
require_once '../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fname    = $_POST['first_name'];
    $lname    = $_POST['last_name'];
    $address  = $_POST['user_address'];
    $dob      = $_POST['date_of_birth'];
    $phone    = $_POST['phone_number'];
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO customers (first_name, last_name, user_address, date_of_birth, phone_number, email, password_hash) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $fname, $lname, $address, $dob, $phone, $email, $password_hash);

    if ($stmt->execute()) {
        header("Location: ../client/clientlogin.php?success=1");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>