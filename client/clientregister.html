<?php
// Status messages (optional) - expects ?success=1 or ?error=...
$message = "";
$messageClass = "hidden";

if (isset($_GET['success'])) {
    $message = "Account created successfully! You can now log in.";
    $messageClass = "bg-green-100 text-green-700 block";
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] === "email_taken") $message = "That email is already registered.";
    elseif ($_GET['error'] === "invalid_email") $message = "Please enter a valid email address.";
    elseif ($_GET['error'] === "weak_password") $message = "Password must be at least 8 characters (include a number if required).";
    elseif ($_GET['error'] === "password_mismatch") $message = "Passwords do not match.";
    else $message = "Something went wrong. Please try again.";
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
            <div>
                <label for="client-name" class="block text-sm font-semibold text-gray-700 mb-1">Full Name</label>
                <input type="text" id="client-name" name="client-name" required
                       placeholder="Ahmet Dongel"
                       class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
            </div>

            <div>
                <label for="client-email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                <input type="email" id="client-email" name="client-email" required
                       placeholder="guest@example.com"
                       class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
            </div>

            <div>
                <label for="client-password" class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                <input type="password" id="client-password" name="client-password" required minlength="8"
                       placeholder="••••••••"
                       class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
                <p class="text-xs text-gray-500 mt-2">Use at least 8 characters.</p>
            </div>

            <div>
                <label for="client-password-confirm" class="block text-sm font-semibold text-gray-700 mb-1">Confirm Password</label>
                <input type="password" id="client-password-confirm" name="client-password-confirm" required minlength="8"
                       placeholder="••••••••"
                       class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500">
            </div>

            <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-lg font-bold text-gray-900 bg-amber-500 hover:bg-amber-600 transition duration-150">
                Create My Account
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Already have an account?
            <a href="clientlogin.php" class="text-amber-600 hover:text-amber-800 font-medium">Log In</a>
        </p>
    </div>

    <script>
        // Client-side password match check (still validate on server!)
        const pw = document.getElementById('client-password');
        const pw2 = document.getElementById('client-password-confirm');

        function validateMatch() {
            if (pw2.value && pw.value !== pw2.value) {
                pw2.setCustomValidity("Passwords do not match.");
            } else {
                pw2.setCustomValidity("");
            }
        }

        pw.addEventListener('input', validateMatch);
        pw2.addEventListener('input', validateMatch);
    </script>
</body>
</html>
