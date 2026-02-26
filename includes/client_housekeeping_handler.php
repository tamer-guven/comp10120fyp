<?php
session_start();
require_once '../database/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function time_str_to_sql_datetime($time_str): string{
    if ($time_str){
        // input in format: 9:00 AM
        
        $timezone = new DateTimeZone("Europe/London");

        $datetime = DateTime::createFromFormat('g:i A', $time_str, $timezone);

        if (!$datetime) {
            return null; // invalid format
        }
        
        $now = new DateTime('now', $timezone);
        
        // Set date to today
        $datetime->setDate(
        (int)$now->format('Y'),
        (int)$now->format('m'),
        (int)$now->format('d')
        );

        if ($now > $datetime){
            $datetime->modify('+1 day');
        }
        
        return date_format($datetime, "Y-m-d H:i:s");
    }
    else{
        return null;
    }
}

//echo "<pre>";
//print_r($_SESSION['']);
//echo "</pre>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $serviceType = $_POST['serviceType'] ?? null;
    $time = $_POST['timeSlot'] ?? null;
    $time = time_str_to_sql_datetime($time);
    $hk_notes = $_POST['notes'] ?? null;

    //verify there is all the right data.
    if (!$serviceType || !$time){
        echo "missing required fields";
        exit;
    }
    if (!isset($_SESSION['current_booking_details'])){
        echo "no current booking found";
        exit;
    }

    $booking_id = $_SESSION['current_booking_details']['booking_id'];
    $room_id = $_SESSION['current_booking_details']['room_id'];

    //insert into housekeeping requests table
    $sql = "INSERT INTO housekeeping_requests
    (room_id, service_type, scheduled_datetime, notes, status)
    VALUES (?, ?, ?, ?, 'scheduled')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $room_id, $serviceType, $time, $hk_notes);

    if ($stmt->execute()) {
        echo "Success";
    } else {
        echo "Database error.";
    }

    $stmt->close();
    $conn->close();
}
exit;