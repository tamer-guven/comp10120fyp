<?php
session_start();
require_once '../database/db.php';

// Only allow logged-in managers
if (!isset($_SESSION['staff_id']) || ($_SESSION['role'] ?? '') !== 'manager') {
    header("Location: stafflogin.php");
    exit();
}

$conn->select_db("hotel_management_db");
$flash = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add staff
    if ($action === 'add_staff') {
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone_number'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($first && $last && $email && $phone && $role && $password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO staff (first_name, last_name, role, phone_number, email, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $first, $last, $role, $phone, $email, $hash);
            if ($stmt->execute()) {
                $flash = 'Staff member added successfully.';
            } else {
                $flash = 'Error adding staff: ' . $conn->error;
            }
            $stmt->close();
        } else {
            $flash = 'Please fill in all staff fields.';
        }
    }

    // Delete staff
    if ($action === 'delete_staff') {
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        if ($staff_id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = ?");
                $stmt->bind_param('i', $staff_id);
                $stmt->execute();
                $stmt->close();
                $flash = 'Staff member removed.';
            } catch (mysqli_sql_exception $e) {
                $flash = 'Cannot remove: Staff member has assigned tasks or history.';
            }
        }
    }

    // Delete room type
    if ($action === 'delete_room_type') {
        $room_type_id = (int)($_POST['room_type_id'] ?? 0);
        if ($room_type_id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM room_types WHERE room_type_id = ?");
                $stmt->bind_param('i', $room_type_id);
                $stmt->execute();
                $stmt->close();
                $flash = 'Room type removed.';
            } catch (mysqli_sql_exception $e) {
                $flash = 'Cannot remove: There are active rooms using this room type.';
            }
        }
    }

    // Delete room
    if ($action === 'delete_room') {
        $room_id = (int)($_POST['room_id'] ?? 0);
        if ($room_id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
                $stmt->bind_param('i', $room_id);
                $stmt->execute();
                $stmt->close();
                $flash = 'Room removed.';
            } catch (mysqli_sql_exception $e) {
                $flash = 'Cannot remove: Room has active bookings or housekeeping history.';
            }
        }
    }

    // Add room
    if ($action === 'add_room') {
        $room_number = (int)($_POST['room_number'] ?? 0);
        $room_type_id = (int)($_POST['room_type_id'] ?? 0);
        if ($room_number > 0 && $room_type_id > 0) {
            $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type_id, last_cleaned) VALUES (?, ?, CURDATE())");
            $stmt->bind_param('ii', $room_number, $room_type_id);
            if ($stmt->execute()) {
                $flash = 'Room added.';
            } else {
                $flash = 'Error adding room: ' . $conn->error;
            }
            $stmt->close();
        } else {
            $flash = 'Please provide room number and room type.';
        }
    }
}

