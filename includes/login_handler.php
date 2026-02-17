<?php
session_start();
require_once '../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['client-username']; // From the 'name' attribute in clientlogin.php
    $password = $_POST['client-password'];

    // Select the hashed password and first name for session use
    $stmt = $conn->prepare("SELECT customer_id, password_hash, first_name FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Verify the submitted password against the hash in the DB
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['customer_id'];
            $_SESSION['first_name'] = $user['first_name'];
            
            header("Location: ../client/clientdashboard.php");
            exit();
        } else {
            header("Location: ../client/clientlogin.php?error=invalid_password");
        }
    } else {
        header("Location: ../client/clientlogin.php?error=no_user");
    }
}
?>