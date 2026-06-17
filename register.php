<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $nida = trim($_POST['nida'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $property_address = trim($_POST['property_address'] ?? '');

    if (!$full_name || !$nida || !$phone || !$email || !$username || !$password || !$property_address) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters.';
    } else {
        $stmt = db()->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username already exists. Please choose another.';
        } else {
            $id = 'u' . round(microtime(true) * 1000);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare("INSERT INTO users (id, username, password, full_name, phone, email, nida, role, approved, property_address) VALUES (?,?,?,?,?,?,?,'landlord',0,?)");
            $stmt->execute([$id, $username, $hash, $full_name, $phone, $email, $nida, $property_address]);
            $success = 'Registration submitted! An admin will review and approve your account shortly.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register as Landlord - RMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">
</head>
<body>
<div class="min-h-screen flex items-center justify-center p-4" style="background-color: #777585;">
    <div class="w-full max-w-lg bg-[#282733] rounded-3xl overflow-hidden shadow-2xl p-8 sm:p-12">
        <div class="flex items-center space-x-2 text-white mb-8">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span class="font-bold tracking-widest text-lg">RMS</span>
        </div>

        <h1 class="text-white text-3xl font-medium mb-2">Register as Landlord</h1>
        <p class="text-gray-400 text-sm mb-8">Fill in your details to request a landlord account.</p>

        <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-3 rounded-lg text-sm text-center mb-6"><?= hsc($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="bg-green-500/10 border border-green-500/50 text-green-400 p-3 rounded-lg text-sm text-center mb-6"><?= hsc($success) ?></div>
        <div class="text-center mt-6">
            <a href="login.php" class="text-[#7B5CFA] hover:text-[#6849E3] text-sm font-medium">← Back to Login</a>
        </div>
        <?php else: ?>
        <form method="post" class="space-y-4">
            <div>
                <input type="text" name="full_name" placeholder="Full Name" required value="<?= hsc($_POST['full_name'] ?? '') ?>"
                    class="w-full bg-[#353443] border border-[#424155] text-white placeholder-gray-500 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#7B5CFA] focus:border-transparent sm:text-sm">
            </div>
            <div>
                <input type="text" name="nida" placeholder="NIDA Number" required value="<?= hsc($_POST['nida'] ?? '') ?>"
                    class="w-full bg-[#353443] border border-[#424155] text-white placeholder-gray-500 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#7B5CFA] focus:border-transparent sm:text-sm">
            </div>
            <div>
                <input type="tel" name="phone" placeholder="Phone Number" required value="<?= hsc($_POST['phone'] ?? '') ?>"
                    class="w-full bg-[#353443] border border-[#424155] text-white placeholder-gray-500 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#7B5CFA] focus:border-transparent sm:text-sm">
            </div>
            <div>
                <input type="email" name="email" placeholder="Email Address" required value="<?= hsc($_POST['email'] ?? '') ?>"
                    class="w-full bg-[#353443] border border-[#424155] text-white placeholder-gray-500 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#7B5CFA] focus:border-transparent sm:text-sm">
            </div>
            <div>
                <input type="text" name="username" placeholder="Username" required value="<?= hsc($_POST['username'] ?? '') ?>"
                    class="w-full bg-[#353443] border border-[#424155] text-white placeholder-gray-500 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#7B5CFA] focus:border-transparent sm:text-sm">
            </div>
            <div>
                <input type="password" name="password" placeholder="Password" required
                    class="w-full bg-[#353443] border border-[#424155] text-white placeholder-gray-500 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#7B5CFA] focus:border-transparent sm:text-sm">
            </div>
            <div>
                <textarea name="property_address" placeholder="Property Address (where you own property)" required
                    class="w-full bg-[#353443] border border-[#424155] text-white placeholder-gray-500 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#7B5CFA] focus:border-transparent sm:text-sm"
                    rows="3"><?= hsc($_POST['property_address'] ?? '') ?></textarea>
            </div>
            <button type="submit"
                class="w-full bg-[#7B5CFA] hover:bg-[#6849E3] text-white font-medium rounded-xl px-4 py-3 transition-colors shadow-lg shadow-[#7B5CFA]/20 mt-2">
                Submit Registration
            </button>
        </form>
        <div class="text-center mt-6">
            <a href="login.php" class="text-gray-400 hover:text-white text-sm">Already have an account? Sign in →</a>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
