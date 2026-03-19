<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_SESSION['user_id'];
    $room_type_id = (int)$_POST['room_type_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];

    if ($check_in < date('Y-m-d') || $check_out <= $check_in) {
        header("Location: ../client/clientbook.php?error=dates");
        exit();
    }

    // START TRANSACTION: Prevents race conditions during check-then-insert
    $conn->begin_transaction();

    try {
        // Lock the room_type row to prevent concurrent updates
        $stmt = $conn->prepare("SELECT base_price FROM room_types WHERE room_type_id = ? FOR UPDATE");
        $stmt->bind_param("i", $room_type_id);
        $stmt->execute();
        $type = $stmt->get_result()->fetch_assoc();

        if (!$type) throw new Exception("invalid_type");

        // Availability check inside the lock
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
        $free = $avail_stmt->get_result()->fetch_assoc()['free_rooms'];

        if ($free <= 0) throw new Exception("unavailable");

        $nights = max(1, (new DateTime($check_in))->diff(new DateTime($check_out))->days);
        $total = $type['base_price'] * $nights;

        $insert = $conn->prepare("INSERT INTO bookings (room_type_id, customer_id, check_in_date, check_out_date, price, booking_status) VALUES (?, ?, ?, ?, ?, 'booked')");
        $insert->bind_param("iissd", $room_type_id, $customer_id, $check_in, $check_out, $total);
        $insert->execute();

        $conn->commit();
        header("Location: ../client/clientdashboard.php?booked=1");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../client/clientbook.php?error=" . $e->getMessage());
    }
}