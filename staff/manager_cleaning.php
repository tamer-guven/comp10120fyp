<?php
session_start();
require_once '../database/db.php';

// Only allow logged-in managers
if (!isset($_SESSION['staff_id']) || (strtolower($_SESSION['role'] ?? '') !== 'manager')) {
    header("Location: ../login.php");
    exit();
}

$conn->select_db("hotel_management_db");

// Housekeeping requests with cleaner & room details
$hk_sql = "SELECT hk.request_id, hk.room_id, hk.service_type, hk.scheduled_datetime, hk.estimated_duration, hk.status, hk.notes,
                  s.staff_id, s.first_name AS staff_first, s.last_name AS staff_last,
                  r.room_number
           FROM housekeeping_requests hk
           LEFT JOIN staff s ON hk.staff_id = s.staff_id
           LEFT JOIN rooms r ON hk.room_id = r.room_id
           ORDER BY hk.scheduled_datetime DESC, hk.request_id DESC";
$housekeeping = $conn->query($hk_sql)->fetch_all(MYSQLI_ASSOC);

// Cleaner overview with active task counts
$cleaner_sql = "SELECT s.staff_id, s.first_name, s.last_name, s.email, s.phone_number, s.role,
                       COALESCE(active.active_tasks, 0) AS active_tasks,
                       COALESCE(next_task.next_time, NULL) AS next_time
                FROM staff s
                LEFT JOIN (
                    SELECT staff_id, COUNT(*) AS active_tasks
                    FROM housekeeping_requests
                    WHERE status IN ('assigned','in_progress')
                    GROUP BY staff_id
                ) active ON active.staff_id = s.staff_id
                LEFT JOIN (
                    SELECT staff_id, MIN(scheduled_datetime) AS next_time
                    FROM housekeeping_requests
                    WHERE status IN ('assigned','in_progress','scheduled')
                    GROUP BY staff_id
                ) next_task ON next_task.staff_id = s.staff_id
                WHERE LOWER(s.role) = 'cleaner'
                ORDER BY s.first_name";
$cleaners = $conn->query($cleaner_sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager - Cleaning Overview</title>
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
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="window.location.href='manager_dashboard.php'">
                    Manager Panel
                </button>
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="window.location.href='admin_dashboard.php'">
                    Dashboard
                </button>
                <button id="logoutBtn" class="px-5 py-2 bg-gray-800 text-white rounded-lg shadow-md hover:bg-gray-900 transition duration-150 font-medium" onclick="window.location.href='../login.php'">
                    Log Out
                </button>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-4xl font-bold text-gray-900 mb-1">Cleaning & Housekeeping Overview</h2>
                <p class="text-gray-600 text-lg">Monitor requests, assignments, and cleaner workload</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- Housekeeping Requests -->
            <section class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-lg border-t-4 border-amber-500 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-gray-900">Housekeeping Requests</h3>
                    <span class="text-sm text-gray-500">Latest first</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">Request</th>
                                <th class="px-4 py-2 text-left">Room</th>
                                <th class="px-4 py-2 text-left">Service</th>
                                <th class="px-4 py-2 text-left">Scheduled</th>
                                <th class="px-4 py-2 text-left">Duration</th>
                                <th class="px-4 py-2 text-left">Cleaner</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($housekeeping)): ?>
                                <tr><td class="px-4 py-3 text-gray-500" colspan="8">No housekeeping requests yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($housekeeping as $hk): ?>
                                    <tr class="border-b">
                                        <td class="px-4 py-2 font-semibold text-gray-900">#HK<?php echo $hk['request_id']; ?></td>
                                        <td class="px-4 py-2 text-gray-800"><?php echo $hk['room_number'] ? 'Room ' . htmlspecialchars($hk['room_number']) : 'N/A'; ?></td>
                                        <td class="px-4 py-2 text-gray-800"><?php echo htmlspecialchars($hk['service_type']); ?></td>
                                        <td class="px-4 py-2 text-gray-700"><?php echo $hk['scheduled_datetime'] ? date('M j, Y g:i A', strtotime($hk['scheduled_datetime'])) : '—'; ?></td>
                                        <td class="px-4 py-2 text-gray-700"><?php echo (int)$hk['estimated_duration']; ?> mins</td>
                                        <td class="px-4 py-2 text-gray-700"><?php echo $hk['staff_id'] ? htmlspecialchars($hk['staff_first'] . ' ' . $hk['staff_last']) : 'Unassigned'; ?></td>
                                        <td class="px-4 py-2">
                                            <?php
                                                $status = strtolower($hk['status']);
                                                $colors = [
                                                    'scheduled' => 'bg-gray-200 text-gray-800',
                                                    'assigned' => 'bg-blue-100 text-blue-700',
                                                    'in_progress' => 'bg-amber-100 text-amber-700',
                                                    'completed' => 'bg-green-100 text-green-700',
                                                    'cancelled' => 'bg-red-100 text-red-700'
                                                ];
                                                $pill = $colors[$status] ?? 'bg-gray-200 text-gray-800';
                                            ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $pill; ?>"><?php echo ucfirst(str_replace('_',' ', $status)); ?></span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600 text-xs max-w-xs">&nbsp;<?php echo $hk['notes'] ? htmlspecialchars($hk['notes']) : ''; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Cleaner Overview -->
            <section class="bg-white p-6 rounded-2xl shadow-lg border-t-4 border-blue-500 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-gray-900">Cleaners</h3>
                    <span class="text-sm text-gray-500">Workload snapshot</span>
                </div>
                <div class="space-y-3">
                    <?php if (empty($cleaners)): ?>
                        <p class="text-gray-500">No cleaners found.</p>
                    <?php else: ?>
                        <?php foreach ($cleaners as $cleaner): ?>
                            <div class="border rounded-lg p-4 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-bold text-gray-900"><?php echo htmlspecialchars($cleaner['first_name'] . ' ' . $cleaner['last_name']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($cleaner['email']); ?> · <?php echo htmlspecialchars($cleaner['phone_number']); ?></p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">Cleaner</span>
                                </div>
                                <div class="mt-3 text-sm text-gray-700 flex flex-col gap-1">
                                    <div>Active tasks: <span class="font-semibold"><?php echo (int)$cleaner['active_tasks']; ?></span></div>
                                    <div>Next task: <span class="font-semibold"><?php echo $cleaner['next_time'] ? date('M j, Y g:i A', strtotime($cleaner['next_time'])) : '—'; ?></span></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
