<?php
include "db.php";

function check_created($name, $conn, $sql){
  if ($conn->query($sql)) {
    echo "$name created successfully<br>";
  } else {
    echo "Error creating $name: " . $conn->error;
  }
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS hotel_management_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci"; //case insensitive

check_created("hotel_management_db", $conn, $sql);

$conn->select_db("hotel_management_db");

$conn->query("DROP TABLE IF EXISTS services_customers");
$conn->query("DROP TABLE IF EXISTS housekeeping_requests");
$conn->query("DROP TABLE IF EXISTS bookings");
$conn->query("DROP TABLE IF EXISTS rooms");
$conn->query("DROP TABLE IF EXISTS room_types");
$conn->query("DROP TABLE IF EXISTS staff");
$conn->query("DROP TABLE IF EXISTS customers");
$conn->query("DROP TABLE IF EXISTS services");


$sql = "CREATE TABLE IF NOT EXISTS customers(
  customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(255) NOT NULL,
  last_name VARCHAR(255) NOT NULL,
  user_address VARCHAR(255) NOT NULL,
  date_of_birth DATE NOT NULL,
  phone_number VARCHAR(20) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

check_created("customers", $conn, $sql);

$sql = "CREATE TABLE IF NOT EXISTS room_types(
  room_type_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(255) NOT NULL,
  base_price DECIMAL(10,2) NOT NULL,
  max_guests INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

check_created("room_types", $conn, $sql);

$sql = "CREATE TABLE IF NOT EXISTS rooms(
  room_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_number INT NOT NULL UNIQUE,
  room_type_id INT UNSIGNED NOT NULL,
  last_cleaned DATE NOT NULL,
  CONSTRAINT fk_room_type 
    FOREIGN KEY (room_type_id) REFERENCES room_types(room_type_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

check_created("rooms", $conn, $sql);

$sql = "CREATE TABLE IF NOT EXISTS bookings(
  booking_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id INT UNSIGNED DEFAULT NULL,
  room_type_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  check_in_date DATE NOT NULL,
  booking_status ENUM('booked', 'checked_in', 'checked_out') NOT NULL DEFAULT 'booked',
  check_out_date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  actual_check_in DATETIME DEFAULT NULL,
  CONSTRAINT fk_booking_room
    FOREIGN KEY (room_id) REFERENCES rooms(room_id)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT fk_booking_room_type
    FOREIGN KEY (room_type_id) REFERENCES room_types(room_type_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT fk_bookings_customer
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

check_created("bookings", $conn, $sql);

$sql = "CREATE TABLE IF NOT EXISTS staff(
  staff_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(255) NOT NULL,
  last_name VARCHAR(255) NOT NULL,
  role VARCHAR(40) NOT NULL,
  phone_number VARCHAR(20) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

check_created("staff", $conn, $sql);

$sql = "CREATE TABLE IF NOT EXISTS services(
  service_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  description TEXT,
  activity_date DATE DEFAULT NULL,
  activity_time TIME DEFAULT NULL,
  cost DECIMAL(10,2),
  is_available BOOLEAN
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

check_created("services", $conn, $sql);

$sql = "CREATE TABLE IF NOT EXISTS services_customers(
  services_customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  CONSTRAINT fk_service
    FOREIGN KEY (service_id) REFERENCES services(service_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT fk_sc_customer
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

check_created("services_customers", $conn, $sql);

$sql = "CREATE TABLE IF NOT EXISTS housekeeping_requests(
  request_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  staff_id INT UNSIGNED DEFAULT NULL,
  room_id INT UNSIGNED NOT NULL,
  service_type VARCHAR(255) NOT NULL,
  scheduled_datetime DATETIME DEFAULT NULL,
  estimated_duration INT NOT NULL,
  notes TEXT,
  status ENUM('scheduled','assigned','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME DEFAULT NULL,
  CONSTRAINT fk_hk_cleaner
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT fk_hk_room
    FOREIGN KEY (room_id) REFERENCES rooms(room_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

check_created("housekeeping_requests", $conn, $sql);

$conn->close();
?>