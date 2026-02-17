<?php
session_start();
// If the session variable isn't set, kick them back to login
if (!isset($_SESSION['user_id'])) {
    header("Location: clientlogin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - The Bee Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        :root { font-family: 'Inter', sans-serif; }
        .scroll-container::-webkit-scrollbar { width: 8px; }
        .scroll-container::-webkit-scrollbar-thumb { background-color: #f59e0b; border-radius: 4px; }
        .scroll-container::-webkit-scrollbar-track { background-color: #f3f4f6; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Header & Navigation (Amber & Charcoal) -->
    <header class="bg-white shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-wider">The <span class="text-amber-500">Bee</span> Hotel</h1>
            <nav class="space-x-4 flex items-center">
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="goToPage('clientpage.html')">
                    Home
                </button>
                
                <!-- Menu Button remains in the main header -->
                <button class="px-4 py-2 text-gray-700 rounded-lg hover:text-amber-600 transition duration-150 font-medium" onclick="goToPage('menu.html')">
                    Menu
                </button>

                <button id="logoutBtn" class="px-5 py-2 bg-gray-800 text-white rounded-lg shadow-md hover:bg-gray-900 transition duration-150 font-medium" onclick="handleLogout()">
                    Log Out
                </button>
            </nav>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <h2 class="text-4xl font-bold text-gray-900 mb-2">Welcome, <?php echo $_SESSION['first_name']; ?>!</h2>
        <p class="text-gray-600 text-lg mb-8">Manage your current stay and review past bookings below.</p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- 1. Current Booking Status Card -->
            <div class="lg:col-span-2 bg-white p-6 md:p-8 rounded-2xl shadow-xl border-t-4 border-amber-500 space-y-4">
                <h3 class="text-3xl font-extrabold text-gray-900">Your Current Stay</h3>
                
                <div class="grid grid-cols-2 gap-4 text-gray-700">
                    <p><span class="font-bold text-gray-900">Room:</span> <span class="text-amber-600">402</span> (Deluxe Suite)</p>
                    <p><span class="font-bold text-gray-900">Booking ID:</span> #BEE1007</p>
                    <p><span class="font-bold text-gray-900">Check-in:</span> Nov 3, 2025</p>
                    <p><span class="font-bold text-gray-900">Check-out:</span> Nov 7, 2025 (4 Nights)</p>
                    <p class="col-span-2"><span class="font-bold text-gray-900">Status:</span> <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-semibold">Checked In</span></p>
                </div>

                <div class="border-t pt-4 mt-4">
                    <h4 class="text-xl font-bold mb-3 text-gray-800">In-Stay Services</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        <!-- Dynamic Link: Housekeeping (First row, first button) -->
                        <button class="py-3 px-4 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition" 
                                onclick="goToPage('clienthousekeeping.html')">
                            Request Housekeeping
                        </button>
                        
                        <!-- Dynamic Link: Activities (First row, second button) -->
                        <button class="py-3 px-4 bg-gray-200 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition" 
                                onclick="goToPage('clientactivities.html')">
                            Book Activities
                        </button>
                        
                        <!-- Dynamic Link: Expenses (Second row, spanning 2 columns) -->
                        <button class="py-3 px-4 bg-amber-500 text-gray-900 font-semibold rounded-lg shadow-md hover:bg-amber-600 transition sm:col-span-2" 
                                onclick="goToPage('clientexpenses.html')">
                            View & Pay Expenses
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Past Bookings/Account Overview Card -->
            <div class="bg-gray-800 p-6 md:p-8 rounded-2xl shadow-xl space-y-4 text-white">
                <h3 class="text-2xl font-extrabold text-amber-500">Past Bookings</h3>
                <div class="scroll-container max-h-[400px] overflow-y-auto pr-2">
                    <div class="border-b border-gray-700 pb-3 mb-3">
                        <p class="font-bold">Booking #BEE0951</p>
                        <p class="text-sm text-gray-400">Mar 10 - Mar 13, 2024 (Room 201)</p>
                        <p class="text-sm text-amber-500">Total: $750.00</p>
                    </div>
                    <div class="border-b border-gray-700 pb-3 mb-3">
                        <p class="font-bold">Booking #BEE0722</p>
                        <p class="text-sm text-gray-400">Sep 1 - Sep 3, 2023 (Room 305)</p>
                        <p class="text-sm text-amber-500">Total: $500.00</p>
                    </div>
                    <div class="border-b border-gray-700 pb-3 mb-3">
                        <p class="font-bold">Booking #BEE0510</p>
                        <p class="text-sm text-gray-400">Jan 15 - Jan 17, 2023 (Room 101)</p>
                        <p class="text-sm text-amber-500">Total: $420.00</p>
                    </div>
                    <div class="pb-3 mb-3">
                        <p class="font-bold">Booking #BEE0300</p>
                        <p class="text-sm text-gray-400">Aug 20 - Aug 25, 2022 (Room 401)</p>
                        <p class="text-sm text-amber-500">Total: $1200.00</p>
                    </div>
                </div>
                <p class="text-xs text-gray-400 pt-2">Contact reception for detailed invoices.</p>
            </div>
        </div>
    </main>

    <!-- JavaScript Logic -->
    <script>
        // Central function to handle all navigation
        function goToPage(filename) {
            window.location.href = filename;
        }

        // Simple function to simulate logout (redirects back to login screen)
        function handleLogout() {
            goToPage('clientlogin.php');
        }
    </script>
</body>
</html>