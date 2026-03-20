<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

if (!$conn->select_db("hotel_management_db")) {
    die("Error: Database 'hotel_management_db' does not exist. Run create_tables.php first.");
}

// 1. Clear existing data safely by disabling foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$tables = ['services_customers', 'housekeeping_requests', 'bookings', 'rooms', 'room_types', 'staff', 'customers', 'services'];
foreach ($tables as $table) {
    $conn->query("TRUNCATE TABLE $table");
}
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// 2. Insert Room Types
$conn->query("INSERT INTO room_types (type, base_price, max_guests) VALUES ('Single Standard', 50.00, 1)");
$single_id = $conn->insert_id;

$conn->query("INSERT INTO room_types (type, base_price, max_guests) VALUES ('Deluxe Suite', 150.00, 2)");
$deluxe_id = $conn->insert_id;

// 3. Insert Rooms
$conn->query("INSERT INTO rooms (room_number, room_type_id, last_cleaned) VALUES (101, $single_id, CURDATE())");
$conn->query("INSERT INTO rooms (room_number, room_type_id, last_cleaned) VALUES (102, $single_id, CURDATE())");
$conn->query("INSERT INTO rooms (room_number, room_type_id, last_cleaned) VALUES (201, $deluxe_id, CURDATE())");

// 4. Insert Staff (Password: password123)
$staff_pass = password_hash('password123', PASSWORD_DEFAULT); 
$conn->query("INSERT INTO staff (first_name, last_name, role, phone_number, email, password_hash) VALUES 
    ('Admin', 'User', 'manager', '0161111222', 'admin@example.com', '$staff_pass')");
$conn->query("INSERT INTO staff (first_name, last_name, role, phone_number, email, password_hash) VALUES 
    ('Reception', 'Staff', 'receptionist', '0161333444', 'staff@example.com', '$staff_pass')");
$conn->query("INSERT INTO staff (first_name, last_name, role, phone_number, email, password_hash) VALUES 
    ('Charlie', 'Cleaner', 'cleaner', '0161555666', 'cleaner@example.com', '$staff_pass')");

// 5. Insert Customers (Password: password123)
$cust_pass = password_hash('password123', PASSWORD_DEFAULT);
$conn->query("INSERT INTO customers (first_name, last_name, user_address, date_of_birth, phone_number, email, password_hash) VALUES 
    ('Customer', 'A', '101 Piccadilly, Manchester', '1990-01-01', '07700000001', 'a@example.com', '$cust_pass')");
$cust_a_id = $conn->insert_id;

$conn->query("INSERT INTO customers (first_name, last_name, user_address, date_of_birth, phone_number, email, password_hash) VALUES 
    ('Customer', 'B', '202 Deansgate, Manchester', '1992-05-15', '07700000002', 'b@example.com', '$cust_pass')");

// 6. Insert Services/Activities
$conn->query("INSERT INTO services (name, description, activity_date, activity_time, cost, is_available) VALUES 
    ('Hotel Gym', '24/7 Access to fitness center', CURDATE(), '00:00:00', 0.00, 1)");
$conn->query("INSERT INTO services (name, description, activity_date, activity_time, cost, is_available) VALUES 
    ('Spa Treatment', '1 Hour full body massage', '2026-03-25', '14:00:00', 55.00, 1)");

// 7. Insert an existing booking to block Room 201 for test cases
$sql = "INSERT INTO bookings (room_type_id, customer_id, check_in_date, check_out_date, price, booking_status) 
        VALUES ($deluxe_id, $cust_a_id, '2026-03-20', '2026-03-25', 750.00, 'booked')";

if ($conn->query($sql)) {
    echo "<h1>Test Data Successfully Added</h1>";
    echo "<h3>Added Data Summary:</h3>";
    echo "<ul>";
    echo "<li><b>Room Types:</b> Single Standard (£50), Deluxe Suite (£150)</li>";
    echo "<li><b>Rooms:</b> 101, 102 (Single), 201 (Deluxe)</li>";
    echo "<li><b>Staff (Pass: password123):</b> admin@example.com (Manager), staff@example.com (Reception), cleaner@example.com (Cleaner)</li>";
    echo "<li><b>Customers (Pass: password123):</b> a@example.com, b@example.com</li>";
    echo "<li><b>Services:</b> Hotel Gym, Spa Treatment</li>";
    echo "<li><b>Active Bookings:</b> Customer A (Deluxe Suite, March 20-25)</li>";
    echo "</ul>";
} else {
    echo "Error inserting blocker booking: " . $conn->error;
}

$conn->close();
?>