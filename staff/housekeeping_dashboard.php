<?php
session_start();
require_once '../database/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

$staff_id = $_SESSION['staff_id'];
$first_name = $_SESSION['first_name'] ?? 'Staff';
$conn->select_db("hotel_management_db");

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $action = $_POST['action'];
    $request_id = (int)$_POST['request_id'];
    
    $new_status = '';
    if ($action === 'start') {
        $new_status = 'in_progress';
    } elseif ($action === 'complete') {
        $new_status = 'completed';
    }

    if ($new_status) {
        // Update the task status
    $stmt = $conn->prepare("UPDATE housekeeping_requests SET status = ?, completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END WHERE request_id = ? AND staff_id = ?");
    $stmt->bind_param("ssii", $new_status, $new_status, $request_id, $staff_id);
        $stmt->execute();
        $stmt->close();
        
        // If completed, update the room's last_cleaned date
        if ($new_status === 'completed') {
            $stmt_room = $conn->prepare("UPDATE rooms r JOIN housekeeping_requests h ON r.room_id = h.room_id SET r.last_cleaned = CURDATE() WHERE h.request_id = ?");
            $stmt_room->bind_param("i", $request_id);
            $stmt_room->execute();
            $stmt_room->close();
        }
        
        // Redirect to prevent form resubmission
        header("Location: housekeeping_dashboard.php?updated=1");
        exit();
    }
}

// Fetch upcoming tasks (assigned or in_progress)
$sql = "SELECT h.*, r.room_number 
        FROM housekeeping_requests h 
        JOIN rooms r ON h.room_id = r.room_id 
        WHERE h.staff_id = ? AND h.status IN ('assigned', 'in_progress') 
        ORDER BY DATE(h.scheduled_datetime) ASC, TIME(h.scheduled_datetime) ASC";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch tasks completed today (based on completion timestamp; fallback to scheduled date if completion timestamp missing)
$sql_completed = "SELECT h.*, r.room_number 
    FROM housekeeping_requests h 
    JOIN rooms r ON h.room_id = r.room_id 
    WHERE h.staff_id = ? AND h.status = 'completed' AND DATE(COALESCE(h.completed_at, h.scheduled_datetime)) = CURDATE()
    ORDER BY COALESCE(h.completed_at, h.scheduled_datetime) DESC";
$stmt_comp = $conn->prepare($sql_completed);
$stmt_comp->bind_param("i", $staff_id);
$stmt_comp->execute();
$completed_tasks = $stmt_comp->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_comp->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Housekeeping Dashboard - The Bee Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        :root { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <header class="bg-white shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-wider">The <span class="text-amber-500">Bee</span> Hotel <span class="text-sm text-gray-600 font-normal">Housekeeping</span></h1>
            <nav class="space-x-4 flex items-center">
                <button id="logoutBtn" class="px-5 py-2 bg-gray-800 text-white rounded-lg shadow-md hover:bg-gray-900 transition duration-150 font-medium" onclick="window.location.href='../login.php'">
                    Log Out
                </button>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-xl mb-6 font-semibold shadow-sm transition-opacity" id="successAlert">
                Task status updated successfully!
            </div>
            <script>
                setTimeout(() => { document.getElementById('successAlert').style.display = 'none'; }, 3000);
            </script>
        <?php endif; ?>

        <div class="mb-8">
            <h2 class="text-4xl font-bold text-gray-900 mb-2">Hello, <?php echo htmlspecialchars($first_name); ?>!</h2>
            <p class="text-gray-600 text-lg">Here is your cleaning schedule for today.</p>
        </div>

        <h3 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Upcoming Tasks (<?php echo count($tasks); ?>)</h3>
        
        <?php if (count($tasks) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <?php foreach ($tasks as $task): ?>
                    <div class="bg-white rounded-2xl shadow-lg border-t-4 <?php echo $task['status'] === 'in_progress' ? 'border-blue-500' : 'border-amber-500'; ?> p-6 flex flex-col h-full transform transition hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">Room <?php echo htmlspecialchars($task['room_number']); ?></h3>
                                <span class="inline-block px-3 py-1 bg-amber-100 text-amber-800 text-xs font-semibold rounded-full mt-1">
                                    <?php echo htmlspecialchars($task['service_type']); ?>
                                </span>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-500">Scheduled Time</p>
                                <p class="text-lg font-bold <?php echo (strtotime($task['scheduled_datetime']) < time() && $task['status'] !== 'in_progress') ? 'text-red-600' : 'text-gray-900'; ?>">
                                    <?php echo date('h:i A', strtotime($task['scheduled_datetime'])); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="mb-4 flex-grow">
                            <p class="text-gray-600 text-sm mb-2">
                                <span class="font-semibold text-gray-800">Est. Duration:</span> ~<?php echo $task['estimated_duration']; ?> mins
                            </p>
                            <p class="text-gray-600 text-sm mb-3">
                                <span class="font-semibold text-gray-800">Status:</span> 
                                <?php if ($task['status'] === 'in_progress'): ?>
                                    <span class="text-blue-600 font-bold">In Progress</span>
                                <?php else: ?>
                                    <span class="text-gray-600 font-medium">Assigned</span>
                                <?php endif; ?>
                            </p>

                            <?php if (!empty($task['notes'])): ?>
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 mt-2">
                                    <p class="text-sm text-gray-700"><span class="font-semibold block mb-1">Guest Notes:</span> <?php echo nl2br(htmlspecialchars($task['notes'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-auto border-t border-gray-100 pt-4">
                            <form method="POST" class="flex gap-2">
                                <input type="hidden" name="request_id" value="<?php echo $task['request_id']; ?>">
                                
                                <?php if ($task['status'] === 'assigned'): ?>
                                    <button type="submit" name="action" value="start" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2.5 px-4 rounded-xl shadow-sm transition duration-150">
                                        Start
                                    </button>
                                <?php endif; ?>
                                
                                <button type="submit" name="action" value="complete" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-bold py-2.5 px-4 rounded-xl shadow-sm transition duration-150" onclick="return confirm('Confirm that room <?php echo $task['room_number']; ?> is fully cleaned?')">
                                    Mark Complete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-2xl shadow-md p-10 text-center mb-12 border border-gray-100">
                <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <h3 class="text-xl font-bold text-gray-900 mb-2">All Caught Up!</h3>
                <p class="text-gray-500">You have no pending housekeeping tasks at the moment.</p>
            </div>
        <?php endif; ?>

        <h3 class="text-2xl font-bold text-gray-800 mb-4 border-b pb-2">Completed Today (<?php echo count($completed_tasks); ?>)</h3>
        
        <?php if (count($completed_tasks) > 0): ?>
            <div class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-200">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($completed_tasks as $task): ?>
                        <li class="p-4 sm:px-6 hover:bg-gray-50 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-lg font-bold text-gray-900">Room <?php echo htmlspecialchars($task['room_number']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($task['service_type']); ?></p>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500 font-medium">
                                    Scheduled: <?php echo date('h:i A', strtotime($task['scheduled_datetime'])); ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p class="text-gray-500 italic">No tasks completed yet today.</p>
        <?php endif; ?>

    </main>

</body>
</html>