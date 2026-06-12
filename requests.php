<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();
requireRole('admin');

// Handle status update
$update_id = $_GET['update'] ?? '';
$new_status = $_GET['status'] ?? '';
if ($update_id && $new_status) {
    $stmt = db()->prepare("UPDATE service_requests SET status=? WHERE id=?");
    $stmt->execute([$new_status, $update_id]);
    header('Location: requests.php');
    exit;
}

$requests = db()->query("SELECT * FROM service_requests ORDER BY date DESC")->fetchAll();
$tenants = db()->query("SELECT * FROM tenants")->fetchAll();
$contracts_map = [];
$cstmt = db()->query("SELECT c.*, p.title as property_title FROM contracts c JOIN properties p ON p.id = c.property_id");
foreach ($cstmt as $c) $contracts_map[$c['id']] = $c;

$tenant_map = [];
foreach ($tenants as $t) $tenant_map[$t['id']] = $t;

function getRequestIcon(string $type): string {
    if ($type === 'maintenance') {
        return '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.066z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
    }
    if ($type === 'move_out') {
        return '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>';
    }
    return '<svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
}
function getRequestLabel(string $type): string {
    if ($type === 'maintenance') return 'Repair/Maintenance';
    if ($type === 'move_out') return 'Notice to Vacate';
    return 'Contract Extension';
}

ob_start();
?>
<div class="space-y-6">
    <div class="sm:flex sm:items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Tenant Requests & Notices</h1>
        <p class="mt-2 text-sm text-gray-700">Manage complaints, maintenance requests, and move-out notices from tenants.</p>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant / Property</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php foreach ($requests as $req):
                        $tenant = $tenant_map[$req['tenant_id']] ?? null;
                        $contract = $contracts_map[$req['contract_id']] ?? null;
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"><?= date('M d, Y', strtotime($req['date'])) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                            <div class="flex items-center space-x-2">
                                <?= getRequestIcon($req['type']) ?>
                                <span><?= getRequestLabel($req['type']) ?></span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                            <div class="font-medium"><?= hsc($tenant['name'] ?? 'Unknown') ?></div>
                            <div class="text-gray-500 text-xs"><?= hsc($contract['property_title'] ?? '') ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs break-words"><?= hsc($req['description']) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm"><?= statusBadge($req['status']) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <?php if ($req['status'] === 'pending'): ?>
                            <div class="flex justify-end space-x-2">
                                <?php $resolve_status = $req['type'] === 'maintenance' ? 'resolved' : 'approved'; ?>
                                <a href="?update=<?= $req['id'] ?>&status=<?= $resolve_status ?>"
                                   class="inline-flex items-center p-1.5 border border-transparent rounded-full shadow-sm text-white bg-green-600 hover:bg-green-700"
                                   title="Approve / Mark Resolved">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </a>
                                <a href="?update=<?= $req['id'] ?>&status=rejected"
                                   class="inline-flex items-center p-1.5 border border-transparent rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700"
                                   title="Reject">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </a>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($requests) === 0): ?>
                    <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">No requests from tenants yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Requests';
require __DIR__ . '/includes/layout.php';
