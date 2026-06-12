<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();
requireRole('admin');

$action = $_GET['action'] ?? '';
$delete_id = $_GET['delete'] ?? '';
$mark_paid = $_GET['mark_paid'] ?? '';

if ($delete_id) {
    db()->prepare("DELETE FROM payments WHERE id = ?")->execute([$delete_id]);
    header('Location: payments.php');
    exit;
}

if ($mark_paid) {
    db()->prepare("UPDATE payments SET status='completed' WHERE id=?")->execute([$mark_paid]);
    header('Location: payments.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contract_id = $_POST['contract_id'] ?? '';
    $amount = (float)($_POST['amount'] ?? 0);
    $date = $_POST['date'] ?? '';
    $method = $_POST['method'] ?? 'bank_transfer';
    $status = $_POST['status'] ?? 'completed';
    $control_number = $_POST['control_number'] ?? '';

    $id = 'pay' . round(microtime(true) * 1000);
    $stmt = db()->prepare("INSERT INTO payments (id, contract_id, amount, date, method, status, control_number) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$id, $contract_id, $amount, $date, $method, $status, $control_number ?: null]);
    header('Location: payments.php');
    exit;
}

$payments = db()->query("SELECT * FROM payments ORDER BY date DESC")->fetchAll();
$contracts = db()->query("SELECT c.*, t.name as tenant_name, p.title as property_title FROM contracts c JOIN tenants t ON t.id = c.tenant_id JOIN properties p ON p.id = c.property_id WHERE c.status='active'")->fetchAll();

$show_modal = $action === 'add';

ob_start();
?>
<div class="space-y-6">
    <div class="sm:flex sm:items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Payments</h1>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="?action=add" class="flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Record Payment
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Property</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php foreach ($payments as $p):
                        $contract = null;
                        foreach ($contracts as $c) {
                            if ($c['id'] === $p['contract_id']) { $contract = $c; break; }
                        }
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900"><?= date('M d, Y', strtotime($p['date'])) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900"><?= hsc($contract['tenant_name'] ?? 'Unknown') ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"><?= hsc($contract['property_title'] ?? 'Unknown') ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-900"><?= formatCurrency($p['amount']) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            <span class="capitalize px-2 py-1 bg-gray-100 rounded text-xs"><?= str_replace('_', ' ', $p['method']) ?></span>
                            <?php if ($p['control_number']): ?>
                            <div class="text-xs text-gray-400 mt-1">Ref: <?= hsc($p['control_number']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm"><?= statusBadge($p['status']) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <?php if ($p['status'] === 'pending'): ?>
                            <a href="?mark_paid=<?= $p['id'] ?>" onclick="return confirm('Confirm that funds for this Control Number have hit your account?')" class="text-green-600 hover:text-green-900 mr-4" title="Confirm Payment Received">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </a>
                            <?php endif; ?>
                            <a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Are you sure you want to delete this payment record?')" class="text-red-600 hover:text-red-900">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($payments) === 0): ?>
                    <tr><td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">No payments recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($show_modal): ?>
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Record Payment</h3>
            <a href="payments.php" class="text-gray-400 hover:text-gray-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></a>
        </div>
        <div class="p-6 overflow-y-auto">
            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Contract / Tenant</label>
                    <select name="contract_id" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="" disabled selected>Select active contract</option>
                        <?php foreach ($contracts as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= hsc($c['tenant_name']) ?> - <?= hsc($c['property_title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Amount (TZS)</label>
                    <input type="number" name="amount" required min="1" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Payment Date</label>
                    <input type="date" name="date" required value="<?= date('Y-m-d') ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                    <select name="method" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Control Number (optional)</label>
                    <input type="text" name="control_number" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="e.g. 99123456789">
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <a href="payments.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = 'Payments';
require __DIR__ . '/includes/layout.php';
