<?php
session_start();
require_once '../database/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }

// Fetch available room types from database
$room_types = $conn->query("SELECT * FROM room_types");

// Handle Error Messages from the handler redirect
$error_msg = "";
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'unavailable':
            $error_msg = "Sorry, there are no rooms of this type available for your selected dates. Please choose a different room type or adjust your dates.";
            break;
        case 'dates':
            $error_msg = "Invalid dates selected. Please ensure your check-out date is after your check-in date.";
            break;
        case 'invalid_type':
            $error_msg = "The selected room type is invalid. Please try again.";
            break;
        case 'db':
            $error_msg = "A database error occurred while processing your booking. Please contact support.";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book a Room - The Bee Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        :root { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white p-8 md:p-10 rounded-2xl border-t-8 border-amber-500 shadow-2xl">
        <h1 class="text-3xl font-extrabold text-center mb-6 text-gray-900">Book Your Stay</h1>
        
        <?php if ($error_msg): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm" role="alert">
                <div class="flex items-center text-red-700">
                    <svg class="h-6 w-6 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="font-bold">Booking Unsuccessful</p>
                </div>
                <p class="text-sm mt-2 text-red-600 ml-8 leading-relaxed"><?php echo htmlspecialchars($error_msg); ?></p>
            </div>
        <?php endif; ?>

        <form action="../includes/book_room_handler.php" method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Select Room Type</label>
                <select name="room_type_id" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition shadow-sm">
                    <?php while($type = $room_types->fetch_assoc()): ?>
                        <option value="<?php echo $type['room_type_id']; ?>">
                            <?php echo htmlspecialchars($type['type']); ?> - £<?php echo number_format($type['base_price'], 2); ?>/night
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Check-in</label>
                    <input type="date" id="check_in" name="check_in" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Check-out</label>
                    <input type="date" id="check_out" name="check_out" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition shadow-sm">
                </div>
            </div>
            
            <div class="pt-2 space-y-3">
                <button type="submit" class="w-full bg-amber-500 py-3 font-bold text-gray-900 rounded-lg shadow-md hover:bg-amber-600 focus:ring-4 focus:ring-amber-300 transition transform hover:-translate-y-0.5">
                    Confirm Booking
                </button>
                <button type="button" onclick="window.location.href='clientdashboard.php'" class="w-full bg-gray-100 py-3 font-bold text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    Cancel & Go Back
                </button>
            </div>
        </form>
    </div>

    <script>
        const checkIn = document.getElementById('check_in');
        const checkOut = document.getElementById('check_out');
        
        // Prevent booking in the past
        const today = new Date().toISOString().split('T')[0];
        checkIn.min = today;
        
        // Ensure check-out is at least 1 day after check-in
        checkIn.addEventListener('change', function() {
            if (this.value) {
                const minCheckOut = new Date(this.value);
                minCheckOut.setDate(minCheckOut.getDate() + 1);
                checkOut.min = minCheckOut.toISOString().split('T')[0];
                
                // Reset checkout if invalid
                if (checkOut.value && checkOut.value <= this.value) {
                    checkOut.value = '';
                }
            }
        });
    </script>
</body>
</html>