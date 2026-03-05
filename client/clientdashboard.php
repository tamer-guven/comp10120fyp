<?php
session_start();
require_once __DIR__ . '/../database/db.php';
require '../includes/housekeeping_scheduler.php';

// If the session variable isn't set, kick them back to login
if (!isset($_SESSION['user_id'])) {
    header("Location: clientlogin.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$conn->select_db("hotel_management_db");

// Handle check-in/check-out API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $booking_id = isset($_POST['booking_id']) && is_numeric($_POST['booking_id']) ? (int)$_POST['booking_id'] : null;
    $action = isset($_POST['action']) ? $_POST['action'] : null;
    
    if (!$booking_id || !$action) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing booking_id or action.']);
        exit;
    }
    
    // Fetch booking and verify it belongs to this customer
    $stmt = $conn->prepare("SELECT booking_id, booking_status, customer_id FROM bookings WHERE booking_id = ? LIMIT 1");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    if (!$booking || $booking['customer_id'] != $customer_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }
    
    // Handle check-in
    if ($action === 'checkin') {
        if ($booking['booking_status'] !== 'booked') {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Booking must be in booked status to check in.']);
            exit;
        }
        
        // Fetch the booking details including room_type_id
        $bookingDetails = $conn->prepare("
            SELECT b.booking_id, b.room_id, b.check_in_date, b.check_out_date, b.room_type_id 
            FROM bookings b 
            WHERE b.booking_id = ? LIMIT 1
        ");
        
        if (!$bookingDetails) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        $bookingDetails->bind_param("i", $booking_id);
        $bookingDetails->execute();
        $bookingResult = $bookingDetails->get_result();
        $bookingData = $bookingResult->fetch_assoc();
        $bookingDetails->close();
        
        if (!$bookingData) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Could not fetch booking details.']);
            exit;
        }
        
        $room_type_id = $bookingData['room_type_id'];
        $check_in = $bookingData['check_in_date'];
        $check_out = $bookingData['check_out_date'];
        
        // Find an available room of the same type that is not occupied during the stay dates
        // Only 'checked_in' bookings block a room since 'booked' ones don't have a real room yet
        $availableRoom = $conn->prepare("
            SELECT r.room_id, r.room_number 
            FROM rooms r 
            WHERE r.room_type_id = ? 
            AND r.room_id NOT IN (
                SELECT b.room_id 
                FROM bookings b 
                WHERE b.booking_status = 'checked_in'
                AND b.check_in_date < ? 
                AND b.check_out_date > ?
            )
            ORDER BY r.room_id
            LIMIT 1
        ");
        
        if (!$availableRoom) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        $availableRoom->bind_param("iss", $room_type_id, $check_out, $check_in);
        $availableRoom->execute();
        $roomResult = $availableRoom->get_result();
        $room = $roomResult->fetch_assoc();
        $availableRoom->close();
        
        // If no available room found, send error
        if (!$room) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'The booked room type is not currently available. Please contact reception for assistance.']);
            exit;
        }
        
        // Update booking with the assigned room and check-in status
        $update = $conn->prepare("UPDATE bookings SET room_id = ?, booking_status='checked_in', actual_check_in = NOW() WHERE booking_id = ?");
        $update->bind_param("ii", $room['room_id'], $booking_id);
        
        if ($update->execute()) {
            echo json_encode(['success' => true, 'message' => 'Checked in successfully. Room assigned: #' . $room['room_number'], 'booking_id' => $booking_id, 'room_number' => $room['room_number']]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        $update->close();
    }
    // Handle check-out
    elseif ($action === 'checkout') {
        if ($booking['booking_status'] !== 'checked_in') {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Booking must be checked in to check out.']);
            exit;
        }
        
        // get room_id for housekeeping
        $get = $conn->prepare("SELECT room_id FROM bookings WHERE booking_id = ?"); 
        $get->bind_param("i", $booking_id); 
        $get->execute(); 
        $result = $get->get_result(); 
        $row = $result->fetch_assoc(); 
        $room_id = $row['room_id'];

        // set booking status to checked out
        $update = $conn->prepare("UPDATE bookings SET booking_status='checked_out' WHERE booking_id = ?");
        $update->bind_param("i", $booking_id);
        
        if ($update->execute()) {
            //assign housekeeping
            ob_start();
            handle_checkout_hk($room_id);
            ob_end_clean();

            echo json_encode(['success' => true, 'message' => 'Checked out successfully.', 'booking_id' => $booking_id]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        $update->close();        
    }
    else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
    
    $conn->close();
    exit;
}

// Fetch the current/latest booking for this customer
$stmt = $conn->prepare("SELECT b.booking_id, b.check_in_date, b.check_out_date, b.booking_status, b.actual_check_in, b.room_id, r.room_number, rt.type FROM bookings b LEFT JOIN rooms r ON b.room_id = r.room_id JOIN room_types rt ON b.room_type_id = rt.room_type_id WHERE b.customer_id = ? ORDER BY b.check_in_date DESC LIMIT 1");
    
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$current_booking = $result->fetch_assoc();
if ($current_booking){
    $_SESSION['current_booking_details'] = array('booking_id'=>$current_booking['booking_id'], 'booking_status'=>$current_booking['booking_status'], 'room_id'=>$current_booking['room_id'], 'room_number'=>$current_booking['room_number'], 'customer_id'=>$customer_id);
}
else{
    $_SESSION['current_booking_details'] = null;
}
$stmt->close();


echo "<pre>";
print_r($_SESSION);
echo "</pre>";
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

    <!-- Header & Navigation (Amber & Charcoal) -->
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

    <!-- Main Content Area -->
    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <h2 class="text-4xl font-bold text-gray-900 mb-2">Welcome, <?php echo $_SESSION['first_name']; ?>!</h2>
        <p class="text-gray-600 text-lg mb-8">Manage your current stay and review past bookings below.</p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- 1. Current Booking Status Card -->
            <div class="lg:col-span-2 bg-white p-6 md:p-8 rounded-2xl shadow-xl border-t-4 border-amber-500 space-y-4">
                <h3 class="text-3xl font-extrabold text-gray-900">Your Current Stay</h3>
                
                <?php if ($current_booking): ?>
                    <?php 
                        $checkInDate = strtotime($current_booking['check_in_date']);
                        $checkOutDate = strtotime($current_booking['check_out_date']);
                        $nights = ceil(($checkOutDate - $checkInDate) / (60 * 60 * 24));
                        $statusBadge = '';
                        $statusColor = '';
                        
                        if ($current_booking['booking_status'] === 'checked_in') {
                            $statusBadge = 'Checked In';
                            $statusColor = 'bg-green-100 text-green-700';
                        } elseif ($current_booking['booking_status'] === 'booked') {
                            $statusBadge = 'Booked';
                            $statusColor = 'bg-blue-100 text-blue-700';
                        } elseif ($current_booking['booking_status'] === 'checked_out') {
                            $statusBadge = 'Checked Out';
                            $statusColor = 'bg-gray-100 text-gray-700';
                        }
                    ?>
                    <div class="grid grid-cols-2 gap-4 text-gray-700">
                        <?php if ($current_booking['booking_status'] === 'checked_in'): ?>
                        <p><span class="font-bold text-gray-900">Room:</span> <span class="text-amber-600"><?= htmlspecialchars($current_booking['room_number']) ?></span> (<?= htmlspecialchars($current_booking['type']) ?>)</p>
                        <?php else: ?>
                        <p><span class="font-bold text-gray-900">Room:</span> <span class="text-gray-400">Not assigned yet</span></p>
                        <?php endif; ?>
                        <p><span class="font-bold text-gray-900">Booking ID:</span> #<?= htmlspecialchars($current_booking['booking_id']) ?></p>
                        <p><span class="font-bold text-gray-900">Check-in:</span> <?= date('M j, Y', strtotime($current_booking['check_in_date'])) ?></p>
                        <p><span class="font-bold text-gray-900">Check-out:</span> <?= date('M j, Y', strtotime($current_booking['check_out_date'])) ?> (<?= $nights ?> Night<?= $nights !== 1 ? 's' : '' ?>)</p>
                        <div class="flex items-center gap-2"><span class="font-bold text-gray-900">Status:</span> <span class="<?= $statusColor ?> px-3 py-1 rounded-full text-sm font-semibold"><?= $statusBadge ?></span></div>
                        <div class="flex justify-start">
                            <?php if ($current_booking['booking_status'] === 'booked'): ?>
                                <button class="py-2 px-4 bg-green-600 text-white font-semibold rounded-lg shadow-md hover:bg-green-700 transition text-sm whitespace-nowrap" 
                                        onclick="handleCheckIn(<?= $current_booking['booking_id'] ?>)">
                                    Check-in
                                </button>
                            <?php elseif ($current_booking['booking_status'] === 'checked_in'): ?>
                                <button class="py-2 px-4 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition text-sm whitespace-nowrap" 
                                        onclick="handleCheckOut(<?= $current_booking['booking_id'] ?>)">
                                    Check-out
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">No active bookings. <a href="clientcheckin.php" class="text-amber-500 hover:text-amber-600 font-semibold">View all bookings</a></p>
                <?php endif; ?>

                <div class="border-t pt-4 mt-4">
                    <h4 class="text-xl font-bold mb-3 text-gray-800">In-Stay Services</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        <!-- Dynamic Link: Housekeeping (First row, first button) -->
                        <?php if ($current_booking && $current_booking['booking_status'] === 'checked_in'):?>
                            <button class="py-3 px-4 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition" 
                                onclick="goToPage('clienthousekeeping.php')">
                                Request Housekeeping
                            </button>
                        <?php else:?>
                            <button class="py-3 px-4 bg-gray-200 text-gray-300 font-semibold rounded-lg shadow-md">
                                Request Housekeeping
                            </button>
                        <?php endif; ?>
                        
                        <!-- Dynamic Link: Activities -->
                        <button class="py-3 px-4 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition" 
                                onclick="goToPage('clientactivities.php')">
                            Book Activities
                        </button>
                        
                        <!-- Dynamic Link: Expenses (Second row, spanning 2 columns) -->
                        <button class="py-3 px-4 bg-amber-500 text-gray-900 font-semibold rounded-lg shadow-md hover:bg-amber-600 transition sm:col-span-2" 
                                onclick="goToPage('clientexpenses.php')">
                            View Expenses
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Past Bookings/Account Overview Card -->
            <div class="bg-gray-800 p-6 md:p-8 rounded-2xl shadow-xl space-y-4 text-white">
                <h3 class="text-2xl font-extrabold text-amber-500">Past Bookings</h3>
                <div class="scroll-container max-h-[400px] overflow-y-auto pr-2">
                    <?php
                        // Fetch all past bookings (excluding the current one)
                        $past_stmt = $conn->prepare("SELECT b.booking_id, b.check_in_date, b.check_out_date, b.booking_status, b.price, b.room_id, r.room_number FROM bookings b LEFT JOIN rooms r ON b.room_id = r.room_id WHERE b.customer_id = ? ORDER BY b.check_in_date DESC LIMIT 10 OFFSET 1");
                        $past_stmt->bind_param("i", $customer_id);
                        $past_stmt->execute();
                        $past_result = $past_stmt->get_result();
                        $past_bookings = $past_result->fetch_all(MYSQLI_ASSOC);
                        $past_stmt->close();
                    ?>

                    <?php if (empty($past_bookings)): ?>
                        <p class="text-sm text-gray-400">No past bookings yet.</p>
                    <?php else: ?>
                        <?php foreach ($past_bookings as $booking): ?>
                            <div class="border-b border-gray-700 pb-3 mb-3">
                                <p class="font-bold">Booking #<?= htmlspecialchars($booking['booking_id']) ?></p>
                                <p class="text-sm text-gray-400"><?= date('M j, Y', strtotime($booking['check_in_date'])) ?> - <?= date('M j, Y', strtotime($booking['check_out_date'])) ?> (<?= $booking['room_id'] > 0 ? 'Room ' . htmlspecialchars($booking['room_number']) : 'Room not assigned' ?>)</p>
                                <p class="text-sm text-amber-500">Total: $<?= number_format($booking['price'], 2) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-gray-400 pt-2">Contact reception for detailed invoices.</p>
            </div>
        </div>
    </main>

    <!-- Check-in Modal -->
    <div id="checkinModal" class="fixed inset-0 bg-gray-900 bg-opacity-70 flex items-center justify-center z-50 hidden" onclick="closeCheckinModal()">
        <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md w-full m-4 border-t-8 border-green-500" onclick="event.stopPropagation()">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Check In</h3>
            <p class="text-gray-700 mb-6">Are you sure you want to check in to your room?</p>
            <div class="flex gap-3">
                <button onclick="confirmCheckIn()" class="flex-1 px-4 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition">
                    Confirm Check In
                </button>
                <button type="button" onclick="closeCheckinModal()" class="flex-1 px-4 py-3 bg-gray-300 text-gray-800 font-bold rounded-lg hover:bg-gray-400 transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Check-out Modal -->
    <div id="checkoutModal" class="fixed inset-0 bg-gray-900 bg-opacity-70 flex items-center justify-center z-50 hidden" onclick="closeCheckoutModal()">
        <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md w-full m-4 border-t-8 border-red-500" onclick="event.stopPropagation()">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Check Out</h3>
            <p class="text-gray-700 mb-6">Are you sure you want to check out from your room?</p>
            <div class="flex gap-3">
                <button onclick="confirmCheckOut()" class="flex-1 px-4 py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition">
                    Confirm Check Out
                </button>
                <button type="button" onclick="closeCheckoutModal()" class="flex-1 px-4 py-3 bg-gray-300 text-gray-800 font-bold rounded-lg hover:bg-gray-400 transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-gray-900 bg-opacity-70 flex items-center justify-center z-50 hidden" onclick="closeSuccessModal()">
        <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md w-full m-4 border-t-8 border-green-500" onclick="event.stopPropagation()">
            <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-2xl font-bold text-gray-900 mb-4 text-center">Success!</h3>
            <p id="successMessage" class="text-gray-700 mb-6 text-center"></p>
            <button onclick="location.reload()" class="w-full px-4 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition">
                Done
            </button>
        </div>
    </div>

    <!-- JavaScript Logic -->
    <script>
        let pendingCheckIn = null;
        let pendingCheckOut = null;

        function goToPage(filename) {
            window.location.href = filename;
        }

        function handleLogout() {
            goToPage('clientlogin.php');
        }

        function handleCheckIn(bookingId) {
            pendingCheckIn = bookingId;
            document.getElementById('checkinModal').classList.remove('hidden');
        }

        function handleCheckOut(bookingId) {
            pendingCheckOut = bookingId;
            document.getElementById('checkoutModal').classList.remove('hidden');
        }

        function closeCheckinModal() {
            document.getElementById('checkinModal').classList.add('hidden');
            pendingCheckIn = null;
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').classList.add('hidden');
            pendingCheckOut = null;
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
        }

        async function confirmCheckIn() {
            if (!pendingCheckIn) return;
            
            const formData = new FormData();
            formData.append('booking_id', pendingCheckIn);
            formData.append('action', 'checkin');

            try {
                const res = await fetch('clientdashboard.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (res.ok && data.success) {
                    closeCheckinModal();
                    // Display success message with room assignment if available
                    if (data.room_number) {
                        document.getElementById('successMessage').textContent = 'You have checked in successfully! Your room is #' + data.room_number + '.';
                    } else {
                        document.getElementById('successMessage').textContent = data.message || 'You have checked in successfully!';
                    }
                    document.getElementById('successModal').classList.remove('hidden');
                } else {
                    alert(data.message || 'Check-in failed.');
                    closeCheckinModal();
                }
            } catch (err) {
                console.error(err);
                alert('Network error. Please try again.');
                closeCheckinModal();
            }
        }

        async function confirmCheckOut() {
            if (!pendingCheckOut) return;
            
            const formData = new FormData();
            formData.append('booking_id', pendingCheckOut);
            formData.append('action', 'checkout');

            try {
                const res = await fetch('clientdashboard.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (res.ok && data.success) {
                    closeCheckoutModal();
                    document.getElementById('successMessage').textContent = 'You have checked out successfully!';
                    document.getElementById('successModal').classList.remove('hidden');
                } else {
                    alert(data.message || 'Check-out failed.');
                    closeCheckoutModal();
                }
            } catch (err) {
                console.error(err);
                alert('Network error. Please try again.');
                closeCheckoutModal();
            }
        }
    </script>
</body>
</html>