<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();

$user = getCurrentUser();
$error = '';

if (!$user['must_change_password']) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 4) {
        $error = 'Password must be at least 4 characters.';
    } elseif ($new_password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        db()->prepare("UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?")
            ->execute([$hash, $user['id']]);
        $_SESSION['password_changed'] = true;
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - RMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">
</head>
<body>
<div class="min-h-screen flex items-center justify-center p-4" style="background-color: #777585;">
    <div class="w-full max-w-md bg-[#282733] rounded-3xl overflow-hidden shadow-2xl p-8 sm:p-12">
        <div class="text-center mb-8">
            <svg class="w-12 h-12 text-[#7B5CFA] mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <h1 class="text-white text-2xl font-medium">Change Your Password</h1>
            <p class="text-gray-400 text-sm mt-2">You must change your password before continuing.</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-3 rounded-lg text-sm text-center mb-6"><?= hsc($error) ?></div>
        <?php endif; ?>
        <form method="post" class="space-y-4">
            <div>
                <input type="password" name="new_password" placeholder="New Password" required minlength="4"
                    class="w-full bg-[#353443] border border-[#424155] text-white placeholder-gray-500 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#7B5CFA] focus:border-transparent sm:text-sm">
            </div>
            <div>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="4"
                    class="w-full bg-[#353443] border border-[#424155] text-white placeholder-gray-500 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-[#7B5CFA] focus:border-transparent sm:text-sm">
            </div>
            <button type="submit"
                class="w-full bg-[#7B5CFA] hover:bg-[#6849E3] text-white font-medium rounded-xl px-4 py-3 transition-colors shadow-lg shadow-[#7B5CFA]/20 mt-2">
                Change Password
            </button>
        </form>
    </div>
</div>
</body>
</html>
