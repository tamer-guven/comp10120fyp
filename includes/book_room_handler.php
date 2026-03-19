<?php
session_start();
require_once '../database/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_SESSION['user_id'];
    $room_type_id = $_POST['room_type_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];

    // Server-side Date Validation
    $today = date('Y-m-d');
    if ($check_in < $today || $check_out <= $check_in) {
        die("Invalid dates selected. Please go back and try again.");
    }

    // 1. Check that this room type exists and calculate prices
    $stmt = $conn->prepare("SELECT base_price FROM room_types WHERE room_type_id = ?");
    $stmt->bind_param("i", $room_type_id);
    $stmt->execute();
    $type_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($type_result) {
        // 2. Prevent Infinite Bookings (Check Availability before booking)
        $avail_stmt = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM rooms WHERE room_type_id = ?) - 
                (SELECT COUNT(*) FROM bookings 
                 WHERE room_type_id = ? 
                 AND booking_status IN ('booked', 'checked_in') 
                 AND check_in_date < ? 
                 AND check_out_date > ?) AS free_rooms
        ");
        $avail_stmt->bind_param("iiss", $room_type_id, $room_type_id, $check_out, $check_in);
        $avail_stmt->execute();
        $avail_result = $avail_stmt->get_result()->fetch_assoc();
        $avail_stmt->close();

        if ($avail_result['free_rooms'] <= 0) {
            die("Sorry, there are no available rooms of this type for the selected dates. Please go back and select a different room or date.");
        }

        $base_price = $type_result['base_price'];

        // Calculate number of nights
        $nights = max(1, (strtotime($check_out) - strtotime($check_in)) / 86400);
        $total_price = $base_price * $nights;

        // 3. Insert into bookings table (room_id defaults to NULL dynamically)
        $sql = "INSERT INTO bookings (room_type_id, customer_id, check_in_date, check_out_date, price, booking_status) 
                VALUES (?, ?, ?, ?, ?, 'booked')";
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