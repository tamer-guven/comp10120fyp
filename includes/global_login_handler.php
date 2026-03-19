<?php
session_start();
require_once '../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $conn->select_db("hotel_management_db");

    // 1. Check Staff Table First
    $stmt = $conn->prepare("SELECT staff_id, password_hash, first_name, role FROM staff WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {
            // Log in as Staff
            $_SESSION['staff_id'] = $user['staff_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on exact role
            $role = strtolower($user['role']);
            if (in_array($role, ['manager', 'admin', 'receptionist'])) {
                header("Location: ../staff/admin_dashboard.php");
            } elseif (in_array($role, ['cleaner', 'housekeeper'])) {
                header("Location: ../staff/housekeeping_dashboard.php");
            } else {
                header("Location: ../staff/admin_dashboard.php"); // Fallback
            }
            exit();
        } else {
            header("Location: ../login.php?error=invalid_password");
            exit();
        }
    }
    $stmt->close();

    // 2. If not staff, check Customers Table
    $stmt = $conn->prepare("SELECT customer_id, password_hash, first_name FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {
            // Log in as Customer
            $_SESSION['user_id'] = $user['customer_id'];
            $_SESSION['first_name'] = $user['first_name'];
            
            header("Location: ../client/clientdashboard.php");
            exit();
        } else {
            header("Location: ../login.php?error=invalid_password");
            exit();
        }
    }
    $stmt->close();

    // 3. Email not found in either table
    header("Location: ../login.php?error=no_user");
    exit();
}
?>