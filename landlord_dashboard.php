<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();
requireRole('landlord');

$user = getCurrentUser();
$lid = $user['id'];

$s1 = db()->prepare("SELECT COUNT(*) as cnt FROM properties WHERE landlord_id = ?"); $s1->execute([$lid]); $total_properties = $s1->fetch()['cnt'];
$s2 = db()->prepare("SELECT COUNT(*) as cnt FROM properties WHERE status='available' AND landlord_id = ?"); $s2->execute([$lid]); $available_properties = $s2->fetch()['cnt'];
$s3 = db()->prepare("SELECT COUNT(*) as cnt FROM tenants WHERE landlord_id = ?"); $s3->execute([$lid]); $total_tenants = $s3->fetch()['cnt'];
$s4 = db()->prepare("SELECT COUNT(*) as cnt FROM contracts c JOIN properties p ON p.id = c.property_id WHERE c.status='active' AND p.landlord_id = ?"); $s4->execute([$lid]); $active_contracts = $s4->fetch()['cnt'];
$s5 = db()->prepare("SELECT COALESCE(SUM(pay.amount),0) as total FROM payments pay JOIN contracts c ON c.id = pay.contract_id JOIN properties p ON p.id = c.property_id WHERE pay.status='completed' AND p.landlord_id = ?"); $s5->execute([$lid]); $total_payments = $s5->fetch()['total'];
$s6 = db()->prepare("SELECT COUNT(*) as cnt FROM service_requests sr JOIN contracts c ON c.id = sr.contract_id JOIN properties p ON p.id = c.property_id WHERE sr.status='pending' AND p.landlord_id = ?"); $s6->execute([$lid]); $pending_requests = $s6->fetch()['cnt'];
$s7 = db()->prepare("SELECT pay.*, c.property_id FROM payments pay JOIN contracts c ON pay.contract_id = c.id JOIN properties p ON p.id = c.property_id WHERE p.landlord_id = ? ORDER BY pay.date DESC LIMIT 5"); $s7->execute([$lid]); $recent_payments = $s7->fetchAll();
$s8 = db()->prepare("SELECT sr.* FROM service_requests sr JOIN contracts c ON c.id = sr.contract_id JOIN properties p ON p.id = c.property_id WHERE p.landlord_id = ? ORDER BY sr.date DESC LIMIT 5"); $s8->execute([$lid]); $recent_requests = $s8->fetchAll();

ob_start();
?>
<div class="space-y-6">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Landlord Dashboard</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm font-medium text-gray-500">Total Properties</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= $total_properties ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm font-medium text-gray-500">Available</p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?= $available_properties ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm font-medium text-gray-500">Active Contracts</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= $active_contracts ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm font-medium text-gray-500">Total Tenants</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= $total_tenants ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-sm font-medium text-gray-500">Pending Requests</p>
            <p class="text-2xl font-bold text-orange-600 mt-1"><?= $pending_requests ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-medium text-gray-900">Revenue Overview</h3>
            </div>
            <div class="px-6 py-5">
                <p class="text-sm text-gray-500">Total Collected Payments</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= formatCurrency($total_payments) ?></p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
            </div>
            <div class="px-6 py-4 space-y-3">
                <a href="properties.php" class="block w-full text-center py-2.5 px-4 rounded-lg bg-blue-600 text-white font-medium text-sm hover:bg-blue-700 transition-colors">Manage Properties</a>
                <a href="tenants.php" class="block w-full text-center py-2.5 px-4 rounded-lg border border-gray-300 text-gray-700 font-medium text-sm hover:bg-gray-50 transition-colors">View Tenants</a>
                <a href="reports.php" class="block w-full text-center py-2.5 px-4 rounded-lg border border-gray-300 text-gray-700 font-medium text-sm hover:bg-gray-50 transition-colors">Generate Reports</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-medium text-gray-900">Recent Payments</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($recent_payments as $p): ?>
                        <tr><td class="px-4 py-3 text-sm text-gray-900"><?= date('M d, Y', strtotime($p['date'])) ?></td>
                            <td class="px-4 py-3 text-sm font-bold text-gray-900"><?= formatCurrency($p['amount']) ?></td>
                            <td class="px-4 py-3 text-sm"><?= statusBadge($p['status']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (count($recent_payments) === 0): ?>
                        <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No payments yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-medium text-gray-900">Recent Requests</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($recent_requests as $r): ?>
                        <tr><td class="px-4 py-3 text-sm capitalize text-gray-900"><?= str_replace('_', ' ', $r['type']) ?></td>
                            <td class="px-4 py-3 text-sm"><?= statusBadge($r['status']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-500"><?= date('M d, Y', strtotime($r['date'])) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (count($recent_requests) === 0): ?>
                        <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">No requests yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Landlord Dashboard';
require __DIR__ . '/includes/layout.php';
