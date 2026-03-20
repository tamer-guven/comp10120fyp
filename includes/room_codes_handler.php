<?php
require_once '../database/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function generate_pin($conn){
    $count = 1;
    while ($count > 0){
        $pin = random_int(100000, 999999);
 
        $stmt = $conn->prepare("SELECT COUNT(*) FROM room_access_codes WHERE pin = ?");
        $stmt->bind_param("i", $pin);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
    }
    
    return strval($pin);
}

function is_pin_time_valid($valid_from_str, $valid_to_str){
    $timezone = new DateTimeZone("Europe/London");
    $now_obj = new DateTimeImmutable('now', $timezone);
    $valid_from = new DateTimeImmutable($valid_from_str);
    $valid_to = new DateTimeImmutable($valid_to_str);

    if ($valid_from <= $now_obj && $valid_to > $now_obj)
        return true;
    else
        return false;
}

function update_is_pin_valid($conn, $booking_id){
    $stmt = $conn->prepare("SELECT pin, valid_from, valid_to
        FROM room_access_codes
        WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $info = $result->fetch_assoc();
    $stmt->close();

    if (!$info) {
        throw new Exception("No access code found");
    }

    if ($info['pin'] && is_pin_time_valid($info['valid_from'], $info['valid_to'])){
        return true;
    }else{
        if ($info['pin']){
            update_pin($conn, $booking_id, null);
        }
        return false;
    }
}

function add_pin_to_table($conn, $booking_id){
    if (get_pin($conn, $booking_id)){
        throw new Exception("Pin already exists");
    }
    $stmt = $conn->prepare("SELECT room_id, check_in_date, check_out_date
        FROM bookings
        WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $info = $result->fetch_assoc();
    $stmt->close();

    if (!$info) {
        throw new Exception("Booking not found");
    }

    $check_out = new DateTimeImmutable($info['check_out_date']);
    $check_out = $check_out->modify("+1 day");
    $check_out_str = $check_out->format('Y-m-d H:i:s');

    $pin = is_pin_time_valid($info['check_in_date'], $check_out_str) ? generate_pin($conn) : null;

    $stmt = $conn->prepare("INSERT INTO room_access_codes 
    (booking_id, room_id, pin, valid_from, valid_to)
    VALUES (?, ?, ?, ?, ?)");

    $stmt->bind_param("iisss", $booking_id, $info['room_id'], $pin, $info['check_in_date'], $check_out_str);

    $stmt->execute();
    $stmt->close();

    return $pin;
}

function update_pin($conn, $booking_id, $new_pin){
    $stmt = $conn->prepare("UPDATE room_access_codes SET pin = ? WHERE booking_id = ?");
    $stmt->bind_param("si", $new_pin, $booking_id);
    $stmt->execute();
    $stmt->close();
}

function get_pin($conn, $booking_id){
    $pin = null;
    $stmt = $conn->prepare("SELECT pin
        FROM room_access_codes
        WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($pin);
    $stmt->fetch();
    $stmt->free_result();
    $stmt->close();

    if ($pin)
        return $pin;
    else
        return null;
}