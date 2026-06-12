<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();
requireRole('tenant');

$user = getCurrentUser();
$tenant_id = $user['tenant_id'];

$tenant = db()->prepare("SELECT * FROM tenants WHERE id = ?");
$tenant->execute([$tenant_id]);
$tenant = $tenant->fetch();

$contract = db()->prepare("SELECT * FROM contracts WHERE tenant_id = ? AND status='active' LIMIT 1");
$contract->execute([$tenant_id]);
$contract = $contract->fetch();

if (!$tenant || !$contract) {
    $content = '<div class="text-center py-12"><h3 class="text-lg font-medium text-gray-900">No Active Rental</h3><p class="mt-1 text-sm text-gray-500">You currently do not have an active rental contract.</p></div>';
    $page_title = 'Tenant Dashboard';
    require __DIR__ . '/includes/layout.php';
    exit;
}

$property = db()->prepare("SELECT * FROM properties WHERE id = ?");
$property->execute([$contract['property_id']]);
$property = $property->fetch();

$stmt = db()->prepare("SELECT * FROM payments WHERE contract_id = ? ORDER BY date DESC");
$stmt->execute([$contract['id']]);
$payments = $stmt->fetchAll();

$stmt = db()->prepare("SELECT * FROM service_requests WHERE tenant_id = ? ORDER BY date DESC");
$stmt->execute([$tenant_id]);
$requests = $stmt->fetchAll();

$days_until_end = floor((strtotime($contract['end_date']) - time()) / 86400);
$is_expiring_soon = $days_until_end <= 45;
$pending_payments = array_filter($payments, fn($p) => $p['status'] === 'pending');

// Handle form submissions
$action = $_GET['action'] ?? '';
$success_msg = '';
$error_msg = '';

