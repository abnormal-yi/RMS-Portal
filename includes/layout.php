<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF'], '.php') ?: 'index';
$current_action = $_GET['action'] ?? '';

$admin_links = [
    ['name' => 'Dashboard', 'path' => 'index.php', 'icon' => 'dashboard'],
    ['name' => 'Users', 'path' => 'users.php', 'icon' => 'users'],
];

$landlord_links = [
    ['name' => 'Dashboard', 'path' => 'index.php', 'icon' => 'dashboard'],
    ['name' => 'Users', 'path' => 'users.php', 'icon' => 'users'],
    ['name' => 'My Properties', 'path' => 'properties.php', 'icon' => 'building'],
    ['name' => 'Tenants', 'path' => 'tenants.php', 'icon' => 'users'],
    ['name' => 'Contracts', 'path' => 'contracts.php', 'icon' => 'file-text'],
    ['name' => 'Payments', 'path' => 'payments.php', 'icon' => 'credit-card'],
    ['name' => 'Requests', 'path' => 'requests.php', 'icon' => 'message-square'],
    ['name' => 'Reports', 'path' => 'reports.php', 'icon' => 'bar-chart'],
];

$tenant_links = [
    ['name' => 'My Dashboard', 'path' => 'index.php', 'icon' => 'dashboard', 'action' => ''],
    ['name' => 'Pay Rent (Control No.)', 'path' => 'index.php?action=generate_control_number', 'icon' => 'credit-card', 'action' => 'generate_control_number'],
    ['name' => 'Renew Contract', 'path' => 'index.php?action=extension', 'icon' => 'file-text', 'action' => 'extension'],
    ['name' => 'Report Issue (Repair)', 'path' => 'index.php?action=maintenance', 'icon' => 'wrench', 'action' => 'maintenance'],
    ['name' => 'Notice to Vacate', 'path' => 'index.php?action=move_out', 'icon' => 'file-output', 'action' => 'move_out'],
];

if ($user['role'] === 'admin') {
    $links = $admin_links;
} elseif ($user['role'] === 'landlord') {
    $links = $landlord_links;
} else {
    $links = $tenant_links;
}

function iconSVG(string $name): string {
    $icons = [
        'dashboard' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>',
        'building' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>',
        'users' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>',
        'file-text' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        'credit-card' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
        'message-square' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
        'bar-chart' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
        'log-out' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>',
        'wrench' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.066z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        'file-output' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>',
    ];
    return $icons[$name] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Rental Management - <?= hsc($page_title ?? 'Dashboard') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">
    <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body class="bg-gray-100">

<div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-black/50 lg:hidden" style="display: none;"></div>

    <aside class="fixed lg:static inset-y-0 left-0 z-30 w-64 bg-slate-900 transform transition-transform duration-200 ease-in-out -translate-x-full lg:translate-x-0 flex flex-col"
           x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-700">
            <h1 class="text-xl font-bold text-white tracking-tight">RMS Portal</h1>
            <button @click="sidebarOpen = false" class="lg:hidden p-1 -mr-2 text-slate-400 hover:bg-slate-800 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
            <?php foreach ($links as $link):
                $is_active = false;
                if (isset($link['action'])) {
                    $is_active = $current_page === 'index' && $current_action === $link['action'];
                    if ($link['action'] === '') {
                        $is_active = $current_page === 'index' && $current_action === '';
                    }
                } else {
                    $is_active = $current_page === basename($link['path'], '.php');
                }
            ?>
            <a href="<?= $link['path'] ?>"
               class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors <?= $is_active ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                <span class="mr-3 <?= $is_active ? 'text-white' : 'text-slate-500' ?>"><?= iconSVG($link['icon']) ?></span>
                <?= $link['name'] ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 border-t border-slate-700">
            <div class="flex items-center px-3 py-2 text-sm text-slate-300">
                <span class="truncate flex-1"><?= hsc($user['full_name'] ?: $user['username']) ?> <span class="text-slate-500 capitalize">(<?= $user['role'] ?>)</span></span>
            </div>
            <a href="logout.php"
               class="mt-2 w-full flex items-center px-3 py-2 text-sm font-medium text-red-400 rounded-lg hover:bg-slate-800 transition-colors">
                <span class="mr-3"><?= iconSVG('log-out') ?></span>
                Logout
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center px-4 sm:px-6 lg:hidden shadow-sm">
            <button @click="sidebarOpen = true" class="p-2 mr-3 text-gray-500 hover:bg-gray-100 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h2 class="text-lg font-semibold text-gray-900">RMS Portal</h2>
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <?= $content ?? '' ?>
            </div>
        </div>
    </main>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<?php if (isset($extra_scripts)) echo $extra_scripts; ?>
</body>
</html>
