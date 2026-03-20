<?php
header('Content-Type: application/json');
session_start();
require_once '../database/db.php';
require_once 'housekeeping_scheduler.php';

// Redirect if not logged in
if (!isset($_SESSION['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn->select_db("hotel_management_db");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $request_type = isset($_POST['request_type']) ? $_POST['request_type'] : null;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

    if (!$customer_id || !$request_type) {
        echo json_encode(['success' => false, 'message' => 'Missing customer_id or request_type']);
        exit();
    }
    // Map admin-facing request types to service types used in scheduler
    $service_type = null;
    if ($request_type === 'Full Cleaning') {
        $service_type = 'Full Cleaning';
    } elseif ($request_type === 'Towel & Amenity Refresh') {
        $service_type = 'Towel & Amenity Refresh';
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request type']);
        exit();
    }

    // Find the currently checked-in booking for this customer to get the room
    $booking_stmt = $conn->prepare("SELECT room_id FROM bookings WHERE customer_id = ? AND booking_status = 'checked_in' ORDER BY actual_check_in DESC LIMIT 1");
    $booking_stmt->bind_param('i', $customer_id);
    $booking_stmt->execute();
    $booking_res = $booking_stmt->get_result();
    $booking = $booking_res->fetch_assoc();
    $booking_stmt->close();

    if (!$booking || empty($booking['room_id'])) {
        echo json_encode(['success' => false, 'message' => 'No checked-in booking with an assigned room for this customer.']);
        exit();
    }

    $room_id = (int)$booking['room_id'];
    $estimated_duration = get_task_duration($service_type);

    // Schedule immediately (now) for admin-created request
    $timezone = new DateTimeZone("Europe/London");
    $time_obj = new DateTimeImmutable('now', $timezone);

    assign_and_insert($room_id, $service_type, $time_obj, $estimated_duration, $notes);

    echo json_encode(['success' => true, 'message' => 'Housekeeping request created and scheduled.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
