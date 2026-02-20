<?php
require_once "db.php";

$conn->select_db("hotel_management_db");

echo "<h2>Inserting Sample Data...</h2>";

// Sample customer data
$customers = [
    [
        'id' => 1,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address' => '123 Main Street',
        'dob' => '1990-05-15',
        'phone' => '+1234567890',
        'email' => 'b@example.com',
        'password' => 'password123'
    ],
    [
        'id' => 2,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address' => '456 Oak Avenue',
        'dob' => '1988-03-22',
        'phone' => '+1234567891',
        'email' => 'a@example.com',
        'password' => 'password123'
    ]
];

// 1 room type
$room_types = [
    ['id' => 1, 'type' => 'Deluxe Suite', 'price' => 150.00, 'guests' => 2]
];

// 2 rooms of the same type
$rooms = [
    ['id' => 1, 'number' => 201, 'type_id' => 1],
    ['id' => 2, 'number' => 202, 'type_id' => 1]
];

echo "<h3>Creating Customers...</h3>";
foreach ($customers as $customer) {
    $checkCustomer = "SELECT customer_id FROM customers WHERE customer_id = " . $customer['id'] . " LIMIT 1";
    $result = $conn->query($checkCustomer);

    if ($result->num_rows == 0) {
        $password_hash = password_hash($customer['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO customers (customer_id, first_name, last_name, user_address, date_of_birth, phone_number, email, password_hash) 
                VALUES (" . $customer['id'] . ", '" . $customer['first_name'] . "', '" . $customer['last_name'] . "', '" . $customer['address'] . "', '" . $customer['dob'] . "', '" . $customer['phone'] . "', '" . $customer['email'] . "', '$password_hash')";
        if ($conn->query($sql)) {
            echo "✓ Customer created (ID: " . $customer['id'] . " - " . $customer['first_name'] . " " . $customer['last_name'] . ")<br>";
        } else {
            echo "✗ Error creating customer: " . $conn->error . "<br>";
        }
    } else {
        echo "✓ Customer already exists (ID: " . $customer['id'] . ")<br>";
    }
}

echo "<h3>Creating Room Types...</h3>";
foreach ($room_types as $room_type) {
    $checkRoomType = "SELECT room_type_id FROM room_types WHERE room_type_id = " . $room_type['id'] . " LIMIT 1";
    $result = $conn->query($checkRoomType);

    if ($result->num_rows == 0) {
        $sql = "INSERT INTO room_types (room_type_id, type, base_price, max_guests) 
                VALUES (" . $room_type['id'] . ", '" . $room_type['type'] . "', " . $room_type['price'] . ", " . $room_type['guests'] . ")";
        if ($conn->query($sql)) {
            echo "✓ Room type created (ID: " . $room_type['id'] . " - " . $room_type['type'] . ")<br>";
        } else {
            echo "✗ Error creating room type: " . $conn->error . "<br>";
        }
    } else {
        echo "✓ Room type already exists (ID: " . $room_type['id'] . ")<br>";
    }
}

echo "<h3>Creating Rooms...</h3>";
foreach ($rooms as $room) {
    $checkRoom = "SELECT room_id FROM rooms WHERE room_id = " . $room['id'] . " LIMIT 1";
    $result = $conn->query($checkRoom);

    if ($result->num_rows == 0) {
        $sql = "INSERT INTO rooms (room_id, room_number, room_type_id, last_cleaned) 
                VALUES (" . $room['id'] . ", " . $room['number'] . ", " . $room['type_id'] . ", CURDATE())";
        if ($conn->query($sql)) {
            echo "✓ Room created (ID: " . $room['id'] . ", Room #" . $room['number'] . ")<br>";
        } else {
            echo "✗ Error creating room: " . $conn->error . "<br>";
        }
    } else {
        echo "✓ Room already exists (ID: " . $room['id'] . ")<br>";
    }
}

echo "<h3>Creating Bookings...</h3>";
// Both customers book the same room type with overlapping dates
// room_id=0 means not checked in yet, actual room assigned at check-in
$bookings = [
    [
        'customer_id' => 1,
        'room_id' => 0,
        'check_in' => date('Y-m-d'),
        'check_out' => date('Y-m-d', strtotime('+3 days')),
        'status' => 'booked',
        'price' => 450.00
    ],
    [
        'customer_id' => 2,
        'room_id' => 0,
        'check_in' => date('Y-m-d'),
        'check_out' => date('Y-m-d', strtotime('+3 days')),
        'status' => 'booked',
        'price' => 450.00
    ]
];

foreach ($bookings as $booking) {
    $checkBooking = "SELECT booking_id FROM bookings WHERE customer_id = " . $booking['customer_id'] . " LIMIT 1";
    $result = $conn->query($checkBooking);

    if ($result->num_rows == 0) {
        $sql = "INSERT INTO bookings (room_id, room_type_id, customer_id, check_in_date, check_out_date, booking_status, price) 
                VALUES (" . $booking['room_id'] . ", 1, " . $booking['customer_id'] . ", '" . $booking['check_in'] . "', '" . $booking['check_out'] . "', '" . $booking['status'] . "', " . $booking['price'] . ")";
        if ($conn->query($sql)) {
            $booking_id = $conn->insert_id;
            echo "✓ Booking created (ID: $booking_id) for customer ID " . $booking['customer_id'] . "<br>";
            echo "  - Check-in: " . $booking['check_in'] . "<br>";
            echo "  - Check-out: " . $booking['check_out'] . "<br>";
            echo "  - Status: " . $booking['status'] . "<br>";
            echo "  - Price: $" . number_format($booking['price'], 2) . "<br><br>";
        } else {
            echo "✗ Error creating booking: " . $conn->error . "<br>";
        }
    } else {
        echo "✓ Booking already exists for customer ID " . $booking['customer_id'] . "<br>";
    }
}

$conn->close();
echo "<br><strong style='color: green; font-size: 16px;'>✓ Sample data insertion complete!</strong>";
?>
