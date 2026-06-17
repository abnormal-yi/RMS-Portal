<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$user = getCurrentUser();
if (!$user || $user['role'] !== 'landlord' || $user['approved']) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approval - RMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center" style="background-color: #777585;">
    <div class="bg-[#282733] rounded-3xl shadow-2xl p-10 max-w-md w-full text-center border border-[#424155]">
        <div class="w-16 h-16 bg-yellow-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-white text-2xl font-medium mb-3">Awaiting Approval</h1>
        <p class="text-gray-400 text-sm mb-2">Your registration has been submitted successfully.</p>
        <p class="text-gray-400 text-sm mb-8">An administrator will review your details and approve your account shortly. You will be able to access the system once approved.</p>
        <div class="bg-[#353443] rounded-xl p-4 border border-[#424155] mb-8">
            <p class="text-gray-300 text-sm font-medium"><?= hsc($user['full_name'] ?? $user['username']) ?></p>
            <p class="text-gray-500 text-xs mt-1">Registered as Landlord</p>
        </div>
        <a href="logout.php" class="text-red-400 hover:text-red-300 text-sm font-medium">Sign out</a>
    </div>
</body>
</html>
