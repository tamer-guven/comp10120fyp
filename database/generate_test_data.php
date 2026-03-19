<?php
require_once "db.php";
$conn->select_db("hotel_management_db");

echo "<h2>Generating Comprehensive Test Data...</h2>";

// 1. Create Staff Members
$staff_members = [
    ['first' => 'Alice', 'last' => 'Admin', 'role' => 'manager', 'phone' => '1111111111', 'email' => 'manager@bee.com', 'pass' => 'manager123'],
    ['first' => 'Bob', 'last' => 'Reception', 'role' => 'receptionist', 'phone' => '2222222222', 'email' => 'reception@bee.com', 'pass' => 'reception123'],
    ['first' => 'Charlie', 'last' => 'Cleaner', 'role' => 'cleaner', 'phone' => '3333333333', 'email' => 'cleaner@bee.com', 'pass' => 'cleaner123']
];

echo "<h3>Creating Staff...</h3>";
foreach ($staff_members as $s) {
    $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE email = ?");
    $stmt->bind_param("s", $s['email']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        $hash = password_hash($s['pass'], PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO staff (first_name, last_name, role, phone_number, email, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->bind_param("ssssss", $s['first'], $s['last'], $s['role'], $s['phone'], $s['email'], $hash);
        if ($insert->execute()) echo "✓ Staff created: {$s['email']} (Role: {$s['role']})<br>";
    } else {
        echo "✓ Staff already exists: {$s['email']}<br>";
    }
}

// 2. Create Customers
$customers = [
    ['first' => 'John', 'last' => 'Doe', 'address' => '123 Main St', 'dob' => '1990-01-01', 'phone' => '4444444444', 'email' => 'guest1@example.com', 'pass' => 'guest123'],
    ['first' => 'Jane', 'last' => 'Smith', 'address' => '456 Oak St', 'dob' => '1985-05-15', 'phone' => '5555555555', 'email' => 'guest2@example.com', 'pass' => 'guest123']
];

echo "<h3>Creating Customers...</h3>";
foreach ($customers as $c) {
    $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
    $stmt->bind_param("s", $c['email']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        $hash = password_hash($c['pass'], PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO customers (first_name, last_name, user_address, date_of_birth, phone_number, email, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert->bind_param("sssssss", $c['first'], $c['last'], $c['address'], $c['dob'], $c['phone'], $c['email'], $hash);
        if ($insert->execute()) echo "✓ Customer created: {$c['email']}<br>";
    } else {
        echo "✓ Customer already exists: {$c['email']}<br>";
    }
}

// 3. Create Services/Activities
$services = [
    ['name' => 'Spa Massage', 'cost' => 75.00, 'avail' => 1],
    ['name' => 'City Tour', 'cost' => 45.00, 'avail' => 1]
];

echo "<h3>Creating Services...</h3>";
foreach ($services as $srv) {
    $stmt = $conn->prepare("SELECT service_id FROM services WHERE name = ?");
    $stmt->bind_param("s", $srv['name']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        $insert = $conn->prepare("INSERT INTO services (name, cost, is_available) VALUES (?, ?, ?)");
        $insert->bind_param("sdi", $srv['name'], $srv['cost'], $srv['avail']);
        if ($insert->execute()) echo "✓ Service created: {$srv['name']}<br>";
    }
}

echo "<br><strong style='color: green; font-size: 16px;'>✓ Test data generation complete!</strong>";
?>