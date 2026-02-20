<?php
session_start();
require_once '../database/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: clientlogin.php");
    exit();
}

$conn->select_db("hotel_management_db");

$customer_id = $_SESSION['user_id'];
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get bookings for this customer
$bookings = [];
$services = [];
$total_due = 0;

if ($filter === 'all' || $filter === 'bookings') {
    $booking_sql = "SELECT b.booking_id, b.room_id, b.check_in_date, b.check_out_date, 
                    b.booking_status, b.price, rt.type as room_type
                    FROM bookings b
                    JOIN rooms r ON b.room_id = r.room_id
                    JOIN room_types rt ON r.room_type_id = rt.room_type_id
                    WHERE b.customer_id = ?
                    ORDER BY b.check_in_date DESC";
    
    $stmt = $conn->prepare($booking_sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    
    foreach ($bookings as $booking) {
        $total_due += $booking['price'];
    }
}

if ($filter === 'all' || $filter === 'services') {
    $service_sql = "SELECT sc.services_customer_id, s.name, s.cost
                    FROM services_customers sc
                    JOIN services s ON sc.service_id = s.service_id
                    WHERE sc.customer_id = ?
                    ORDER BY sc.services_customer_id DESC";
    
    $stmt = $conn->prepare($service_sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $services = $result->fetch_all(MYSQLI_ASSOC);
    
    foreach ($services as $service) {
        $total_due += $service['cost'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Expenses - The Bee Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        :root { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Header & Navigation -->
    <header class="bg-white shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-wider">The <span class="text-amber-500">Bee</span> Hotel</h1>
            <nav class="space-x-4 flex items-center">
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="window.location.href='clientdashboard.php'">
                    &larr; Back to Dashboard
                </button>
                <button id="logoutBtn" class="px-5 py-2 bg-gray-800 text-white rounded-lg shadow-md hover:bg-gray-900 transition duration-150 font-medium" onclick="handleLogout()">
                    Log Out
                </button>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <h2 class="text-4xl font-bold text-gray-900 mb-2">My Expenses & Services</h2>
        <p class="text-gray-600 text-lg mb-8">View your room bookings and booked activities</p>

        <!-- Total Due Summary -->
        <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-8 rounded-2xl shadow-lg mb-8 text-white">
            <h3 class="text-2xl font-bold mb-2">Total Amount Due</h3>
            <p class="text-5xl font-extrabold">$<?php echo number_format($total_due, 2); ?></p>
        </div>

        <!-- Filter Tabs -->
        <div class="bg-white p-6 rounded-2xl shadow-lg mb-8 border-t-4 border-amber-500">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Filter Expenses</h3>
            <div class="flex gap-4 flex-wrap">
                <a href="?filter=all" class="px-6 py-3 <?php echo $filter === 'all' ? 'bg-amber-500 text-gray-900' : 'bg-gray-200 text-gray-800'; ?> font-semibold rounded-lg hover:shadow-md transition">
                    All Expenses
                </a>
                <a href="?filter=bookings" class="px-6 py-3 <?php echo $filter === 'bookings' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800'; ?> font-semibold rounded-lg hover:shadow-md transition">
                    Room Bookings Only
                </a>
                <a href="?filter=services" class="px-6 py-3 <?php echo $filter === 'services' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-800'; ?> font-semibold rounded-lg hover:shadow-md transition">
                    Services Only
                </a>
            </div>
        </div>

        <!-- Room Bookings Section -->
        <?php if (($filter === 'all' || $filter === 'bookings') && count($bookings) > 0): ?>
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border-t-4 border-blue-500 mb-8">
            <div class="bg-blue-500 text-white px-6 py-4">
                <h3 class="text-2xl font-bold">Room Bookings (<?php echo count($bookings); ?>)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Booking ID</th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Room Type</th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Room Number</th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Check-in</th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Check-out</th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Status</th>
                            <th class="px-6 py-4 text-right font-bold text-gray-900">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-semibold text-gray-900">#<?php echo $booking['booking_id']; ?></td>
                            <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($booking['room_type']); ?></td>
                            <td class="px-6 py-4 font-semibold text-gray-900">Room <?php echo $booking['room_id']; ?></td>
                            <td class="px-6 py-4 text-gray-700"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                            <td class="px-6 py-4 text-gray-700"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                            <td class="px-6 py-4">
                                <?php 
                                $status_colors = [
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'confirmed' => 'bg-blue-100 text-blue-700',
                                    'checked_in' => 'bg-green-100 text-green-700',
                                    'checked_out' => 'bg-gray-100 text-gray-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                    'no_show' => 'bg-orange-100 text-orange-700'
                                ];
                                $class = $status_colors[$booking['booking_status']] ?? 'bg-gray-100 text-gray-700';
                                ?>
                                <span class="<?php echo $class; ?> px-3 py-1 rounded-full text-sm font-semibold">
                                    <?php echo ucfirst(str_replace('_', ' ', $booking['booking_status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900">$<?php echo number_format($booking['price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php elseif (($filter === 'all' || $filter === 'bookings') && count($bookings) === 0 && ($filter !== 'services')): ?>
        <div class="bg-white rounded-2xl shadow-lg border-t-4 border-blue-500 mb-8 p-8 text-center">
            <p class="text-gray-500 text-lg">No room bookings found</p>
        </div>
        <?php endif; ?>

        <!-- Services/Activities Section -->
        <?php if (($filter === 'all' || $filter === 'services') && count($services) > 0): ?>
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border-t-4 border-green-500 mb-8">
            <div class="bg-green-500 text-white px-6 py-4">
                <h3 class="text-2xl font-bold">Booked Services & Activities (<?php echo count($services); ?>)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Booking ID</th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Service Name</th>
                            <th class="px-6 py-4 text-right font-bold text-gray-900">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-semibold text-gray-900">#<?php echo $service['services_customer_id']; ?></td>
                            <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($service['name']); ?></td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900">$<?php echo number_format($service['cost'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php elseif (($filter === 'all' || $filter === 'services') && count($services) === 0 && ($filter !== 'bookings')): ?>
        <div class="bg-white rounded-2xl shadow-lg border-t-4 border-green-500 mb-8 p-8 text-center">
            <p class="text-gray-500 text-lg">No services booked yet</p>
        </div>
        <?php endif; ?>

        <?php if (count($bookings) === 0 && count($services) === 0): ?>
        <div class="bg-white rounded-2xl shadow-lg border-t-4 border-amber-500 p-8 text-center">
            <p class="text-gray-500 text-lg">No expenses to display</p>
        </div>
        <?php endif; ?>
    </main>

    <script>
        function handleLogout() {
            window.location.href = 'clientlogin.html';
        }
    </script>
</body>
</html>
