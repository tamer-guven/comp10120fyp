<?php
session_start();
require_once '../database/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }

// Fetch available room types from database
$room_types = $conn->query("SELECT * FROM room_types");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book a Room - The Bee Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white p-10 rounded-xl border-t-8 border-amber-500 shadow-2xl">
        <h1 class="text-3xl font-bold text-center mb-6 text-gray-900">Book Your Stay</h1>
        <form action="../includes/book_room_handler.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700">Select Room Type</label>
                <select name="room_type_id" required class="w-full p-3 border rounded-lg focus:ring-amber-500">
                    <?php while($type = $room_types->fetch_assoc()): ?>
                        <option value="<?php echo $type['room_type_id']; ?>">
                            <?php echo $type['type']; ?> - £<?php echo $type['base_price']; ?>/night
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Check-in</label>
                    <input type="date" name="check_in" required class="w-full p-3 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700">Check-out</label>
                    <input type="date" name="check_out" required class="w-full p-3 border rounded-lg">
                </div>
            </div>
            <button type="submit" class="w-full bg-amber-500 py-3 font-bold text-gray-900 rounded-lg hover:bg-amber-600 transition">Confirm Booking</button>
        </form>
    </div>
</body>
</html>