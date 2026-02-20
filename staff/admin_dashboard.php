<?php
session_start();
require_once '../database/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: stafflogin.php");
    exit();
}

$conn->select_db("hotel_management_db");

// Get search/filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build search query
$where = "WHERE 1=1";
if ($search) {
    $search_param = "%$search%";
    $where .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone_number LIKE ?)";
}

// Get customers with their active bookings
$sql = "SELECT DISTINCT c.*, 
        b.booking_id, b.room_id, b.check_in_date, b.check_out_date, b.booking_status, b.price,
        r.room_number, rt.type as room_type
        FROM customers c
        LEFT JOIN bookings b ON c.customer_id = b.customer_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN room_types rt ON b.room_type_id = rt.room_type_id
        $where
        ORDER BY c.first_name";

$stmt = $conn->prepare($sql);

if ($search) {
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
}

$stmt->execute();
$result = $stmt->get_result();

// Get all services for the booking modal
$services_sql = "SELECT * FROM services WHERE is_available = 1";
$services_result = $conn->query($services_sql);
$services = $services_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - The Bee Hotel</title>
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
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="window.location.href='expenses.php'">
                    Expenses & Billing
                </button>
                <button id="logoutBtn" class="px-5 py-2 bg-gray-800 text-white rounded-lg shadow-md hover:bg-gray-900 transition duration-150 font-medium" onclick="handleLogout()">
                    Log Out
                </button>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="w-full py-10 px-4 sm:px-6 lg:px-8">
        <h2 class="text-4xl font-bold text-gray-900 mb-2">Admin Dashboard</h2>
        <p class="text-gray-600 text-lg mb-8">Manage customers, bookings, and services</p>

        <!-- Search & Filter Section -->
        <div class="bg-white p-6 rounded-2xl shadow-lg mb-8 border-t-4 border-amber-500">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Search Customers</h3>
            <form method="GET" class="flex gap-4">
                <input type="text" name="search" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>"
                       class="flex-1 px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                <button type="submit" class="px-6 py-3 bg-amber-500 text-gray-900 font-semibold rounded-lg hover:bg-amber-600 transition">
                    Search
                </button>
                <a href="admin_dashboard.php" class="px-6 py-3 bg-gray-300 text-gray-800 font-semibold rounded-lg hover:bg-gray-400 transition">
                    Clear
                </a>
            </form>
        </div>

        <!-- Customers & Bookings Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border-t-4 border-amber-500">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left font-bold">Customer Name</th>
                            <th class="px-6 py-4 text-left font-bold">Email</th>
                            <th class="px-6 py-4 text-left font-bold">Phone</th>
                            <th class="px-6 py-4 text-left font-bold">Booking Number</th>
                            <th class="px-6 py-4 text-left font-bold">Room</th>
                            <th class="px-6 py-4 text-left font-bold">Status</th>
                            <th class="px-6 py-4 text-left font-bold">Price</th>
                            <th class="px-6 py-4 text-center font-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($row['phone_number']); ?></td>
                            <td class="px-6 py-4 text-gray-700">
                                <?php 
                                if ($row['booking_id']) {
                                    echo "#" . htmlspecialchars($row['booking_id']);
                                } else {
                                    echo '<span class="text-gray-400">No active booking</span>';
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 text-gray-700">
                                <?php 
                                if ($row['booking_id'] && $row['booking_status'] === 'checked_in') {
                                    echo htmlspecialchars($row['room_number']) . " (" . htmlspecialchars($row['room_type']) . ")";
                                } elseif ($row['booking_id']) {
                                    echo '<span class="text-gray-400">Not assigned</span>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 text-nowrap">
                                <?php 
                                if ($row['booking_status']) {
                                    $status_colors = [
                                        'checked_in' => 'bg-green-100 text-green-700',
                                        'checked_out' => 'bg-gray-100 text-gray-700',
                                        'booked' => 'bg-blue-100 text-blue-700'
                                    ];
                                    $class = $status_colors[$row['booking_status']] ?? 'bg-gray-100 text-gray-700';
                                    echo '<span class="' . $class . ' px-3 py-1 rounded-full text-sm font-semibold whitespace-nowrap">' . ucfirst(str_replace('_', ' ', $row['booking_status'])) . '</span>';
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                <?php echo $row['price'] ? '$' . number_format($row['price'], 2) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex gap-2 justify-center items-center flex-wrap">
                                    <button onclick="openBookActivityModal(<?php echo $row['customer_id']; ?>, '<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>')" 
                                            class="px-3 py-2 bg-amber-500 text-gray-900 font-semibold rounded-lg hover:bg-amber-600 transition text-sm whitespace-nowrap">
                                        Book Activity
                                    </button>
                                    <button onclick="openHousekeepingModal(<?php echo $row['customer_id']; ?>, '<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>')" 
                                            class="px-3 py-2 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 transition text-sm whitespace-nowrap">
                                        Housekeeping
                                    </button>
                                    <?php if ($row['booking_id']): ?>
                                        <?php if ($row['booking_status'] === 'booked'): ?>
                                        <button onclick="handleCheckIn(<?php echo $row['booking_id']; ?>, '<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>')" 
                                                class="px-3 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition text-sm whitespace-nowrap">
                                            Check In
                                        </button>
                                        <?php elseif ($row['booking_status'] === 'checked_in'): ?>
                                        <button onclick="handleCheckOut(<?php echo $row['booking_id']; ?>, '<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>')" 
                                                class="px-3 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition text-sm whitespace-nowrap">
                                            Check Out
                                        </button>
                                        <?php else: ?>
                                        <span class="text-gray-500 text-sm">—</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <button disabled class="px-3 py-2 bg-gray-300 text-gray-500 font-semibold rounded-lg text-sm cursor-not-allowed whitespace-nowrap">
                                        Check In/Out
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Book Activity Modal -->
    <div id="bookActivityModal" class="fixed inset-0 bg-gray-900 bg-opacity-70 flex items-center justify-center z-50 hidden" onclick="closeBookActivityModal()">
        <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md w-full m-4 border-t-8 border-amber-500" onclick="event.stopPropagation()">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Book Activity for <span id="modalCustomerName"></span></h3>
            <form id="bookActivityForm" onsubmit="submitBookActivity(event)">
                <input type="hidden" id="modalCustomerId" name="customer_id">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Activity</label>
                    <select id="serviceSelect" name="service_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                        <option value="">-- Select an activity --</option>
                        <?php foreach ($services as $service): ?>
                        <option value="<?php echo $service['service_id']; ?>">
                            <?php echo htmlspecialchars($service['name']) . ' ($' . number_format($service['cost'], 2) . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-3 bg-amber-500 text-gray-900 font-bold rounded-lg hover:bg-amber-600 transition">
                        Book Activity
                    </button>
                    <button type="button" onclick="closeBookActivityModal()" class="flex-1 px-4 py-3 bg-gray-300 text-gray-800 font-bold rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Housekeeping Modal -->
    <div id="housekeepingModal" class="fixed inset-0 bg-gray-900 bg-opacity-70 flex items-center justify-center z-50 hidden" onclick="closeHousekeepingModal()">
        <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md w-full m-4 border-t-8 border-blue-500" onclick="event.stopPropagation()">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Request Housekeeping for <span id="housekeepingCustomerName"></span></h3>
            <form id="housekeepingForm" onsubmit="submitHousekeeping(event)">
                <input type="hidden" id="housekeepingCustomerId" name="customer_id">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Request Type</label>
                    <select name="request_type" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                        <option value="">-- Select request type --</option>
                        <option value="cleaning">Room Cleaning</option>
                        <option value="linens">Linens & Towels</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" placeholder="Additional notes..." class="w-full px-4 py-3 border border-gray-300 rounded-lg" rows="3"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-3 bg-blue-500 text-white font-bold rounded-lg hover:bg-blue-600 transition">
                        Request Housekeeping
                    </button>
                    <button type="button" onclick="closeHousekeepingModal()" class="flex-1 px-4 py-3 bg-gray-300 text-gray-800 font-bold rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Check-in Modal -->
    <div id="checkinModal" class="fixed inset-0 bg-gray-900 bg-opacity-70 flex items-center justify-center z-50 hidden" onclick="closeCheckinModal()">
        <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-md w-full m-4 border-t-8 border-green-500" onclick="event.stopPropagation()">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Check In Guest</h3>
            <p id="checkinMessage" class="text-gray-700 mb-6"></p>
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
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Check Out Guest</h3>
            <p id="checkoutMessage" class="text-gray-700 mb-6"></p>
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

    <script>
        let pendingCheckIn = null;
        let pendingCheckOut = null;

        function handleLogout() {
            window.location.href = 'stafflogin.php';
        }

        function handleCheckIn(bookingId, customerName) {
            pendingCheckIn = bookingId;
            document.getElementById('checkinMessage').textContent = 'Check in ' + customerName + '?';
            document.getElementById('checkinModal').classList.remove('hidden');
        }

        function handleCheckOut(bookingId, customerName) {
            pendingCheckOut = bookingId;
            document.getElementById('checkoutMessage').textContent = 'Check out ' + customerName + '?';
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
                const res = await fetch('admin_checkin.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (res.ok && data.success) {
                    closeCheckinModal();
                    // Display success message with room assignment if available
                    if (data.room_number) {
                        document.getElementById('successMessage').textContent = 'Guest checked in successfully! Room assigned: #' + data.room_number;
                    } else {
                        document.getElementById('successMessage').textContent = 'Guest checked in successfully!';
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
                const res = await fetch('admin_checkin.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (res.ok && data.success) {
                    closeCheckoutModal();
                    document.getElementById('successMessage').textContent = 'Guest checked out successfully!';
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

        function openBookActivityModal(customerId, customerName) {
            document.getElementById('modalCustomerId').value = customerId;
            document.getElementById('modalCustomerName').textContent = customerName;
            document.getElementById('bookActivityModal').classList.remove('hidden');
        }

        function closeBookActivityModal() {
            document.getElementById('bookActivityModal').classList.add('hidden');
            document.getElementById('bookActivityForm').reset();
        }

        function submitBookActivity(event) {
            event.preventDefault();
            const customerId = document.getElementById('modalCustomerId').value;
            const serviceId = document.getElementById('serviceSelect').value;

            const formData = new FormData();
            formData.append('customer_id', customerId);
            formData.append('service_id', serviceId);

            fetch('../includes/staff_book_activity.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Activity booked successfully!');
                    closeBookActivityModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while booking the activity');
            });
        }

        function openHousekeepingModal(customerId, customerName) {
            document.getElementById('housekeepingCustomerId').value = customerId;
            document.getElementById('housekeepingCustomerName').textContent = customerName;
            document.getElementById('housekeepingModal').classList.remove('hidden');
        }

        function closeHousekeepingModal() {
            document.getElementById('housekeepingModal').classList.add('hidden');
            document.getElementById('housekeepingForm').reset();
        }

        function submitHousekeeping(event) {
            event.preventDefault();
            const customerId = document.getElementById('housekeepingCustomerId').value;
            const requestType = document.querySelector('[name="request_type"]').value;
            const notes = document.querySelector('[name="notes"]').value;

            const formData = new FormData();
            formData.append('customer_id', customerId);
            formData.append('request_type', requestType);
            formData.append('notes', notes);

            fetch('../includes/staff_housekeeping.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Housekeeping request created successfully!');
                    closeHousekeepingModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while creating the request');
            });
        }
    </script>
</body>
</html>
