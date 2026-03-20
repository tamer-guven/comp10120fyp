<?php

//session_start();
require_once '../database/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// gets given a task, looks at all the cleaners, sorts them by availability, assigns the task.
// new requests should automatically be issued when a room is checked out of.
// Should weight busyness based on length of task.
// Make a lookup table for types of cleaning jobs.

function assign_task($time_obj, $duration, $time_tolerance=30){
    $order = get_task_count();
    $length = count($order);
    //$time_tolerance = 30; //scheduled cleaning can happen within 30 minutes of original time.

    for ($i=0; $i<$length; $i++){
        $cur_staff = $order[$i]['staff_id'];

        //see if there is free time in the next 30 minutes
        for ($j=0; $j<=$time_tolerance; $j+=10){
            $check_time_obj = $time_obj->modify("+{$j} minutes");

            $conflicts = is_cleaner_free($cur_staff, $check_time_obj, $duration);
            if ($conflicts == 0){
                $check_time_str = $check_time_obj->format('Y-m-d H:i:s');
                return ['staff_id'=>$cur_staff, 'scheduled time'=>$check_time_str];
            }            
        }   
    }
    return null;
}

function get_task_count(){
    $sql = "SELECT s.staff_id, COALESCE(SUM(h.estimated_duration), 0) AS task_weight
            FROM staff s
            LEFT JOIN housekeeping_requests h ON s.staff_id = h.staff_id AND h.status IN ('scheduled', 'assigned', 'in_progress')
            WHERE lower(s.role) = 'cleaner'
            GROUP BY s.staff_id
            ORDER BY task_weight ASC";

    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $counts = [];
    while ($row = $result->fetch_assoc()){
        $counts[] = $row;
    }
    return $counts;
}

function is_cleaner_free($staff_id, $start_time_obj, $duration) {
    /*
    $staff_id: int
    $start_time_obj: DateTimeImmutable
    $duration: int
    */
    global $conn;

    // Calculate end time

    //$start_time_obj = new DateTime($start_time);
    //$end_time_obj = clone $start_time_obj;
    $end_time_obj =  $start_time_obj->modify("+{$duration} minutes");
    $start_time= $start_time_obj->format('Y-m-d H:i:s');
    $end_time= $end_time_obj->format('Y-m-d H:i:s');

    $sql = "
        SELECT COUNT(*) AS conflicts
        FROM housekeeping_requests
        WHERE staff_id = ?
          AND status IN ('assigned', 'in_progress')
          AND scheduled_datetime < ?
          AND DATE_ADD(scheduled_datetime, INTERVAL estimated_duration MINUTE) > ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iss",
        $staff_id,
        $end_time,
        $start_time
    );

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['conflicts'];
}

function update_hk($staff_id, $request_id){
    $sql = "UPDATE `housekeeping_requests` SET `staff_id` = ?, `status` = 'assigned' WHERE `housekeeping_requests`.`request_id` = ?;";
    global $conn;
    $stmt = $conn->prepare($sql); 
    $stmt->bind_param("ii", $staff_id, $request_id); $stmt->execute(); 
}

function get_task_duration($task): int{
    if ($task === "Full Cleaning")
        return 30;
    elseif ($task === "Towel & Amenity Refresh")
        return 5;
    elseif ($task === "Checkout Clean")
        return 45;
    else
        return 30;
}

function insert_into_hk($staff_id, $room_id, $service_type, $time, $duration, $notes, $status){
    //insert into housekeeping requests table
    $sql = "INSERT INTO housekeeping_requests
    (staff_id, room_id, service_type, scheduled_datetime, estimated_duration, notes, status)
    VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iississ", $staff_id, $room_id, $service_type, $time, $duration, $notes, $status);

    $stmt->execute();
    $stmt->close();
}

function assign_and_insert($room_id, $service_type, $time_obj, $estimated_duration, $hk_notes){

    $assigned = assign_task($time_obj, $estimated_duration);

    if($assigned){
        $status = 'assigned';
        $staff_id = $assigned['staff_id'];
        $scheduled_time = $assigned['scheduled time'];
    }
    else{
        $status = 'scheduled';
        $staff_id = null;
        $scheduled_time = $time_obj->format('Y-m-d H:i:s');
    }

    // insert into db
    insert_into_hk($staff_id, $room_id, $service_type, $scheduled_time, $estimated_duration, $hk_notes, $status);
}

function handle_checkout_hk($room_id){
    $timezone = new DateTimeZone("Europe/London");
    $now_obj = new DateTimeImmutable('now', $timezone);
    //$now_string = date_format($now, "Y-m-d H:i:s");

    assign_and_insert($room_id, 'Checkout Clean', $now_obj, 45, null);
}