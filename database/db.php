<?php
// Set global timezone to prevent booking discrepancies
date_default_timezone_set('Europe/London');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hotel_management_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // In production, log this internally and show a generic message to the user
    error_log("Connection Failed: " . $conn->connect_error);
    die("A system error occurred. Please try again later.");
}

$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Flash Message Helper
 */
function set_flash($message, $type = 'info') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function display_flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $colors = [
            'success' => 'bg-green-100 text-green-700 border-green-400',
            'error'   => 'bg-red-100 text-red-700 border-red-400',
            'info'    => 'bg-amber-100 text-amber-700 border-amber-400'
        ];
        $class = $colors[$f['type']] ?? $colors['info'];
        echo "<div class='mb-6 p-4 rounded-lg border $class text-center font-semibold'>{$f['message']}</div>";
    }
}

/**
 * CSRF Protection Helpers
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>