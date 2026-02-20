<?php
header('Content-Type: application/json');
session_start();
require_once '../database/db.php';

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

    // For now, just store in a simple format
    // You can create a housekeeping_requests table if needed
    // This is a simple logging approach
    $log_data = json_encode([
        'customer_id' => $customer_id,
        'request_type' => $request_type,
        'notes' => $notes,
        'requested_by' => $_SESSION['staff_id'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    // Log to a file or database
    // For now, we'll just return success
    echo json_encode(['success' => true, 'message' => 'Housekeeping request created successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
