<?php

session_start();
require_once '../database/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// gets given a task, looks at all the cleaners, sorts them by availability, assigns the task.
// new requests should automatically be issued when a room is checked out of.
// Should weight busyness based on length of task.
// Make a lookup table for types of cleaning jobs.

function assign_task($task_record){
    $order = get_task_count();
    $length = count($order);
    for ($i=0; $i<$length; $i++){
        $cur_staff = $order[$i]['staff_id'];
        if (is_cleaner_free($cur_staff, $task_record))
            update_hk($cur_staff,$task_record['request_id']);
            return $cur_staff;
    }
    return null;
}

function get_task_count(){
    $sql = "SELECT s.staff_id, count(h.request_id) AS task_count 
            FROM staff s
            LEFT JOIN housekeeping_requests h ON s.staff_id = h.staff_id
            WHERE lower(s.role) = 'cleaner'
            GROUP BY s.staff_id
            ORDER BY task_count ASC";

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

function is_cleaner_free($staff_id, $task){
    //todo
    return true;
}

/*
function get_all_cleaners(){
    $sql = "SELECT 'staff_id' FROM staff WHERE lower(role) = 'cleaner'";

    global $conn;
    $stmt = $conn->prepare($sql); 
    $stmt->bind_param("i", $staff_id); $stmt->execute(); 
    $result = $stmt->get_result(); 
    return $result->fetch_assoc();
}

function get_record_by_ID($staff_id){
    $sql = "SELECT * FROM staff WHERE staff_id = ?";

    global $conn;
    $stmt = $conn->prepare($sql); 
    $stmt->bind_param("i", $staff_id); $stmt->execute(); 
    $result = $stmt->get_result(); 
    return $result->fetch_assoc();
}
*/

function update_hk($staff_id, $request_id){
    $sql = "UPDATE `housekeeping_requests` SET `staff_id` = ? WHERE `housekeeping_requests`.`request_id` = ?;";
    global $conn;
    $stmt = $conn->prepare($sql); 
    $stmt->bind_param("ii", $staff_id, $request_id); $stmt->execute(); 
}


print_r($i);
//echo assign_task(['request_id'=>3]);