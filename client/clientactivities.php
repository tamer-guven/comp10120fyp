<?php
session_start();
require_once '../database/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: clientlogin.php");
    exit();
}

$conn->select_db("hotel_management_db");

// Fetch all available activities from the database
$activities = $conn->query("SELECT * FROM services WHERE is_available = 1 ORDER BY service_id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Activities - The Bee Hotel</title>
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

    <!-- Main Content Area: Activities Catalog -->
    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <h2 class="text-4xl font-bold text-gray-900 mb-2">Book Exciting Activities</h2>
        <p class="text-gray-600 text-lg mb-10">Enhance your stay with our curated services and excursions.</p>

        <!-- Activities Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            <?php if ($activities && $activities->num_rows > 0): ?>
                <?php while ($activity = $activities->fetch_assoc()): ?>
                    <div class="bg-white p-6 rounded-2xl shadow-xl border-t-4 border-amber-500 flex flex-col justify-between">
                        <div>
                            <span class="text-sm font-semibold text-amber-600 uppercase">Activity</span>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1 mb-2"><?php echo htmlspecialchars($activity['name']); ?></h3>
                            <?php if (!empty($activity['description'])): ?>
                                <p class="text-gray-600 text-sm mb-3 leading-relaxed"><?php echo nl2br(htmlspecialchars($activity['description'])); ?></p>
                            <?php endif; ?>
                            <div class="text-sm text-gray-700 space-y-1">
                                <div><span class="font-semibold">Date:</span> <?php echo $activity['activity_date'] ? date('d/m/Y', strtotime($activity['activity_date'])) : 'TBA'; ?></div>
                                <div><span class="font-semibold">Time:</span> <?php echo $activity['activity_time'] ? date('H:i', strtotime($activity['activity_time'])) : 'TBA'; ?></div>
                            </div>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t">
                            <span class="text-xl font-extrabold text-gray-800">£<?php echo number_format($activity['cost'], 2); ?></span>
                            <button class="px-5 py-2 bg-gray-800 text-white font-semibold rounded-lg hover:bg-amber-500 hover:text-gray-900 transition duration-200" onclick="bookService(<?php echo $activity['service_id']; ?>, '<?php echo htmlspecialchars(addslashes($activity['name'])); ?>')">
                                Book Now
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500 text-lg">No activities are currently available. Check back soon!</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Success Confirmation Modal (Centered popup with animation) -->
    <div id="confirmationModal" class="fixed inset-0 bg-gray-900 bg-opacity-70 flex items-center justify-center z-50 hidden" onclick="hideModal()">
        <!-- The modal content wrapper includes the transition classes -->
        <div id="modalContent" class="bg-white p-8 sm:p-12 rounded-2xl shadow-2xl max-w-sm w-full m-4 text-center border-t-8 border-amber-500 transform transition-all duration-300 ease-out opacity-0 scale-95" onclick="event.stopPropagation()">
            
            <!-- Icon -->
            <svg class="mx-auto h-16 w-16 text-green-500 mb-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>

            <h4 class="text-3xl font-bold mb-3 text-gray-900">Booking Confirmed!</h4>
            <p id="modalMessage" class="text-gray-700 mb-8">
                Your activity has been successfully booked. Check your email for confirmation details.
            </p>
            
            <!-- Single Button to Return to Main Dashboard -->
            <button class="w-full px-5 py-3 bg-gray-800 text-white font-bold rounded-lg shadow-md hover:bg-gray-900 transition duration-150" 
                    onclick="returnToDashboard()">
                Back to Dashboard
            </button>
        </div>
    </div>


    <script>
        // Central function for navigation
        function goToPage(filename) {
            window.location.href = filename;
        }

        function handleLogout() {
            goToPage('../login.php');
        }

        // 1. Show the central modal with animation
        function showModal(activityName) {
            const messageElement = document.getElementById('modalMessage');
            messageElement.innerHTML = `<span class="font-bold">${activityName}</span> has been successfully booked. Check your email for confirmation details.`;

            const modal = document.getElementById('confirmationModal');
            const modalContent = document.getElementById('modalContent');
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Start transition for content (fade in and scale up)
            // Use a short timeout to ensure the browser registers the 'hidden' removal before the transition
            setTimeout(() => {
                modalContent.classList.remove('opacity-0', 'scale-95');
                modalContent.classList.add('opacity-100', 'scale-100');
            }, 10);
        }

        // 2. Hide the modal with animation
        function hideModal() {
            const modal = document.getElementById('confirmationModal');
            const modalContent = document.getElementById('modalContent');

            // Start transition for content (fade out and scale down)
            modalContent.classList.remove('opacity-100', 'scale-100');
            modalContent.classList.add('opacity-0', 'scale-95');

            // Hide the wrapper fully after the transition completes (300ms defined in Tailwind class)
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        // 3. Handle the final booking action and show the success popup
        function bookService(serviceId, activityName) {
            const formData = new FormData();
            formData.append('service_id', serviceId);
            
            console.log('Booking service:', serviceId, activityName);
            
            fetch('../includes/book_activity_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    console.log(`Successfully booked service: ${activityName}`);
                    showModal(activityName);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('An error occurred while booking the activity');
            });
        }

        // 4. Action for the button inside the modal
        function returnToDashboard() {
            hideModal();
            goToPage('clientdashboard.php');
        }
    </script>
</body>
</html>
