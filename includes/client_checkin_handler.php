<?php
header('Content-Type: application/json');
session_start();
require_once '../database/db.php';
require_once 'housekeeping_scheduler.php';

$conn->select_db("hotel_management_db");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$customer_id = $_SESSION['user_id'] ?? null;
if (!$customer_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

$booking_id = isset($_POST['booking_id']) && is_numeric($_POST['booking_id']) ? (int)$_POST['booking_id'] : null;
$action = isset($_POST['action']) ? $_POST['action'] : null;

if (!$booking_id || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing booking_id or action.']);
    exit;
}

// Fetch booking and verify it belongs to this customer
$stmt = $conn->prepare("SELECT booking_id, booking_status, room_type_id, room_id, check_in_date, check_out_date FROM bookings WHERE booking_id = ? AND customer_id = ? LIMIT 1");
$stmt->bind_param("ii", $booking_id, $customer_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Booking not found.']);
    exit;
}

// Handle check-in
if ($action === 'check_in') {
    if ($booking['booking_status'] !== 'booked') {
        echo json_encode(['success' => false, 'message' => 'Booking must be in booked status to check in.']);
        exit;
    }

    $room_type_id = $booking['room_type_id'];
    $check_in = $booking['check_in_date'];
    $check_out = $booking['check_out_date'];

    // Find an available room of the same type not occupied during the stay dates
    $availableRoom = $conn->prepare("
        SELECT r.room_id, r.room_number 
        FROM rooms r 
        WHERE r.room_type_id = ? 
        AND r.room_id NOT IN (
            SELECT b.room_id 
            FROM bookings b 
            WHERE b.booking_status = 'checked_in'
            AND b.check_in_date < ? 
            AND b.check_out_date > ?
        )
        ORDER BY r.room_id
        LIMIT 1
    ");
    $availableRoom->bind_param("iss", $room_type_id, $check_out, $check_in);
    $availableRoom->execute();
    $room = $availableRoom->get_result()->fetch_assoc();
    $availableRoom->close();

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'No rooms of this type are currently available. Please contact reception.']);
        exit;
    }

    // Update booking with assigned room and check-in status
    $update = $conn->prepare("UPDATE bookings SET room_id = ?, booking_status = 'checked_in', actual_check_in = NOW() WHERE booking_id = ?");
    $update->bind_param("ii", $room['room_id'], $booking_id);

    if ($update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Checked in successfully! Room assigned: #' . $room['room_number']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    $update->close();
}
// Handle check-out
elseif ($action === 'check_out') {
    if ($booking['booking_status'] !== 'checked_in') {
        echo json_encode(['success' => false, 'message' => 'You must be checked in to check out.']);
        exit;
    }

    $room_id = $booking['room_id'];
    $update = $conn->prepare("UPDATE bookings SET booking_status = 'checked_out' WHERE booking_id = ?");
    $update->bind_param("i", $booking_id);

    if ($update->execute()) {
        // Trigger housekeeping assignment upon checkout
        if (!empty($room_id)) {
            ob_start();
            handle_checkout_hk($room_id);
            ob_end_clean();
        }
        echo json_encode(['success' => true, 'message' => 'Checked out successfully. Thank you for your stay!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    $update->close();
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}

$conn->close();
?>