// Fetch data for display
$staff = $conn->query("SELECT staff_id, first_name, last_name, email, phone_number, role FROM staff ORDER BY first_name")->fetch_all(MYSQLI_ASSOC);
$room_types = $conn->query("SELECT room_type_id, type, base_price, max_guests FROM room_types ORDER BY type")->fetch_all(MYSQLI_ASSOC);
$rooms = $conn->query("SELECT r.room_id, r.room_number, rt.type AS room_type FROM rooms r JOIN room_types rt ON r.room_type_id = rt.room_type_id ORDER BY r.room_number")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Panel - The Bee Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        :root { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-wider">The <span class="text-amber-500">Bee</span> Hotel <span class="text-sm text-gray-600 font-normal">Manager</span></h1>
            <nav class="space-x-4 flex items-center">
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="window.location.href='admin_activities.php'">
                    Manage Activities
                </button>
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="window.location.href='manager_cleaning.php'">
                    Cleaning Overview
                </button>
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="window.location.href='admin_dashboard.php'">
                    Dashboard
                </button>
                <button id="logoutBtn" class="px-5 py-2 bg-gray-800 text-white rounded-lg shadow-md hover:bg-gray-900 transition duration-150 font-medium" onclick="handleLogout()">
                    Log Out
                </button>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-4xl font-bold text-gray-900 mb-1">Manager Panel</h2>
                <p class="text-gray-600 text-lg">Add/Remove staff, rooms, and room types</p>
            </div>
            <?php if ($flash): ?>
                <div class="px-4 py-2 bg-amber-100 text-amber-800 rounded-lg shadow-sm border border-amber-200">
                    <?php echo htmlspecialchars($flash); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Staff Management -->
            <section class="bg-white p-6 rounded-2xl shadow-lg border-t-4 border-amber-500 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-gray-900">Staff</h3>
                    <span class="text-sm text-gray-500">Add / Remove</span>
                </div>

                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="action" value="add_staff">
                    <input name="first_name" placeholder="First name" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                    <input name="last_name" placeholder="Last name" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                    <input name="email" type="email" placeholder="Email" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                    <input name="phone_number" placeholder="Phone" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                    <input name="password" type="password" placeholder="Password" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                    <select name="role" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                        <option value="">Select role</option>
                        <option value="manager">Manager</option>
                        <option value="receptionist">Receptionist</option>
                        <option value="cleaner">Cleaner</option>
                    </select>
                    <div class="md:col-span-2">
                        <button type="submit" class="w-full bg-amber-500 text-gray-900 font-semibold py-3 rounded-lg hover:bg-amber-600 transition">Add Staff</button>
                    </div>
                </form>

                <div class="border rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="px-3 py-2 text-left">Name</th>
                                <th class="px-3 py-2 text-left">Email</th>
                                <th class="px-3 py-2 text-left">Role</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $person): ?>
                            <tr class="border-b">
                                <td class="px-3 py-2 font-semibold text-gray-900"><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></td>
                                <td class="px-3 py-2 text-gray-700"><?php echo htmlspecialchars($person['email']); ?></td>
                                <td class="px-3 py-2 text-gray-700"><?php echo htmlspecialchars($person['role']); ?></td>
                                <td class="px-3 py-2 text-right">
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Remove this staff member?');">
                                        <input type="hidden" name="action" value="delete_staff">
                                        <input type="hidden" name="staff_id" value="<?php echo $person['staff_id']; ?>">
                                        <button class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Room Types -->
            <section class="bg-white p-6 rounded-2xl shadow-lg border-t-4 border-amber-500 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-gray-900">Room Types</h3>
                    <span class="text-sm text-gray-500">Add / Remove</span>
                </div>

                <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <input type="hidden" name="action" value="add_room_type">
                    <input name="type" placeholder="Type" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                    <input name="base_price" type="number" step="0.01" placeholder="Base price" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                    <input name="max_guests" type="number" placeholder="Max guests" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                    <div class="md:col-span-3">
                        <button type="submit" class="w-full bg-amber-500 text-gray-900 font-semibold py-3 rounded-lg hover:bg-amber-600 transition">Add Room Type</button>
                    </div>
                </form>

                <div class="border rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="px-3 py-2 text-left">Type</th>
                                <th class="px-3 py-2 text-left">Base Price</th>
                                <th class="px-3 py-2 text-left">Max Guests</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($room_types as $rt): ?>
                            <tr class="border-b">
                                <td class="px-3 py-2 font-semibold text-gray-900"><?php echo htmlspecialchars($rt['type']); ?></td>
                                <td class="px-3 py-2 text-gray-700">£<?php echo number_format($rt['base_price'], 2); ?></td>
                                <td class="px-3 py-2 text-gray-700"><?php echo htmlspecialchars($rt['max_guests']); ?></td>
                                <td class="px-3 py-2 text-right">
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Remove this room type?');">
                                        <input type="hidden" name="action" value="delete_room_type">
                                        <input type="hidden" name="room_type_id" value="<?php echo $rt['room_type_id']; ?>">
                                        <button class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Rooms -->
            <section class="bg-white p-6 rounded-2xl shadow-lg border-t-4 border-amber-500 space-y-4 lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-gray-900">Rooms</h3>
                    <span class="text-sm text-gray-500">Add / Remove</span>
                </div>

                <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <input type="hidden" name="action" value="add_room">
                    <input name="room_number" type="number" placeholder="Room number" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                    <select name="room_type_id" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" required>
                        <option value="">Room type</option>
                        <?php foreach ($room_types as $rt): ?>
                            <option value="<?php echo $rt['room_type_id']; ?>"><?php echo htmlspecialchars($rt['type']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="md:col-span-3">
                        <button type="submit" class="w-full bg-amber-500 text-gray-900 font-semibold py-3 rounded-lg hover:bg-amber-600 transition">Add Room</button>
                    </div>
                </form>

                <div class="border rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="px-3 py-2 text-left">Room</th>
                                <th class="px-3 py-2 text-left">Type</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                            <tr class="border-b">
                                <td class="px-3 py-2 font-semibold text-gray-900"><?php echo htmlspecialchars($room['room_number']); ?></td>
                                <td class="px-3 py-2 text-gray-700"><?php echo htmlspecialchars($room['room_type']); ?></td>
                                <td class="px-3 py-2 text-right">
                                    <form method="POST" class="inline-block" onsubmit="return confirm('Remove this room?');">
                                        <input type="hidden" name="action" value="delete_room">
                                        <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                        <button class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <script>
        function handleLogout() {
            window.location.href = 'stafflogin.php';
        }
    </script>
</body>
</html>
