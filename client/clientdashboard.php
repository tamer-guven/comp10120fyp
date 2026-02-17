<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: clientlogin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Dashboard - The Bee Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-3xl font-extrabold text-gray-900">The <span class="text-amber-500">Bee</span> Hotel</h1>
            <nav class="space-x-4">
                <button class="px-4 py-2 text-gray-700 hover:text-amber-600 font-medium" onclick="window.location.href='clientpage.html'">Home</button>
                <button class="px-5 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition" onclick="window.location.href='clientlogin.php'">Log Out</button>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-10 px-4">
        <h2 class="text-4xl font-bold text-gray-900 mb-2">Welcome, <?php echo $_SESSION['first_name']; ?>!</h2>
        <p class="text-gray-600 text-lg mb-8">Manage your current stay and review past bookings below.</p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 bg-white p-8 rounded-2xl shadow-xl border-t-4 border-amber-500 space-y-4">
                <h3 class="text-3xl font-extrabold text-gray-900">Your Current Stay</h3>
                <div class="grid grid-cols-2 gap-4 text-gray-700">
                    <p><span class="font-bold">Room:</span> <span class="text-amber-600">402</span> (Deluxe Suite)</p>
                    <p><span class="font-bold">Booking ID:</span> #BEE1007</p>
                    <p><span class="font-bold text-gray-900">Status:</span> <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-semibold">Checked In</span></p>
                </div>
            </div>
            <div class="bg-gray-800 p-8 rounded-2xl shadow-xl text-white">
                <h3 class="text-2xl font-extrabold text-amber-500 mb-4">Past Bookings</h3>
                <p class="font-bold">Booking #BEE0951</p>
                <p class="text-sm text-gray-400">Mar 10 - Mar 13, 2024 (Room 201)</p>
            </div>
        </div>
    </main>
</body>
</html>