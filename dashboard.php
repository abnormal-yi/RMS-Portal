<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();
requireRole('admin');

$user = getCurrentUser();
$total_users = db()->query("SELECT COUNT(*) as cnt FROM users")->fetch()['cnt'];
$total_landlords = db()->query("SELECT COUNT(*) as cnt FROM users WHERE role='landlord'")->fetch()['cnt'];
$total_tenants = db()->query("SELECT COUNT(*) as cnt FROM users WHERE role='tenant'")->fetch()['cnt'];
$pending_approvals = db()->query("SELECT COUNT(*) as cnt FROM users WHERE role='landlord' AND approved=0")->fetch()['cnt'];
$pending_list = db()->query("SELECT * FROM users WHERE role='landlord' AND approved=0 ORDER BY created_at DESC")->fetchAll();

ob_start();
?>
<div class="space-y-6">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Admin Dashboard</h1>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl bg-white px-4 pt-5 pb-6 shadow border border-gray-100">
            <p class="truncate text-sm font-medium text-gray-500">Total Users</p>
            <p class="mt-1 text-3xl font-semibold text-gray-900"><?= $total_users ?></p>
        </div>
        <div class="rounded-xl bg-white px-4 pt-5 pb-6 shadow border border-gray-100">
            <p class="truncate text-sm font-medium text-gray-500">Landlords</p>
            <p class="mt-1 text-3xl font-semibold text-blue-600"><?= $total_landlords ?></p>
        </div>
        <div class="rounded-xl bg-white px-4 pt-5 pb-6 shadow border border-gray-100">
            <p class="truncate text-sm font-medium text-gray-500">Tenants</p>
            <p class="mt-1 text-3xl font-semibold text-green-600"><?= $total_tenants ?></p>
        </div>
        <div class="rounded-xl bg-white px-4 pt-5 pb-6 shadow border border-gray-100">
            <p class="truncate text-sm font-medium text-gray-500">Pending Approvals</p>
            <p class="mt-1 text-3xl font-semibold <?= $pending_approvals > 0 ? 'text-orange-600' : 'text-gray-900' ?>"><?= $pending_approvals ?></p>
        </div>
    </div>

    <?php if (count($pending_list) > 0): ?>
    <div class="bg-orange-50 border border-orange-200 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-orange-900">Pending Landlord Approvals</h3>
            <a href="users.php" class="text-sm font-medium text-orange-700 hover:text-orange-800">View All →</a>
        </div>
        <div class="space-y-3">
            <?php foreach ($pending_list as $p): ?>
            <div class="flex items-center justify-between bg-white rounded-lg p-4 shadow-sm border border-orange-100">
                <div>
                    <p class="text-sm font-medium text-gray-900"><?= hsc($p['full_name'] ?: $p['username']) ?></p>
                    <p class="text-xs text-gray-500"><?= hsc($p['email']) ?> • <?= hsc($p['phone']) ?> • NIDA: <?= hsc($p['nida']) ?></p>
                    <p class="text-xs text-gray-400 mt-1">Property: <?= hsc($p['property_address']) ?></p>
                </div>
                <a href="users.php?action=approve&id=<?= $p['id'] ?>" onclick="return confirm('Approve <?= hsc($p['full_name'] ?: $p['username']) ?>?')" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">Approve</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl shadow border border-gray-100 p-6 text-center">
        <p class="text-gray-500 text-sm">No pending landlord approvals.</p>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Admin Dashboard';
require __DIR__ . '/includes/layout.php';
