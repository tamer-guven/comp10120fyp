<?php
session_start();
require_once '../database/db.php';

// If the session variable isn't set, kick them back to login
if (!isset($_SESSION['user_id'])) {
    header("Location: clientlogin.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

// Query to find the most recent active booking for this customer
// This joins the bookings, rooms, and room_types tables to get the full stay details
$query = "SELECT b.*, r.room_number, rt.type 
          FROM bookings b 
          JOIN rooms r ON b.room_id = r.room_id 
          JOIN room_types rt ON r.room_type_id = rt.room_type_id 
          WHERE b.customer_id = ? AND b.booking_status IN ('confirmed', 'checked_in') 
          ORDER BY b.check_in_date DESC LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$current_booking = $stmt->get_result()->fetch_assoc();
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
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="goToPage('clientmenu.html')">
                    Menu
                </button>
                <button id="logoutBtn" class="px-5 py-2 bg-gray-800 text-white rounded-lg shadow-md hover:bg-gray-900 transition duration-150 font-medium" onclick="handleLogout()">
                    Log Out
                </button>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <h2 class="text-4xl font-bold text-gray-900 mb-2">Welcome, <?php echo htmlspecialchars($first_name); ?>!</h2>
        <p class="text-gray-600 text-lg mb-8">Manage your current stay and review past bookings below.</p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <?php if ($current_booking): ?>
                <div class="lg:col-span-2 bg-white p-6 md:p-8 rounded-2xl shadow-xl border-t-4 border-amber-500 space-y-4">
                    <h3 class="text-3xl font-extrabold text-gray-900">Your Current Stay</h3>
                    
                    <div class="grid grid-cols-2 gap-4 text-gray-700">
                        <p><span class="font-bold text-gray-900">Room:</span> <span class="text-amber-600"><?php echo htmlspecialchars($current_booking['room_number']); ?></span> (<?php echo htmlspecialchars($current_booking['type']); ?>)</p>
                        <p><span class="font-bold text-gray-900">Booking ID:</span> #BEE<?php echo $current_booking['booking_id']; ?></p>
                        <p><span class="font-bold text-gray-900">Check-in:</span> <?php echo date("M j, Y", strtotime($current_booking['check_in_date'])); ?></p>
                        <p><span class="font-bold text-gray-900">Check-out:</span> <?php echo date("M j, Y", strtotime($current_booking['check_out_date'])); ?></p>
                        <p class="col-span-2"><span class="font-bold text-gray-900">Status:</span> <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-semibold"><?php echo ucfirst($current_booking['booking_status']); ?></span></p>
                    </div>

                    <div class="border-t pt-4 mt-4">
                        <h4 class="text-xl font-bold mb-3 text-gray-800">In-Stay Services</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <button class="py-3 px-4 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition" onclick="goToPage('clienthousekeeping.html')">
                                Request Housekeeping
                            </button>
                            <button class="py-3 px-4 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition" onclick="goToPage('clientactivities.html')">
                                Book Activities
                            </button>
                            <button class="py-3 px-4 bg-amber-500 text-gray-900 font-semibold rounded-lg shadow-md hover:bg-amber-600 transition sm:col-span-2" onclick="goToPage('clientexpenses.html')">
                                View & Pay Expenses
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="lg:col-span-2 bg-white p-12 rounded-2xl shadow-xl border-t-4 border-amber-500 text-center">
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

            <div class="bg-gray-800 p-6 md:p-8 rounded-2xl shadow-xl space-y-4 text-white">
                <h3 class="text-2xl font-extrabold text-amber-500">Account Overview</h3>
                <div class="scroll-container max-h-[400px] overflow-y-auto pr-2">
                    <p class="text-sm text-gray-400 mb-4">Past stay history will appear here once your current visit is completed.</p>
                    </div>
                <p class="text-xs text-gray-400 pt-2 border-t border-gray-700">Contact reception for detailed invoices of past stays.</p>
            </div>
        </div>
    </main>

    <script>
        function goToPage(filename) {
            window.location.href = filename;
        }

        function handleLogout() {
            // Redirect to a logout script or directly back to login
            window.location.href = 'clientlogin.php';
        }
    </script>
</body>
</html>