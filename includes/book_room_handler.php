<?php
session_start();
require_once '../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $customer_id = $_SESSION['user_id'];
    $room_type_id = $_POST['room_type_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];

    // 1. Check that this room type exists
    $stmt = $conn->prepare("SELECT base_price FROM room_types WHERE room_type_id = ?");
    $stmt->bind_param("i", $room_type_id);
    $stmt->execute();
    $type_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($type_result) {
        $base_price = $type_result['base_price'];

        // Calculate number of nights
        $nights = max(1, (strtotime($check_out) - strtotime($check_in)) / 86400);
        $total_price = $base_price * $nights;

        // 2. Insert into bookings table (room_id=0 means not assigned yet)
        $sql = "INSERT INTO bookings (room_id, room_type_id, customer_id, check_in_date, check_out_date, price, booking_status) 
                VALUES (0, ?, ?, ?, ?, ?, 'booked')";
        $insert = $conn->prepare($sql);
        $insert->bind_param("iissd", $room_type_id, $customer_id, $check_in, $check_out, $total_price);
        
        if ($insert->execute()) {
            header("Location: ../client/clientdashboard.php?booked=1");
        } else {
            echo "Booking Error: " . $conn->error;
        }
        $insert->close();
    } else {
        echo "Invalid room type selected.";
    }
}
?>