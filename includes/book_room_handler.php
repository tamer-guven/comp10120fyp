<?php
session_start();
require_once '../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $customer_id = $_SESSION['user_id'];
    $room_type_id = $_POST['room_type_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];

    // 1. Find an available room of this type (simplified logic)
    $stmt = $conn->prepare("SELECT room_id FROM rooms WHERE room_type_id = ? LIMIT 1");
    $stmt->bind_param("i", $room_type_id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();

    if ($room) {
        $room_id = $room['room_id'];
        // 2. Calculate price (simplified: base price * 1 night for this example)
        $price_res = $conn->query("SELECT base_price FROM room_types WHERE room_type_id = $room_type_id");
        $base_price = $price_res->fetch_assoc()['base_price'];

        // 3. Insert into bookings table
        $sql = "INSERT INTO bookings (room_id, customer_id, check_in_date, check_out_date, price, booking_status) 
                VALUES (?, ?, ?, ?, ?, 'confirmed')";
        $insert = $conn->prepare($sql);
        $insert->bind_param("iissd", $room_id, $customer_id, $check_in, $check_out, $base_price);
        
        if ($insert->execute()) {
            header("Location: ../client/clientdashboard.php?booked=1");
        } else {
            echo "Booking Error: " . $conn->error;
        }
    } else {
        echo "No rooms of this type available.";
    }
}
?>