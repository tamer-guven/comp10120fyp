<?php
session_start();
require_once __DIR__ . '/../database/db.php';
require_once '../includes/room_codes_handler.php';

// If the session variable isn't set, kick them back to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$conn->select_db("hotel_management_db");

// Fetch customer first name
$customer_stmt = $conn->prepare("SELECT first_name FROM customers WHERE customer_id = ? LIMIT 1");
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();
$customer_data = $customer_result->fetch_assoc();
$first_name = $customer_data ? $customer_data['first_name'] : '';
$customer_stmt->close();

// Fetch the current (active) booking for this customer
$stmt = $conn->prepare("SELECT b.booking_id, b.check_in_date, b.check_out_date, b.booking_status, b.actual_check_in, b.room_id, r.room_number, rt.type FROM bookings b LEFT JOIN rooms r ON b.room_id = r.room_id JOIN room_types rt ON b.room_type_id = rt.room_type_id WHERE b.customer_id = ? AND b.booking_status IN ('booked','checked_in') ORDER BY b.check_in_date ASC LIMIT 1");
    
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$current_booking = $result->fetch_assoc();

$stmt->close();

// Fetch past (checked-out) bookings for account overview, including activities within each stay window
$past_stmt = $conn->prepare("SELECT b.booking_id, b.check_in_date, b.check_out_date, b.booking_status, b.room_id, b.price, r.room_number, rt.type,
    (
        SELECT COALESCE(SUM(s.cost), 0)
        FROM services_customers sc
        JOIN services s ON sc.service_id = s.service_id
        WHERE sc.customer_id = b.customer_id
          AND s.activity_date IS NOT NULL
          AND s.activity_date BETWEEN b.check_in_date AND b.check_out_date
    ) AS activity_total
    FROM bookings b
    LEFT JOIN rooms r ON b.room_id = r.room_id
    JOIN room_types rt ON b.room_type_id = rt.room_type_id
    WHERE b.customer_id = ? AND b.booking_status = 'checked_out'
    ORDER BY b.check_out_date DESC");

$past_stmt->bind_param("i", $customer_id);
$past_stmt->execute();
$past_result = $past_stmt->get_result();
$past_bookings = [];
while ($row = $past_result->fetch_assoc()) {
    $past_bookings[] = $row;
}
$past_stmt->close();

//Fetch any booked activities during the current stay
$current_services = [];
if ($current_booking) {
    $get_services = $conn->prepare("SELECT s.name, s.activity_date, s.activity_time, s.description
        FROM services s
        JOIN services_customers s_c ON s.service_id = s_c.service_id
        WHERE s_c.customer_id = ?
            AND s.activity_date IS NOT NULL
            AND s.activity_date BETWEEN ? AND ?
        ORDER BY s.activity_date ASC, COALESCE(s.activity_time, '23:59:59') ASC, s_c.services_customer_id ASC");

    $get_services->bind_param("iss", $customer_id, $current_booking['check_in_date'], $current_booking['check_out_date']);
    $get_services->execute();
    $result = $get_services->get_result();
    while ($row = $result->fetch_assoc()) {
        $current_services[] = $row;
    }
    $get_services->close();
}

$current_housekeeping = [];
if ($current_booking && $current_booking['booking_status'] === 'checked_in' && !empty($current_booking['room_id'])){
    $get_housekeeping = $conn->prepare("
        SELECT DISTINCT hk.service_type, hk.scheduled_datetime, hk.status
        FROM housekeeping_requests hk
        WHERE hk.room_id = ?
        AND hk.status IN ('scheduled','assigned','in_progress')
        ORDER BY hk.scheduled_datetime ASC
    ");

    $get_housekeeping->bind_param("i", $current_booking['room_id']);
    $get_housekeeping->execute();
    $result = $get_housekeeping->get_result();

    while ($row = $result->fetch_assoc()) {
        $current_housekeeping[] = $row;
    }
    $get_housekeeping->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - The Bee Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        :root { font-family: 'Inter', sans-serif; }
        .scroll-container::-webkit-scrollbar { width: 8px; }
        .scroll-container::-webkit-scrollbar-thumb { background-color: #f59e0b; border-radius: 4px; }
        .scroll-container::-webkit-scrollbar-track { background-color: #f3f4f6; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <header class="bg-white shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-wider">The <span class="text-amber-500">Bee</span> Hotel</h1>
            <nav class="space-x-4 flex items-center">
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="goToPage('clientpage.html')">
                    Home
                </button>
                <button id="logoutBtn" class="px-5 py-2 bg-gray-800 text-white rounded-lg shadow-md hover:bg-gray-900 transition duration-150 font-medium" onclick="handleLogout()">
                    Log Out
                </button>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <h2 class="text-4xl font-bold text-gray-900 mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h2>
        <p class="text-gray-600 text-lg mb-8">Manage your current stay and review past bookings below.</p>

        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] gap-8 items-start">
            <div class="space-y-6">
            <?php if ($current_booking): ?>
                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl border-t-4 border-amber-500 space-y-4">
                    <h3 class="text-3xl font-extrabold text-gray-900">Your Current Stay</h3>
                    
                    <div class="grid grid-cols-2 gap-4 text-gray-700">
                        <div>
                            <p><span class="font-bold text-gray-900">Room:</span> <span class="text-amber-600"><?php echo (!empty($current_booking['room_id'])) ? htmlspecialchars($current_booking['room_number']) : 'Not assigned yet'; ?></span> (<?php echo htmlspecialchars($current_booking['type']); ?>)</p>
                            <p><span class="font-bold text-gray-900">Check-in:</span> <?php echo date("M j, Y", strtotime($current_booking['check_in_date'])); ?></p>
                            <p class="pt-2"><span class="font-bold text-gray-900">Status:</span> <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-semibold"><?php echo ucfirst(str_replace('_', ' ', $current_booking['booking_status'])); ?></span></p>
                            <?php if ($current_booking['booking_status'] === 'checked_in'){ $pin = get_pin($conn, $current_booking['booking_id']); ?>
                            <p class="pt-2">
                                <span class="font-bold text-gray-900">Pin:</span> 
                                <span class="text-gray-700 px-3 py-1 rounded-md font-mono font-semibold text-sm cursor-pointer select-none hover:bg-gray-100 transition" 
                                    data-pin="<?php echo htmlspecialchars($pin); ?>" 
                                    onclick="togglePin(this)"
                                    title="Click to reveal">
                                    ******
                                </span>
                            </p>
                            <?php }?>

                        </div>
                        <div class="flex flex-col">
                            <p><span class="font-bold text-gray-900">Booking ID:</span> #BEE<?php echo $current_booking['booking_id']; ?></p>
                            <p><span class="font-bold text-gray-900">Check-out:</span> <?php echo date("M j, Y", strtotime($current_booking['check_out_date'])); ?></p>
                            <button class="mt-auto py-2 px-3 <?php echo ($current_booking['booking_status'] === 'checked_in') ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'; ?> text-white font-semibold rounded-lg shadow-md transition text-sm" onclick="handleCheckInOut(<?php echo $current_booking['booking_id']; ?>, '<?php echo ($current_booking['booking_status'] === 'checked_in') ? 'check_out' : 'check_in'; ?>')">
                                <?php echo ($current_booking['booking_status'] === 'checked_in') ? 'Check Out' : 'Check In'; ?>
                            </button>
                        </div>
                    </div>

                <div class="border-t pt-4 mt-4">
                    <h4 class="text-xl font-bold mb-3 text-gray-800">In-Stay Services</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        <?php if ($current_booking['booking_status'] === 'checked_in'):?>
                            <button class="py-3 px-4 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition" 
                                onclick="goToPage('clienthousekeeping.php?booking_id=<?php echo $current_booking['booking_id']; ?>')">
                                Request Housekeeping
                            </button>
                        <?php else:?>
                            <button class="py-3 px-4 bg-gray-200 text-gray-300 font-semibold rounded-lg shadow-md">
                                Request Housekeeping
                            </button>
                        <?php endif; ?>
                        
                        <button class="py-3 px-4 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition" 
                                onclick="goToPage('clientactivities.php')">
                            Book Activities
                        </button>
                        
                        <button class="py-3 px-4 bg-amber-500 text-gray-900 font-semibold rounded-lg shadow-md hover:bg-amber-600 transition sm:col-span-2" 
                                onclick="goToPage('clientexpenses.php')">
                            View Expenses
                        </button>
                    </div>

                </div>

            </div>
            <?php else: ?>
                <div class="bg-white p-12 rounded-2xl shadow-xl border-t-4 border-amber-500 text-center">
                    <div class="mb-6">
                        <svg class="mx-auto h-24 w-24 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">You have no active bookings</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">Experience luxury and impeccable service in the heart of Manchester. Ready to plan your escape?</p>
                    <a href="clientbook.php" class="inline-block bg-amber-500 px-10 py-4 rounded-xl font-bold text-gray-900 shadow-xl hover:bg-amber-600 transition transform hover:scale-105">
                        Book a Room Now
                    </a>
                </div>
            <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-1 gap-6">
                <div class="bg-gray-800 p-6 md:p-8 rounded-2xl shadow-xl space-y-4 text-white">
                    <h3 class="text-2xl font-extrabold text-amber-500">Scheduled Activities</h3>
                    <div class="scroll-container max-h-[220px] overflow-y-auto pr-2">
                    <?php if (empty($current_services) && empty($current_housekeeping)): ?>
                        <p class="text-sm text-gray-400 mb-4">No services currently booked.</p>
                    <?php else: ?>
                        <?php foreach ($current_services as $service): ?>
                            <div class="border-b border-gray-700 pb-3 mb-3">
                                <p class="font-bold"><?php echo htmlspecialchars($service['name']); ?></p>
                                <?php if (!empty($service['activity_date']) || !empty($service['activity_time'])): ?>
                                    <p class="text-sm text-gray-400">
                                        <?php if (!empty($service['activity_date'])): ?>
                                            <?php echo date('M j, Y', strtotime($service['activity_date'])); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($service['activity_time'])): ?>
                                            at <?php echo date('g:i A', strtotime($service['activity_time'])); ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($service['description'])): ?>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($service['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($current_housekeeping as $hk): ?>
                            <div class="border-b border-gray-700 pb-3 mb-3">
                                <p class="font-bold">Housekeeping: <?php echo htmlspecialchars($hk['service_type']); ?></p>
                                <p class="text-sm text-gray-400"><?php echo date('M j, Y', strtotime($hk['scheduled_datetime'])); ?></p>
                                <p class="text-sm text-gray-400"><?php echo htmlspecialchars($hk['status']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                </div>

                <div class="bg-gray-800 p-6 md:p-8 rounded-2xl shadow-xl space-y-4 text-white">
                    <h3 class="text-2xl font-extrabold text-amber-500">Account Overview</h3>
                    <div class="scroll-container max-h-[400px] overflow-y-auto pr-2">
                        <?php if (empty($past_bookings)): ?>
                            <p class="text-sm text-gray-400 mb-4">Past stay history will appear here once your current visit is completed.</p>
                        <?php else: ?>
                            <?php foreach ($past_bookings as $booking): ?>
                                <?php 
                                    $activities = isset($booking['activity_total']) ? (float)$booking['activity_total'] : 0;
                                    $total_due = (float)$booking['price'] + $activities;
                                ?>
                                <div class="border-b border-gray-700 pb-3 mb-3">
                                    <p class="font-bold">Booking #BEE<?php echo $booking['booking_id']; ?></p>
                                    <p class="text-sm text-gray-400"><?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?> - <?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?></p>
                                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($booking['type']); ?><?php echo (!empty($booking['room_id'])) ? ' (Room ' . htmlspecialchars($booking['room_number']) . ')' : ''; ?></p>
                                    <p class="text-sm text-amber-500 pt-1">Total: £<?php echo number_format($total_due, 2); ?></p>
                                    <?php if ($activities > 0): ?>
                                        <p class="text-xs text-gray-400">Includes activities: £<?php echo number_format($activities, 2); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-gray-400 pt-2 border-t border-gray-700">Contact reception for detailed invoices of past stays.</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        function goToPage(filename) {
            window.location.href = filename;
        }

        function handleLogout() {
            window.location.href = '../login.php';
        }

        function handleCheckInOut(bookingId, action) {
            const label = action === 'check_in' ? 'check in' : 'check out';
            
            if (!confirm('Are you sure you want to ' + label + '?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', action);
            formData.append('booking_id', bookingId);

            fetch('../includes/client_checkin_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Please try again.');
            });
        }

        function togglePin(element) {
            const actualPin = element.getAttribute('data-pin');
            
            if (element.innerText === '******') {
                element.innerText = actualPin;
            } else {
                element.innerText = '******';
            }
        }
    </script>
</body>
</html>