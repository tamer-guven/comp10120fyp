<?php
header('Content-Type: application/json');
session_start();
require_once '../database/db.php';

$conn->select_db("hotel_management_db");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_id  = isset($_POST['service_id']) ? (int)$_POST['service_id'] : null;
    $customer_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if (!$service_id || !$customer_id) {
        echo json_encode(['success' => false, 'message' => 'Missing service_id or customer_id']);
        exit();
    }

    // Insert booking
    $sql = "INSERT INTO services_customers (service_id, customer_id) 
            VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("ii", $service_id, $customer_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity booked successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Execute error: ' . $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method: ' . $_SERVER["REQUEST_METHOD"]]);
}
?>
