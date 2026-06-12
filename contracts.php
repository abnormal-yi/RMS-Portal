<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();
requireRole('admin');

$action = $_GET['action'] ?? '';
$edit_id = $_GET['edit'] ?? '';
$delete_id = $_GET['delete'] ?? '';

if ($delete_id) {
    $stmt = db()->prepare("SELECT property_id FROM contracts WHERE id = ?");
    $stmt->execute([$delete_id]);
    $contract = $stmt->fetch();
    if ($contract) {
        db()->prepare("UPDATE properties SET status='available' WHERE id=?")->execute([$contract['property_id']]);
    }
    db()->prepare("DELETE FROM contracts WHERE id = ?")->execute([$delete_id]);
    header('Location: contracts.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property_id = $_POST['property_id'] ?? '';
    $tenant_id = $_POST['tenant_id'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $editing_id = $_POST['editing_id'] ?? '';

    if ($editing_id) {
        $stmt = db()->prepare("UPDATE contracts SET property_id=?, tenant_id=?, start_date=?, end_date=?, status=? WHERE id=?");
        $stmt->execute([$property_id, $tenant_id, $start_date, $end_date, $status, $editing_id]);
    } else {
        $id = 'c' . round(microtime(true) * 1000);
        $stmt = db()->prepare("INSERT INTO contracts (id, property_id, tenant_id, start_date, end_date, status) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$id, $property_id, $tenant_id, $start_date, $end_date, $status]);
        if ($status === 'active') {
            db()->prepare("UPDATE properties SET status='rented' WHERE id=?")->execute([$property_id]);
        }
    }
    header('Location: contracts.php');
    exit;
}

$contracts = db()->query("SELECT * FROM contracts ORDER BY created_at DESC")->fetchAll();
$properties = db()->query("SELECT * FROM properties ORDER BY title")->fetchAll();
$tenants = db()->query("SELECT * FROM tenants ORDER BY name")->fetchAll();

$edit_contract = null;
if ($edit_id) {
    $stmt = db()->prepare("SELECT * FROM contracts WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_contract = $stmt->fetch();
}

$show_modal = $action === 'add' || $edit_id;
$form_data = $edit_contract ?: ['id' => '', 'property_id' => '', 'tenant_id' => '', 'start_date' => '', 'end_date' => '', 'status' => 'active'];

// Build lookups
$prop_map = [];
foreach ($properties as $p) $prop_map[$p['id']] = $p;
$tenant_map = [];
foreach ($tenants as $t) $tenant_map[$t['id']] = $t;

ob_start();
?>
<div class="space-y-6">
    <div class="sm:flex sm:items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Contracts</h1>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="?action=add" class="flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create Contract
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Property</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php foreach ($contracts as $c):
                        $p = $prop_map[$c['property_id']] ?? null;
                        $t = $tenant_map[$c['tenant_id']] ?? null;
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900"><?= hsc($p['title'] ?? 'Unknown Property') ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"><?= hsc($t['name'] ?? 'Unknown Tenant') ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                            <?= date('M d, Y', strtotime($c['start_date'])) ?> - <?= date('M d, Y', strtotime($c['end_date'])) ?>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm"><?= statusBadge($c['status']) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <a href="pdf_generate.php?contract_id=<?= $c['id'] ?>" class="text-gray-500 hover:text-[#7B5CFA] mr-4" title="Download PDF">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </a>
                            <a href="?edit=<?= $c['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <a href="?delete=<?= $c['id'] ?>" onclick="return confirm('Are you sure you want to delete this contract?')" class="text-red-600 hover:text-red-900">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($contracts) === 0): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">No contracts found. Create one to assign a tenant to a property.</td></tr>
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
            <h3 class="text-lg font-semibold text-gray-900"><?= $edit_contract ? 'Edit Contract' : 'Create Contract' ?></h3>
            <a href="contracts.php" class="text-gray-400 hover:text-gray-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></a>
        </div>
        <div class="p-6 overflow-y-auto">
            <form method="post" class="space-y-4">
                <input type="hidden" name="editing_id" value="<?= hsc($form_data['id']) ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Property</label>
                    <select name="property_id" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="" disabled <?= !$form_data['property_id'] ? 'selected' : '' ?>>Select a property</option>
                        <?php foreach ($properties as $p):
                            $show = ($p['status'] === 'available' || $p['id'] === $form_data['property_id']);
                            if (!$show) continue;
                        ?>
                        <option value="<?= $p['id'] ?>" <?= $form_data['property_id'] === $p['id'] ? 'selected' : '' ?>><?= hsc($p['title']) ?> (<?= formatCurrency($p['rent_amount']) ?>/mo)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tenant</label>
                    <select name="tenant_id" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="" disabled <?= !$form_data['tenant_id'] ? 'selected' : '' ?>>Select a tenant</option>
                        <?php foreach ($tenants as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $form_data['tenant_id'] === $t['id'] ? 'selected' : '' ?>><?= hsc($t['name']) ?> (<?= hsc($t['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" required value="<?= hsc($form_data['start_date']) ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" name="end_date" required value="<?= hsc($form_data['end_date']) ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="active" <?= $form_data['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="terminated" <?= $form_data['status'] === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <a href="contracts.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Save Contract
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = 'Contracts';
require __DIR__ . '/includes/layout.php';
