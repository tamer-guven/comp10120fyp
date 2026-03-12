<?php
/* Client requests a clean, added to hk request database.

notes:
- What if a client has multiple rooms
*/

session_start();
require_once '../database/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: clientlogin.php");
    exit();
}

$booking_id = $_GET['booking_id'];

$stmt = $conn->prepare("SELECT b.booking_id, b.customer_id, b.room_id, r.room_number FROM bookings b LEFT JOIN rooms r ON b.room_id = r.room_id WHERE b.booking_id = ? LIMIT 1");
    
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if ($booking['customer_id'] != $_SESSION['user_id']){
    header("Location: clientdashboard.php");
    exit();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Housekeeping - The Bee Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        :root { font-family: 'Inter', sans-serif; }

        /* Custom style for the modal to ensure it covers the entire screen */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            padding: 2.5rem;
            border-radius: 1.5rem;
            max-width: 90%;
            width: 500px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: fadeInScale 0.3s ease-out;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Header & Navigation -->
    <header class="bg-white shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-wider">The <span class="text-amber-500">Bee</span> Hotel</h1>
            <nav class="space-x-4 flex items-center">
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="goToPage('clientdashboard.php')">
                    &larr; Back to Dashboard
                </button>
                <button id="logoutBtn" class="px-5 py-2 bg-gray-800 text-white rounded-lg shadow-md hover:bg-gray-900 transition duration-150 font-medium" onclick="handleLogout()">
                    Log Out
                </button>
            </nav>
        </div>
    </header>

    <!-- Main Content Area: Housekeeping Form -->
    <main class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <h2 class="text-4xl font-bold text-gray-900 mb-2">Request Housekeeping</h2>
        <p class="text-gray-600 text-lg mb-8">Schedule your room cleaning or towel refresh at a convenient time.</p>

        <div class="bg-white p-6 sm:p-10 rounded-2xl shadow-xl border-t-4 border-amber-500">
            <form id="housekeepingForm">

                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id'];?>">
                <input type="hidden" name="room_id" value="<?php echo $booking['room_id'];?>">

                <!-- Room Info (Placeholder) -->
                <div class="mb-6 pb-4 border-b">
                    <h3 class="text-xl font-bold text-gray-800">Room Details</h3>
                    <p class="text-gray-600">Requesting service for Room <span class="font-semibold text-amber-600"><?= $booking['room_number'] ?></span>.</p>
                </div>

                <!-- 1. Service Type -->
                <div class="mb-8">
                    <label class="block text-xl font-bold text-gray-700 mb-3">1. Select Service Type</label>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center p-4 bg-gray-100 rounded-xl cursor-pointer hover:bg-gray-200 transition shadow-sm w-full sm:w-auto">
                            <input type="radio" name="serviceType" value="Full Cleaning" class="form-radio text-amber-500 h-5 w-5" checked>
                            <span class="ml-3 text-lg font-medium text-gray-800">Full Cleaning (30-45 mins)</span>
                        </label>
                        <label class="flex items-center p-4 bg-gray-100 rounded-xl cursor-pointer hover:bg-gray-200 transition shadow-sm w-full sm:w-auto">
                            <input type="radio" name="serviceType" value="Towel & Amenity Refresh" class="form-radio text-amber-500 h-5 w-5">
                            <span class="ml-3 text-lg font-medium text-gray-800">Towel & Amenity Refresh (5 mins)</span>
                        </label>
                    </div>
                </div>

                <!-- 2. Preferred Time -->
                <div class="mb-8">
                    <label for="timeSlot" class="block text-xl font-bold text-gray-700 mb-3">2. Choose Preferred Time Today</label>
                    <select id="timeSlot" name="timeSlot" required class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm p-3 text-lg focus:border-amber-500 focus:ring-amber-500">
                        <!-- Options generated by JavaScript -->
                    </select>
                    <p class="text-sm text-gray-500 mt-2">Available slots are from 9:00 AM to 4:30 PM with 30-minute intervals.</p>
                </div>

                <!-- 3. Special Requests -->
                <div class="mb-8">
                    <label for="notes" class="block text-xl font-bold text-gray-700 mb-3">3. Special Requests (Optional)</label>
                    <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm p-3 focus:border-amber-500 focus:ring-amber-500" placeholder="e.g., 'Please pay attention to the bathroom floor' or 'Do not disturb my work desk.'"></textarea>
                </div>

                <!-- Submission Button -->
                <div class="pt-6 border-t">
                    <button type="submit" class="w-full py-3 bg-gray-800 text-white font-semibold text-xl rounded-xl shadow-lg hover:bg-amber-500 hover:text-gray-900 transition duration-200">
                        Confirm Housekeeping Request
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Success Modal (Hidden by default) -->
    <div id="housekeepingModal" class="modal-overlay hidden">
        <div class="modal-content text-center">
            <svg class="w-20 h-20 text-green-500 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-3xl font-bold text-gray-900 mb-3">Request Confirmed!</h3>
            <p class="text-lg text-gray-600 mb-8" id="modalMessageText">
                Your request has been successfully scheduled. You will be notified when staff begins service.
            </p>

            <button onclick="goToPage('clientdashboard.php')" class="w-full py-3 bg-amber-500 text-gray-900 font-semibold text-lg rounded-xl shadow-md hover:bg-amber-600 transition duration-200">
                Go Back to Dashboard
            </button>
        </div>
    </div>


    <script>
        // Central navigation function
        function goToPage(filename) {
            window.location.href = filename;
        }
        function handleLogout() {
            goToPage('clientlogin.php');
        }

        // --- Time Slot Generation Logic ---
        function generateTimeSlots() {
            const select = document.getElementById('timeSlot');
            // Clear existing options first
            select.innerHTML = ''; 

            const startTime = 9 * 60; // 9:00 AM in minutes
            const endTime = 17 * 60;  // 5:00 PM in minutes (last slot will be 4:30 PM)
            const interval = 30; // 30 minutes

            for (let totalMinutes = startTime; totalMinutes < endTime; totalMinutes += interval) {
                const hour = Math.floor(totalMinutes / 60);
                const minute = totalMinutes % 60;
                
                // Format time to 12-hour clock
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const displayHour = hour % 12 || 12; // Convert 0 to 12 for 12 AM
                const displayMinute = minute < 10 ? '0' + minute : minute;
                
                const timeString = `${displayHour}:${displayMinute} ${ampm}`;

                const option = document.createElement('option');
                option.value = timeString;
                option.textContent = timeString;
                
                // Set the 9:00 AM slot as selected by default
                if (totalMinutes === startTime) {
                    option.selected = true;
                }
                
                select.appendChild(option);
            }
        }

        // Function to show the modal with customized text
        function showHousekeepingModal(serviceType, timeSlot) {
            const modal = document.getElementById('housekeepingModal');
            const modalText = document.getElementById('modalMessageText');
            
            modalText.innerHTML = `Your request for <strong>${serviceType}</strong> at <strong>${timeSlot}</strong> has been successfully scheduled! A staff member will confirm shortly.`;
            modal.classList.remove('hidden');
        }

        // Handle form submission
        document.getElementById('housekeepingForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('../includes/client_housekeeping_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            //.then(data => {console.log("PHP response:", data);}
            
            .then(data => {
                console.log("PHP response:", data);

                if (data === "Success"){
                // 1. Show the confirmation modal
                    showHousekeepingModal(formData.get('serviceType'), formData.get('timeSlot'));
                }
                else{
                    alert("Error: ", data);
                }
            })

            .catch(err => {
                console.error(err);
                alert("Network error");
            });
        });

        // Initialize time slots when the page loads
        window.onload = generateTimeSlots;
    </script>
</body>
</html>