if ($action === 'generate_control_number') {
    if (count($pending_payments) > 0) {
        $error_msg = 'You already have a pending rent payment. Please clear it first before generating a new control number.';
    } else {
        $control_num = '99' . random_int(100000000, 999999999);
        $new_id = 'pay' . round(microtime(true) * 1000);
        $stmt = db()->prepare("INSERT INTO payments (id, contract_id, amount, date, method, status, control_number) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$new_id, $contract['id'], $property['rent_amount'], date('Y-m-d'), 'control_number', 'pending', $control_num]);
        $success_msg = "Control Number Generated Successfully: {$control_num}\nWaiting for bank verification...";
        // Reload payments
        $stmt = db()->prepare("SELECT * FROM payments WHERE contract_id = ? ORDER BY date DESC");
        $stmt->execute([$contract['id']]);
        $payments = $stmt->fetchAll();
        $pending_payments = array_filter($payments, fn($p) => $p['status'] === 'pending');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['submit_type'] ?? '') === 'request') {
    $req_type = $_POST['request_type'] ?? 'maintenance';
    $description = $_POST['description'] ?? '';
    $req_id = 'req' . round(microtime(true) * 1000);
    $stmt = db()->prepare("INSERT INTO service_requests (id, tenant_id, contract_id, type, description, date, status) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$req_id, $tenant_id, $contract['id'], $req_type, $description, date('Y-m-d'), 'pending']);
    $success_msg = 'Your request has been submitted successfully.';
    // Reload
    $stmt = db()->prepare("SELECT * FROM service_requests WHERE tenant_id = ? ORDER BY date DESC");
    $stmt->execute([$tenant_id]);
    $requests = $stmt->fetchAll();
}

function getRequestLabel(string $type): string {
    if ($type === 'maintenance') return 'Repair Request';
    if ($type === 'move_out') return 'Notice to Vacate';
    return 'Contract Extension';
}

ob_start();
?>
<div class="space-y-6">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Karibu, <?= hsc($tenant['name']) ?></h1>

    <?php if ($success_msg): ?>
    <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg shadow-sm">
        <div class="flex"><div class="flex-shrink-0"><svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <div class="ml-3"><p class="text-sm text-green-800"><?= hsc($success_msg) ?></p></div></div>
    </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg shadow-sm">
        <div class="flex"><div class="flex-shrink-0"><svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <div class="ml-3"><p class="text-sm text-red-800"><?= hsc($error_msg) ?></p></div></div>
    </div>
    <?php endif; ?>

    <div class="space-y-3">
        <?php if ($is_expiring_soon): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg shadow-sm">
            <div class="flex"><div class="flex-shrink-0"><svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg></div>
            <div class="ml-3"><p class="text-sm text-yellow-800"><strong>Contract Reminder:</strong> Your rental contract expires in <?= $days_until_end ?> days (<?= date('M d, Y', strtotime($contract['end_date'])) ?>). Please renew your contract if you plan to stay.</p></div></div>
        </div>
        <?php endif; ?>

        <?php if (count($pending_payments) > 0): ?>
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg shadow-sm">
            <div class="flex"><div class="flex-shrink-0"><svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div class="ml-3">
                <p class="text-sm text-blue-800 font-medium pb-1">You have pending rent payments.</p>
                <div class="text-xs text-blue-700 space-y-1">
                    <?php foreach ($pending_payments as $p): ?>
                    <div><b>Control Number:</b> <?= hsc($p['control_number']) ?> | <b>Amount:</b> <?= formatCurrency($p['amount']) ?> <i>(Waiting for verification)</i></div>
                    <?php endforeach; ?>
                </div>
            </div></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-8 space-y-6">
            <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Your Rental Details</h3>
                    <a href="pdf_generate.php?contract_id=<?= $contract['id'] ?>" class="flex items-center text-sm font-medium text-[#7B5CFA] hover:text-[#6849E3] bg-[#7B5CFA]/10 px-3 py-1.5 rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download PDF
                    </a>
                </div>
                <div class="px-6 py-5">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-1 flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <div><dt class="text-sm font-medium text-gray-500">Property</dt><dd class="mt-1 text-sm text-gray-900"><?= hsc($property['title']) ?></dd><dd class="text-sm text-gray-500"><?= hsc($property['address']) ?></dd></div>
                        </div>
                        <div class="sm:col-span-1 flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div><dt class="text-sm font-medium text-gray-500">Rent Amount</dt><dd class="mt-1 text-sm text-gray-900 font-bold"><?= formatCurrency($property['rent_amount']) ?> / month</dd></div>
                        </div>
                        <div class="sm:col-span-1 flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <div><dt class="text-sm font-medium text-gray-500">Contract Period</dt><dd class="mt-1 text-sm text-gray-900"><?= date('M d, Y', strtotime($contract['start_date'])) ?> - <?= date('M d, Y', strtotime($contract['end_date'])) ?></dd></div>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Payment History</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php foreach ($payments as $p): ?>
                            <tr><td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900"><?= date('M d, Y', strtotime($p['date'])) ?></td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-900"><?= formatCurrency($p['amount']) ?></td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm capitalize text-gray-500"><?= str_replace('_', ' ', $p['method']) ?><?php if ($p['control_number']): ?><div class="text-xs mt-1 text-gray-400">Ref: <?= hsc($p['control_number']) ?></div><?php endif; ?></td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm"><?= statusBadge($p['status']) ?></td></tr>
                            <?php endforeach; ?>
                            <?php if (count($payments) === 0): ?>
                            <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">No payment history found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-xl shadow border border-gray-100 p-5">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 gap-3">
                    <a href="?action=generate_control_number" <?php if (count($pending_payments) > 0): ?>onclick="return confirm('You already have a pending rent payment. Generate anyway?')"<?php endif; ?>
                       class="w-full flex items-center justify-between p-3 rounded-lg border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                        <span class="flex items-center text-sm font-medium"><svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>Generate Control Number</span>
                    </a>

                    <button onclick="openRequestModal('extension')" class="w-full flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <span class="flex items-center text-sm font-medium text-gray-700"><svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Renew Contract</span>
                    </button>

                    <button onclick="openRequestModal('maintenance')" class="w-full flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                        <span class="flex items-center text-sm font-medium text-gray-700"><svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.573-1.066z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Report Issue (Repair)</span>
                    </button>

                    <button onclick="openRequestModal('move_out')" class="w-full flex items-center justify-between p-3 rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 transition-colors">
                        <span class="flex items-center text-sm font-medium"><svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Notice to Vacate</span>
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-100 p-5">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Your Requests</h3>
                <?php if (count($requests) > 0): ?>
                <ul class="divide-y divide-gray-100 space-y-3">
                    <?php foreach (array_slice($requests, 0, 5) as $req): ?>
                    <li class="pt-3 first:pt-0">
                        <div class="flex justify-between items-start">
                            <div><p class="text-sm font-medium text-gray-900"><?= getRequestLabel($req['type']) ?></p><p class="text-xs text-gray-500 mt-1 truncate max-w-[150px]"><?= hsc($req['description']) ?></p></div>
                            <span class="px-2 py-1 text-xs rounded-full font-medium <?= $req['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' ?>"><?= $req['status'] ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-sm text-gray-500 text-center py-4">No recent requests.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Request Modal -->
<div id="requestModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
            <h3 id="modalTitle" class="text-lg font-semibold text-gray-900"></h3>
            <button onclick="closeRequestModal()" class="text-gray-400 hover:text-gray-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form method="post" class="p-6">
            <input type="hidden" name="submit_type" value="request">
            <input type="hidden" name="request_type" id="requestType" value="maintenance">
            <p id="modalDesc" class="text-sm text-gray-600 mb-4"></p>
            <textarea name="description" required class="w-full h-32 rounded-lg border border-gray-300 p-3 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Type your details here..."></textarea>
            <button type="submit" class="mt-4 w-full flex justify-center py-2.5 px-4 rounded-lg bg-blue-600 text-white font-medium text-sm hover:bg-blue-700">Submit Request</button>
        </form>
    </div>
</div>

<script>
function openRequestModal(type) {
    document.getElementById('requestModal').classList.remove('hidden');
    document.getElementById('requestType').value = type;
    const titles = {maintenance: 'Repair Request', move_out: 'Notice to Vacate', extension: 'Renew Contract'};
    const descs = {maintenance: 'Please describe the problem at your property (e.g., plumbing issue).', move_out: 'Please specify your planned move-out date and the reason for leaving.', extension: 'Please indicate how long you wish to extend the contract (e.g., 6 months, 1 year).'};
    document.getElementById('modalTitle').textContent = titles[type] || 'Request';
    document.getElementById('modalDesc').textContent = descs[type] || '';
}
function closeRequestModal() {
    document.getElementById('requestModal').classList.add('hidden');
}

// Auto-open modal from sidebar action
<?php if (in_array($action, ['extension', 'maintenance', 'move_out'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    openRequestModal('<?= $action ?>');
});
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
$page_title = 'Tenant Dashboard';
require __DIR__ . '/includes/layout.php';
