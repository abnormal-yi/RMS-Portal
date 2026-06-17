<?php
/**
 * login.php
 * Handles user authentication. Checks for an existing session, processes
 * the login form, and displays the sign-in page with error feedback.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect authenticated users away from the login page
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Initialize error message and process POST login attempt
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    // Attempt login; redirect on success
    if (login($username, $password)) {
        if (needsPasswordChange()) {
            header('Location: change-password.php');
        } else {
            header('Location: index.php');
        }
        exit;
    }
    // Check if it's an unapproved landlord
    $stmt = db()->prepare("SELECT role, approved FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    if ($u && $u['role'] === 'landlord' && !$u['approved']) {
        $error = 'Your account is pending admin approval. Please check back later.';
    } else {
        $error = 'Invalid username or password';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Rental Management System - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">
</head>
<body>

<div class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8" style="background-color: #777585;">
    <div class="w-full max-w-5xl bg-[#282733] rounded-3xl overflow-hidden shadow-2xl flex flex-col md:flex-row min-h-[600px]">

        <!-- Left Image Section -->
        <div class="hidden md:block md:w-[45%] relative">
            <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?q=80&w=2075&auto=format&fit=crop" alt="Modern Property" class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-[#282733] via-black/20 to-black/40 flex flex-col justify-between pt-10 pb-12 px-10">
                <div class="flex items-center space-x-2 text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span class="font-bold tracking-widest text-lg">RMS</span>
                </div>
                <div class="text-white pt-10">
                    <h2 class="text-3xl font-light leading-snug mb-2">Modern rentals,<br>managed perfectly.</h2>
                    <div class="flex space-x-2 mt-8">
                        <div class="h-1 w-6 bg-white/30 rounded-full"></div>
                        <div class="h-1 w-8 bg-white rounded-full"></div>
                        <div class="h-1 w-6 bg-white/30 rounded-full"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Form Section -->
        <div class="w-full md:w-[55%] p-8 sm:p-12 lg:p-16 flex flex-col justify-center">
            <h1 class="text-white text-3xl font-medium mb-2">Welcome back</h1>
            <p class="text-gray-400 text-sm mb-10">Sign in to the Online Rental Management System to manage your account.</p>

            <!-- Login form with error display and credential fields -->
            <form method="post" class="space-y-5">
                <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-3 rounded-lg text-sm text-center">
                    <?= hsc($error) ?>
                </div>
                <?php endif; ?>

                <div>
                    <input type="text" name="username" placeholder="Username" required value="<?= hsc($_POST['username'] ?? 'admin') ?>"
                        class="w-full bg-[#353443] border border-[#424155] text-white placeholder-gray-500 rounded-xl px-4 py-3.5 focus:outline-none focus:ring-2 focus:ring-[#7B5CFA] focus:border-transparent transition-all sm:text-sm">
                </div>
                <div>
                    <input type="password" name="password" placeholder="Password" required value="<?= hsc($_POST['password'] ?? 'password') ?>"
                        class="w-full bg-[#353443] border border-[#424155] text-white placeholder-gray-500 rounded-xl px-4 py-3.5 focus:outline-none focus:ring-2 focus:ring-[#7B5CFA] focus:border-transparent transition-all sm:text-sm">
                </div>
                <button type="submit"
                    class="w-full bg-[#7B5CFA] hover:bg-[#6849E3] text-white font-medium rounded-xl px-4 py-3.5 transition-colors duration-200 mt-4 shadow-lg shadow-[#7B5CFA]/20">
                    Sign in
                </button>
            </form>

            <div class="mt-12 pt-8 border-t border-[#424155] space-y-3">
                <div class="flex justify-between items-center bg-[#353443] p-3.5 rounded-xl border border-[#424155]">
                    <span class="text-gray-400 text-xs uppercase tracking-wider font-semibold">Admin</span>
                    <span class="font-mono text-gray-200 text-sm">admin / password</span>
                </div>
                <div class="flex justify-between items-center bg-[#353443] p-3.5 rounded-xl border border-[#424155]">
                    <span class="text-gray-400 text-xs uppercase tracking-wider font-semibold">Tenant</span>
                    <span class="font-mono text-gray-200 text-sm">johndoe / password</span>
                </div>
                <div class="flex justify-between items-center bg-[#353443] p-3.5 rounded-xl border border-[#424155]">
                    <span class="text-gray-400 text-xs uppercase tracking-wider font-semibold">Landlord</span>
                    <span class="font-mono text-gray-200 text-sm">landlord / password</span>
                </div>
            </div>
            <div class="mt-6 text-center">
                <a href="register.php" class="text-[#7B5CFA] hover:text-[#6849E3] text-sm font-medium transition-colors">
                    Don't own property? Register as Landlord →
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
