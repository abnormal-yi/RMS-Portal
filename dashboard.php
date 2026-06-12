<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();
requireRole('admin');

$user = getCurrentUser();

$total_properties = db()->query("SELECT COUNT(*) as cnt FROM properties")->fetch()['cnt'];
$total_tenants = db()->query("SELECT COUNT(*) as cnt FROM tenants")->fetch()['cnt'];
$active_contracts = db()->query("SELECT COUNT(*) as cnt FROM contracts WHERE status='active'")->fetch()['cnt'];
$total_revenue = db()->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status='completed'")->fetch()['total'];

$available = db()->query("SELECT * FROM properties WHERE status='available' ORDER BY title")->fetchAll();
$recent_payments = db()->query("
    SELECT p.*, c.tenant_id, t.name as tenant_name
    FROM payments p
    JOIN contracts c ON c.id = p.contract_id
    JOIN tenants t ON t.id = c.tenant_id
    ORDER BY p.created_at DESC LIMIT 5
")->fetchAll();

ob_start();
?>
<div class="space-y-6">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Dashboard Overview</h1>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative overflow-hidden rounded-xl bg-white px-4 pt-5 pb-12 shadow sm:px-6 sm:pt-6 border border-gray-100">
            <dt><div class="absolute rounded-xl bg-blue-500 p-3"><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500">Total Properties</p></dt>
            <dd class="ml-16 flex items-baseline pb-6 sm:pb-7"><p class="text-2xl font-semibold text-gray-900"><?= $total_properties ?></p></dd>
        </div>
        <div class="relative overflow-hidden rounded-xl bg-white px-4 pt-5 pb-12 shadow sm:px-6 sm:pt-6 border border-gray-100">
            <dt><div class="absolute rounded-xl bg-green-500 p-3"><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg></div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500">Active Tenants</p></dt>
            <dd class="ml-16 flex items-baseline pb-6 sm:pb-7"><p class="text-2xl font-semibold text-gray-900"><?= $total_tenants ?></p></dd>
        </div>
        <div class="relative overflow-hidden rounded-xl bg-white px-4 pt-5 pb-12 shadow sm:px-6 sm:pt-6 border border-gray-100">
            <dt><div class="absolute rounded-xl bg-purple-500 p-3"><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500">Active Contracts</p></dt>
            <dd class="ml-16 flex items-baseline pb-6 sm:pb-7"><p class="text-2xl font-semibold text-gray-900"><?= $active_contracts ?></p></dd>
        </div>
        <div class="relative overflow-hidden rounded-xl bg-white px-4 pt-5 pb-12 shadow sm:px-6 sm:pt-6 border border-gray-100">
            <dt><div class="absolute rounded-xl bg-amber-500 p-3"><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <p class="ml-16 truncate text-sm font-medium text-gray-500">Total Revenue</p></dt>
            <dd class="ml-16 flex items-baseline pb-6 sm:pb-7"><p class="text-2xl font-semibold text-gray-900"><?= formatCurrency($total_revenue) ?></p></dd>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Available Properties</h3>
            <?php if (count($available) > 0): ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($available as $prop): ?>
                <li class="py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?= hsc($prop['title']) ?></p>
                        <p class="text-sm text-gray-500"><?= hsc($prop['address']) ?></p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"><?= formatCurrency($prop['rent_amount']) ?>/mo</span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-sm text-gray-500 text-center py-4">No available properties at the moment.</p>
            <?php endif; ?>
        </div>

        <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Recent Payments</h3>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($recent_payments as $payment): ?>
                <li class="py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?= hsc($payment['tenant_name'] ?? 'Unknown Tenant') ?></p>
                        <p class="text-xs text-gray-500"><?= date('M d, Y', strtotime($payment['date'])) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?= formatCurrency($payment['amount']) ?></p>
                        <span class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $payment['method']) ?></span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$page_title = 'Dashboard';
require __DIR__ . '/includes/layout.php';
