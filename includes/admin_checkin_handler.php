<?php
session_start();
require_once '../database/db.php';
require_once 'housekeeping_scheduler.php';

$staff_id = $_SESSION['staff_id'] ?? null;
$conn->select_db("hotel_management_db");

// Handle check-in/check-out API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $booking_id = isset($_POST['booking_id']) && is_numeric($_POST['booking_id']) ? (int)$_POST['booking_id'] : null;
    $action = isset($_POST['action']) ? $_POST['action'] : null;
    
    if (!$booking_id || !$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing booking_id or action.']);
        exit;
    }
    
    if (!$staff_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied. Staff login required.']);
        exit;
    }
    
    // Fetch booking
    $stmt = $conn->prepare("SELECT booking_id, booking_status, room_id FROM bookings WHERE booking_id = ? LIMIT 1");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found.']);
        exit;
    }
    
    // Handle check-in
    if ($action === 'checkin') {
        if ($booking['booking_status'] !== 'booked') {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Booking must be in booked status to check in.']);
            exit;
        }
        
        $bookingDetails = $conn->prepare("
            SELECT b.booking_id, b.room_id, b.check_in_date, b.check_out_date, b.room_type_id 
            FROM bookings b 
            WHERE b.booking_id = ? LIMIT 1
        ");
        
        if (!$bookingDetails) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        $bookingDetails->bind_param("i", $booking_id);
        $bookingDetails->execute();
        $bookingResult = $bookingDetails->get_result();
        $bookingData = $bookingResult->fetch_assoc();
        $bookingDetails->close();
        
        $room_type_id = $bookingData['room_type_id'];
        $check_in = $bookingData['check_in_date'];
        $check_out = $bookingData['check_out_date'];
        
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
        
        if (!$availableRoom) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        $availableRoom->bind_param("iss", $room_type_id, $check_out, $check_in);
        $availableRoom->execute();
        $roomResult = $availableRoom->get_result();
        $room = $roomResult->fetch_assoc();
        $availableRoom->close();
        
        if (!$room) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'The booked room type is not currently available. Please contact management or find an alternative room type.']);
            exit;
        }
        
        $update = $conn->prepare("UPDATE bookings SET room_id = ?, booking_status='checked_in', actual_check_in = NOW() WHERE booking_id = ?");
        $update->bind_param("ii", $room['room_id'], $booking_id);
        
        if ($update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Checked in successfully.', 'room_number' => $room['room_number']]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        $update->close();
    }
    // Handle check-out
    elseif ($action === 'checkout') {
        if ($booking['booking_status'] !== 'checked_in') {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Booking must be checked in to check out.']);
            exit;
        }
        
        $room_id = $booking['room_id'];
        $update = $conn->prepare("UPDATE bookings SET booking_status='checked_out' WHERE booking_id = ?");
        $update->bind_param("i", $booking_id);
        
        if ($update->execute()) {
            // Trigger housekeeping assignment
            if (!empty($room_id)) {
                ob_start();
                handle_checkout_hk($room_id);
                ob_end_clean();
            }
            echo json_encode(['success' => true, 'message' => 'Checked out successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        $update->close();
    }
    else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
    
    $conn->close();
    exit;
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}
?>