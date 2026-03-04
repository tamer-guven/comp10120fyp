<?php
session_start();
require_once '../database/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: stafflogin.php");
    exit();
}

$conn->select_db("hotel_management_db");

$success_msg = '';
$error_msg = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SECURITY CHECK: Only managers can perform create/update/delete actions
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
        $error_msg = 'Permission Denied: Only managers can perform this action.';
    } else {
        $action = $_POST['form_action'] ?? '';

        // Create new activity
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $cost = $_POST['cost'] ?? 0;
            $is_available = isset($_POST['is_available']) ? 1 : 0;

            if (empty($name) || $cost < 0) {
                $error_msg = 'Please provide a valid name and cost.';
            } else {
                $stmt = $conn->prepare("INSERT INTO services (name, cost, is_available) VALUES (?, ?, ?)");
                $stmt->bind_param("sdi", $name, $cost, $is_available);
                if ($stmt->execute()) {
                    $success_msg = 'Activity "' . htmlspecialchars($name) . '" created successfully!';
                } else {
                    $error_msg = 'Error creating activity: ' . $conn->error;
                }
                $stmt->close();
            }
        }

        // Update existing activity
        elseif ($action === 'update') {
            $service_id = (int)($_POST['service_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $cost = $_POST['cost'] ?? 0;
            $is_available = isset($_POST['is_available']) ? 1 : 0;

            if (empty($name) || $cost < 0 || !$service_id) {
                $error_msg = 'Please provide valid details.';
            } else {
                $stmt = $conn->prepare("UPDATE services SET name = ?, cost = ?, is_available = ? WHERE service_id = ?");
                $stmt->bind_param("sdii", $name, $cost, $is_available, $service_id);
                if ($stmt->execute()) {
                    $success_msg = 'Activity updated successfully!';
                } else {
                    $error_msg = 'Error updating activity: ' . $conn->error;
                }
                $stmt->close();
            }
        }

        // Delete activity
        elseif ($action === 'delete') {
            $service_id = (int)($_POST['service_id'] ?? 0);
            if ($service_id) {
                // Check if activity has any bookings
                $check = $conn->prepare("SELECT COUNT(*) as count FROM services_customers WHERE service_id = ?");
                $check->bind_param("i", $service_id);
                $check->execute();
                $count = $check->get_result()->fetch_assoc()['count'];
                $check->close();

                if ($count > 0) {
                    $error_msg = 'Cannot delete this activity — it has been booked by customers. You can mark it as unavailable instead.';
                } else {
                    $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
                    $stmt->bind_param("i", $service_id);
                    if ($stmt->execute()) {
                        $success_msg = 'Activity deleted successfully!';
                    } else {
                        $error_msg = 'Error deleting activity: ' . $conn->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Fetch all activities
$activities = $conn->query("SELECT * FROM services ORDER BY service_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Activities - The Bee Hotel</title>
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
                    Dashboard
                </button>
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="window.location.href='expenses.php'">
                    Expenses & Billing
                </button>
                <button id="logoutBtn" class="px-5 py-2 bg-gray-800 text-white rounded-lg shadow-md hover:bg-gray-900 transition duration-150 font-medium" onclick="window.location.href='stafflogin.php'">
                    Log Out
                </button>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <h2 class="text-4xl font-bold text-gray-900 mb-2">Manage Activities</h2>
        <p class="text-gray-600 text-lg mb-8">Create, edit, and manage guest activities and services.</p>

        <!-- Success/Error Messages -->
        <?php if ($success_msg): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-xl mb-6 font-semibold">
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-xl mb-6 font-semibold">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'manager'): ?>
                <!-- Create New Activity Form -->
                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl border-t-4 border-amber-500">
                    <h3 class="text-2xl font-extrabold text-gray-900 mb-6">Create New Activity</h3>
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="form_action" value="create">

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Activity Name</label>
                            <input type="text" name="name" required placeholder="e.g. Spa Treatment"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Cost (£)</label>
                            <input type="number" name="cost" required min="0" step="0.01" placeholder="0.00"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="is_available" id="is_available" checked
                                class="w-5 h-5 text-amber-500 border-gray-300 rounded focus:ring-amber-500">
                            <label for="is_available" class="text-sm font-bold text-gray-700">Available for booking</label>
                        </div>

                        <button type="submit" class="w-full py-3 px-4 bg-amber-500 text-gray-900 font-bold rounded-lg shadow-md hover:bg-amber-600 transition">
                            Create Activity
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Existing Activities List -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl overflow-hidden border-t-4 border-amber-500">
                <div class="p-6 md:p-8">
                    <h3 class="text-2xl font-extrabold text-gray-900 mb-6">All Activities</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="px-6 py-4 text-left font-bold">ID</th>
                                <th class="px-6 py-4 text-left font-bold">Name</th>
                                <th class="px-6 py-4 text-left font-bold">Cost</th>
                                <th class="px-6 py-4 text-left font-bold">Status</th>
                                <th class="px-6 py-4 text-center font-bold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($activities && $activities->num_rows > 0): ?>
                                <?php while ($activity = $activities->fetch_assoc()): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 text-gray-700">#<?php echo $activity['service_id']; ?></td>
                                        <td class="px-6 py-4 font-semibold text-gray-900"><?php echo htmlspecialchars($activity['name']); ?></td>
                                        <td class="px-6 py-4 text-gray-700">£<?php echo number_format($activity['cost'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($activity['is_available']): ?>
                                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-semibold">Available</span>
                                            <?php else: ?>
                                                <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm font-semibold">Unavailable</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'manager'): ?>
                                                <button class="px-3 py-2 bg-blue-500 text-white text-sm font-semibold rounded-lg hover:bg-blue-600 transition"
                                                        onclick="openEditModal(<?php echo $activity['service_id']; ?>, '<?php echo htmlspecialchars(addslashes($activity['name'])); ?>', <?php echo $activity['cost']; ?>, <?php echo $activity['is_available']; ?>)">
                                                    Edit
                                                </button>
                                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this activity?')">
                                                    <input type="hidden" name="form_action" value="delete">
                                                    <input type="hidden" name="service_id" value="<?php echo $activity['service_id']; ?>">
                                                    <button type="submit" class="px-3 py-2 bg-red-500 text-white text-sm font-semibold rounded-lg hover:bg-red-600 transition">
                                                        Delete
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-sm font-medium">View Only</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-400">No activities found. Create one to get started.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-extrabold text-gray-900 mb-6">Edit Activity</h3>
            <form method="POST" class="space-y-5">
                <input type="hidden" name="form_action" value="update">
                <input type="hidden" name="service_id" id="edit_service_id">

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Activity Name</label>
                    <input type="text" name="name" id="edit_name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Cost (£)</label>
                    <input type="number" name="cost" id="edit_cost" required min="0" step="0.01"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_available" id="edit_is_available"
                           class="w-5 h-5 text-amber-500 border-gray-300 rounded focus:ring-amber-500">
                    <label for="edit_is_available" class="text-sm font-bold text-gray-700">Available for booking</label>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 py-3 px-4 bg-amber-500 text-gray-900 font-bold rounded-lg shadow-md hover:bg-amber-600 transition">
                        Save Changes
                    </button>
                    <button type="button" onclick="closeEditModal()" class="flex-1 py-3 px-4 bg-gray-300 text-gray-800 font-bold rounded-lg shadow-md hover:bg-gray-400 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, name, cost, isAvailable) {
            document.getElementById('edit_service_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_cost').value = cost;
            document.getElementById('edit_is_available').checked = isAvailable == 1;
            const modal = document.getElementById('editModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    </script>
</body>
</html>
