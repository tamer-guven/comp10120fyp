<?php
$message = "";
$messageClass = "hidden";

if (isset($_GET['error'])) {
    if ($_GET['error'] == "invalid_password") $message = "Incorrect password.";
    elseif ($_GET['error'] == "no_user") $message = "No account found with that email address.";
    $messageClass = "bg-red-100 border border-red-400 text-red-700 block";
} elseif (isset($_GET['success'])) {
    $message = "Account created successfully! Please log in.";
    $messageClass = "bg-green-100 border border-green-400 text-green-700 block";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - The Bee Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">

    <div class="max-w-md w-full mx-auto bg-white p-10 shadow-2xl rounded-2xl border-t-8 border-amber-500">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-2">The <span class="text-amber-500">Bee</span> Hotel</h1>
            <p class="text-gray-500 font-medium">Log in to your account</p>
        </div>
        
        <div id="status-alert" class="mb-6 p-4 rounded-lg text-center font-semibold <?php echo $messageClass; ?>">
            <?php echo $message; ?>
        </div>
        
        <form action="includes/global_login_handler.php" method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-bold text-gray-700 mb-2">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="you@example.com"
                       class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:ring-amber-500 focus:border-amber-500 transition">
            </div>
            <div>
                <label for="password" class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••"
                       class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-xl shadow-sm focus:ring-amber-500 focus:border-amber-500 transition">
            </div>
            
            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-lg font-bold text-gray-900 bg-amber-500 hover:bg-amber-600 transition duration-150 transform hover:-translate-y-0.5">
                Sign In
            </button>
        </form>

        <div class="mt-8 text-center border-t border-gray-200 pt-6">
            <p class="text-sm text-gray-600">
                Are you a guest without an account? <br>
                <a href="client/clientregister.php" class="text-amber-600 hover:text-amber-800 font-bold transition">Register Here</a>
            </p>
        </div>
    </div>

</body>
</html>