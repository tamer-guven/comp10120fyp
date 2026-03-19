<?php
// ENABLE ERROR REPORTING - This turns off the generic 500 error 
// and shows the actual PHP error message.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure the database exists before selecting
if (!$conn->select_db("hotel_management_db")) {
    die("Error: Database 'hotel_management_db' does not exist. Please run create_tables.php first.");
}

echo "Starting data insertion...<br>";

// 1. Clear existing data safely
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$tables = ['services_customers', 'housekeeping_requests', 'bookings', 'rooms', 'room_types', 'staff', 'customers', 'services'];
foreach ($tables as $table) {
    if (!$conn->query("TRUNCATE TABLE $table")) {
        echo "Warning: Could not truncate $table: " . $conn->error . "<br>";
    }
}
$conn->query("SET FOREIGN_KEY_CHECKS = 1");
echo "Database cleared...<br>";

// 2. Insert Room Types
$conn->query("INSERT INTO room_types (type, base_price, max_guests) VALUES ('Single Standard', 50.00, 1)");
$single_id = $conn->insert_id;

$conn->query("INSERT INTO room_types (type, base_price, max_guests) VALUES ('Deluxe Suite', 150.00, 2)");
$deluxe_id = $conn->insert_id;

// 3. Insert Rooms
$conn->query("INSERT INTO rooms (room_number, room_type_id, last_cleaned) VALUES (101, $single_id, CURDATE())");
$conn->query("INSERT INTO rooms (room_number, room_type_id, last_cleaned) VALUES (102, $single_id, CURDATE())");
$conn->query("INSERT INTO rooms (room_number, room_type_id, last_cleaned) VALUES (201, $deluxe_id, CURDATE())");

// 4. Insert Staff
$pass = password_hash('password123', PASSWORD_DEFAULT);
$conn->query("INSERT INTO staff (first_name, last_name, role, phone_number, email, password_hash) VALUES ('Alice', 'Manager', 'manager', '0123456789', 'manager@beehotel.com', '$pass')");
$conn->query("INSERT INTO staff (first_name, last_name, role, phone_number, email, password_hash) VALUES ('Bob', 'Cleaner', 'cleaner', '0987654321', 'cleaner@beehotel.com', '$pass')");

// 5. Insert Customers
$conn->query("INSERT INTO customers (first_name, last_name, user_address, date_of_birth, phone_number, email, password_hash) VALUES ('John', 'Doe', '123 Bee Lane', '1990-01-01', '07712345678', 'john@example.com', '$pass')");
$john_id = $conn->insert_id;

$conn->query("INSERT INTO customers (first_name, last_name, user_address, date_of_birth, phone_number, email, password_hash) VALUES ('Jane', 'Smith', '456 Honey St', '1992-05-15', '07787654321', 'jane@example.com', '$pass')");
$jane_id = $conn->insert_id;

// 6. Insert Services
$conn->query("INSERT INTO services (name, description, activity_date, activity_time, cost, is_available) VALUES ('Morning Yoga', 'Relaxing yoga', '2026-03-20', '08:00:00', 15.00, 1)");

// 7. Insert Overlapping Booking for Testing
$sql = "INSERT INTO bookings (room_type_id, customer_id, check_in_date, check_out_date, price, booking_status) 
        VALUES ($deluxe_id, $john_id, '2026-03-20', '2026-03-25', 750.00, 'booked')";

if ($conn->query($sql)) {
    echo "Sample data inserted successfully!<br>";
} else {
    echo "Final insert failed: " . $conn->error . "<br>";
}

$conn->close();
?>