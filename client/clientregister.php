<?php
// Optional status messages (expects ?success=1 or ?error=...)
$message = "";
$messageClass = "hidden";

if (isset($_GET['success'])) {
    $message = "Account created successfully! Please log in.";
    $messageClass = "bg-green-100 text-green-700 block";
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] === "email_taken")
        $message = "That email is already registered.";
    elseif ($_GET['error'] === "invalid_email")
        $message = "Please enter a valid email address.";
    elseif ($_GET['error'] === "weak_password")
        $message = "Password must be at least 8 characters.";
    elseif ($_GET['error'] === "missing_fields")
        $message = "Please fill in all required fields.";
    else
        $message = "Something went wrong. Please try again.";
    $messageClass = "bg-red-100 text-red-700 block";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Register - The Bee Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-900 min-h-screen flex items-center justify-center">

    <div class="max-w-md w-full mx-auto bg-white p-10 shadow-3xl rounded-xl border-t-8 border-amber-500">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Create Guest Account</h1>
            <p class="text-sm text-gray-500">
                Register to manage your reservation at
                <span class="text-amber-600 font-semibold">The Bee Hotel</span>.
            </p>
        </div>

        <div id="status-alert" class="mb-6 p-4 rounded-lg text-center font-medium <?php echo $messageClass; ?>">
            <?php echo $message; ?>
        </div>

        <form action="../includes/register_handler.php" method="POST" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-sm font-semibold text-gray-700 mb-1">First Name</label>
                    <input type="text" id="first_name" name="first_name" required placeholder="John"
                        class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-semibold text-gray-700 mb-1">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required placeholder="Doe"
                        class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                </div>
            </div>

            <div>
                <label for="user_address" class="block text-sm font-semibold text-gray-700 mb-1">Address</label>
                <input type="text" id="user_address" name="user_address" required
                    placeholder="123 Deansgate, Manchester"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="date_of_birth" class="block text-sm font-semibold text-gray-700 mb-1">Date of
                        Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required
                        class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                </div>

                <div>
                    <label for="phone_number" class="block text-sm font-semibold text-gray-700 mb-1">Phone
                        Number</label>
                    <input type="tel" id="phone_number" name="phone_number" required placeholder="+44 7xxx xxx xxx"
                        class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                </div>
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="guest@example.com"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required minlength="8" placeholder="••••••••"
                    class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                <p class="text-xs text-gray-500 mt-2">Use at least 8 characters.</p>
            </div>

            <button type="submit"
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-lg font-bold text-gray-900 bg-amber-500 hover:bg-amber-600 transition duration-150">
                Create My Account
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Already have an account?
            <a href="../login.php" class="text-amber-600 hover:text-amber-800 font-medium">Log In</a>
        </p>
    </div>

</body>

</html>