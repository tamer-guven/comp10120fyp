<?php
session_start();
require_once '../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['staff-username'];
    $password = $_POST['staff-password'];

    $conn->select_db("hotel_management_db");

    // Select the hashed password and staff info including role
    $stmt = $conn->prepare("SELECT staff_id, password_hash, first_name, role FROM staff WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Verify the submitted password against the hash in the DB
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['staff_id'] = $user['staff_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['role'] = $user['role'];

            // Route based on role
            if ($user['role'] === 'admin') {
                header("Location: ../staff/admin_dashboard.php");
            } elseif ($user['role'] === 'manager') {
                header("Location: ../staff/manager_dashboard.php");
            } elseif ($user['role'] === 'cleaner') {
                header("Location: ../staff/housekeeping_dashboard.php");
            } else {
                // Default to admin dashboard for unknown roles
                header("Location: ../staff/admin_dashboard.php");
            }
            exit();
        } else {
            header("Location: ../staff/stafflogin.php?error=invalid_password");
        }
    } else {
        header("Location: ../staff/stafflogin.php?error=no_user");
    }
}
?>
