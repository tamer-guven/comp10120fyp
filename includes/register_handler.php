<?php
require_once '../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check for missing fields
    if (
        empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['user_address']) ||
        empty($_POST['date_of_birth']) || empty($_POST['phone_number']) || empty($_POST['email']) ||
        empty($_POST['password'])
    ) {
        header("Location: ../client/clientregister.php?error=missing_fields");
        exit();
    }

    $fname = $_POST['first_name'];
    $lname = $_POST['last_name'];
    $address = $_POST['user_address'];
    $dob = $_POST['date_of_birth'];
    $phone = $_POST['phone_number'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $email_pattern = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
    if (!preg_match($email_pattern, $email)) {
        header("Location: ../client/clientregister.php?error=invalid_email");
        exit();
    }

    if (strlen($password) < 8) {
        header("Location: ../client/clientregister.php?error=weak_password");
        exit();
    }

    // Hash the password for security
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL using table columns from create_tables.php
    $sql = "INSERT INTO customers (first_name, last_name, user_address, date_of_birth, phone_number, email, password_hash) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $fname, $lname, $address, $dob, $phone, $email, $password_hash);

    try {
        if ($stmt->execute()) {
            header("Location: ../login.php?success=1");
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            header("Location: ../client/clientregister.php?error=email_taken");
            exit();
        } else {
            // Log locally for you to fix, but keep the user in the dark
            error_log("Registration Failure: " . $e->getMessage());
            die("A system error occurred. Please try again later.");
        }
    }
}
?>