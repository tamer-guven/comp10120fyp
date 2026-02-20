<?php
// Check for status messages in the URL to show alerts
$message = "";
$messageClass = "hidden";

if (isset($_GET['success'])) {
    $message = "Account created successfully! Please log in.";
    $messageClass = "bg-green-100 text-green-700 block";
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] == "invalid_password") $message = "Incorrect password.";
    elseif ($_GET['error'] == "no_user") $message = "No account found with that email.";
    $messageClass = "bg-red-100 text-red-700 block";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - The Bee Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">

    <div class="max-w-md w-full mx-auto bg-white p-10 shadow-3xl rounded-xl border-t-8 border-amber-500">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Staff Portal</h1>
            <p class="text-sm text-gray-500">Log in to manage hotel operations at <span class="text-amber-600 font-semibold">The Bee Hotel</span>.</p>
        </div>
        
        <div id="status-alert" class="mb-6 p-4 rounded-lg text-center font-medium <?php echo $messageClass; ?>">
            <?php echo $message; ?>
        </div>
        
        <form action="../includes/staff_login_handler.php" method="POST" class="space-y-6">
            <div>
                <label for="staff-username" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                <input type="email" id="staff-username" name="staff-username" required
                       placeholder="staff@example.com"
                       class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
            </div>
            <div>
                <label for="staff-password" class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                <input type="password" id="staff-password" name="staff-password" required
                       placeholder="••••••••"
                       class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
            </div>
            
            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-lg font-bold text-gray-900 bg-amber-500 hover:bg-amber-600 transition duration-150">
                Access Staff Portal
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Contact administrator for account access
        </p>
    </div>
</body>
</html>
