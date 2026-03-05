<?php
session_start();
require_once '../database/db.php';
require_once 'housekeeping_scheduler.php';

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_type = $_POST['serviceType'] ?? null;
    $time = $_POST['timeSlot'] ?? null;
    $time = time_str_to_sql_datetime($time);
    $hk_notes = $_POST['notes'] ?? null;
    $estimated_duration = get_task_duration($service_type);

    //verify there is all the right data.
    if (!$service_type || !$time){
        echo "missing required fields";
        exit;
    }
    if (!isset($_SESSION['current_booking_details'])){
        echo "no current booking found";
        exit;
    }

    $booking_id = $_SESSION['current_booking_details']['booking_id'];
    $room_id = $_SESSION['current_booking_details']['room_id'];

    if ($assigned_staff_id = assign_task($time, $estimated_duration)){
        $status = 'assigned';
    }
    else
        $status = 'scheduled';
    
    // insert into db
    insert_into_hk($assigned_staff_id, $room_id, $service_type, $time, $estimated_duration, $hk_notes, $status);

    $conn->close();
    echo 'Success';
} else {
    http_response_code(405);
    echo "Invalid request";
}
exit;