<?php
session_start();
require_once '../database/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

$conn->select_db("hotel_management_db");

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build queries based on filter
$bookings = [];
$services = [];
$total_revenue = 0;

// Get bookings
if ($filter === 'all' || $filter === 'bookings') {
    $booking_sql = "SELECT b.booking_id, c.first_name, c.last_name, c.email, 
                    b.room_id, b.check_in_date, b.check_out_date, b.booking_status, b.price
                    FROM bookings b
                    JOIN customers c ON b.customer_id = c.customer_id";
    
    if ($search) {
        $booking_sql .= " WHERE c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ?";
    }
    
    $booking_sql .= " ORDER BY b.check_in_date DESC";
    
    $stmt = $conn->prepare($booking_sql);
    
    if ($search) {
        $search_param = "%$search%";
        $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    
    foreach ($bookings as $booking) {
        $total_revenue += $booking['price'];
    }
}

// Get services/activities booked
if ($filter === 'all' || $filter === 'services') {
    $service_sql = "SELECT sc.services_customer_id, c.first_name, c.last_name, c.email, 
                    s.name, s.cost, s.service_id
                    FROM services_customers sc
                    JOIN customers c ON sc.customer_id = c.customer_id
                    JOIN services s ON sc.service_id = s.service_id";
    
    if ($search) {
        $service_sql .= " WHERE c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR s.name LIKE ?";
    }
    
    $service_sql .= " ORDER BY sc.services_customer_id DESC";
    
    $stmt = $conn->prepare($service_sql);
    
    if ($search) {
        $search_param = "%$search%";
        $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $services = $result->fetch_all(MYSQLI_ASSOC);
    
    foreach ($services as $service) {
        $total_revenue += $service['cost'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses & Billing - The Bee Hotel</title>
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
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-wider">The <span class="text-amber-500">Bee</span> Hotel <span class="text-sm text-gray-600 font-normal">Admin</span></h1>
            <nav class="space-x-4 flex items-center">
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="window.location.href='admin_dashboard.php'">
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
        <h2 class="text-4xl font-bold text-gray-900 mb-2">Expenses & Billing</h2>
        <p class="text-gray-600 text-lg mb-8">View all room bookings and service charges</p>

        <!-- Revenue Summary -->
        <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-8 rounded-2xl shadow-lg mb-8 text-white">
            <h3 class="text-2xl font-bold mb-2">Total Revenue</h3>
            <p class="text-5xl font-extrabold">$<?php echo number_format($total_revenue, 2); ?></p>
        </div> 

        <!-- Search & Filter Section -->
        <div class="bg-white p-6 rounded-2xl shadow-lg mb-8 border-t-4 border-amber-500">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Search & Filter</h3>
            <form method="GET" class="flex gap-4 flex-wrap">
                <input type="text" name="search" placeholder="Search by customer name or email..." value="<?php echo htmlspecialchars($search); ?>"
                       class="flex-1 min-w-64 px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                
                <select name="filter" class="px-6 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Expenses</option>
                    <option value="bookings" <?php echo $filter === 'bookings' ? 'selected' : ''; ?>>Room Bookings Only</option>
                    <option value="services" <?php echo $filter === 'services' ? 'selected' : ''; ?>>Services Only</option>
                </select>

                <button type="submit" class="px-6 py-3 bg-amber-500 text-gray-900 font-semibold rounded-lg hover:bg-amber-600 transition">
                    Search
                </button>
                <a href="expenses.php" class="px-6 py-3 bg-gray-300 text-gray-800 font-semibold rounded-lg hover:bg-gray-400 transition">
                    Clear
                </a>
            </form>
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
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Customer</th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Email</th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Room</th>
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
                            <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                            <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($booking['email']); ?></td>
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
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Customer</th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Email</th>
                            <th class="px-6 py-4 text-left font-bold text-gray-900">Service Name</th>
                            <th class="px-6 py-4 text-right font-bold text-gray-900">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-semibold text-gray-900">#<?php echo $service['services_customer_id']; ?></td>
                            <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($service['first_name'] . ' ' . $service['last_name']); ?></td>
                            <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($service['email']); ?></td>
                            <td class="px-6 py-4 font-semibold text-gray-900"><?php echo htmlspecialchars($service['name']); ?></td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900">$<?php echo number_format($service['cost'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php elseif (($filter === 'all' || $filter === 'services') && count($services) === 0 && ($filter !== 'bookings')): ?>
        <div class="bg-white rounded-2xl shadow-lg border-t-4 border-green-500 mb-8 p-8 text-center">
            <p class="text-gray-500 text-lg">No services found</p>
        </div>
        <?php endif; ?>

        <?php if (count($bookings) === 0 && count($services) === 0 && $search): ?>
        <div class="bg-white rounded-2xl shadow-lg border-t-4 border-amber-500 p-8 text-center">
            <p class="text-gray-500 text-lg">No results found for your search</p>
        </div>
        <?php endif; ?>
    </main>

    <script>
        function handleLogout() {
            window.location.href = '../login.php';
        }
    </script>
</body>
</html>
