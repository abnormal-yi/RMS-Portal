<?php
/**
 * tenants.php
 * Landlord CRUD page for managing tenants. Creating a tenant also
 * auto-creates a user account with generated credentials.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();
requireRole('landlord');

$action = $_GET['action'] ?? '';
$edit_id = $_GET['edit'] ?? '';
$delete_id = $_GET['delete'] ?? '';
$new_creds = $_SESSION['new_tenant_creds'] ?? null;
unset($_SESSION['new_tenant_creds']);

if ($delete_id) {
    db()->prepare("DELETE FROM tenants WHERE id = ?")->execute([$delete_id]);
    header('Location: tenants.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $nida = trim($_POST['nida'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $editing_id = $_POST['editing_id'] ?? '';

    if ($editing_id) {
        db()->prepare("UPDATE tenants SET name=?, nida=?, phone=? WHERE id=?")
            ->execute([$name, $nida, $phone, $editing_id]);
    } else {
        $email = strtolower(str_replace(' ', '.', $name)) . '@rental.local';
        $tid = 't' . round(microtime(true) * 1000);
        db()->prepare("INSERT INTO tenants (id, name, nida, phone, email) VALUES (?,?,?,?,?)")
            ->execute([$tid, $name, $nida, $phone, $email]);

        $creds = generateCredentials();
        $uid = 'u' . round(microtime(true) * 1000);
        $hash = password_hash($creds['password'], PASSWORD_DEFAULT);
        db()->prepare("INSERT INTO users (id, username, password, full_name, phone, nida, role, approved, must_change_password, tenant_id) VALUES (?,?,?,?,?,?,'tenant',1,1,?)")
            ->execute([$uid, $creds['username'], $hash, $name, $phone, $nida, $tid]);

        $_SESSION['new_tenant_creds'] = $creds;
    }
    header('Location: tenants.php');
    exit;
}

$tenants = db()->query("SELECT * FROM tenants ORDER BY name")->fetchAll();
$edit_tenant = null;
if ($edit_id) {
    $stmt = db()->prepare("SELECT * FROM tenants WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_tenant = $stmt->fetch();
}

$show_modal = $action === 'add' || $edit_id;
$form_data = $edit_tenant ?: ['id' => '', 'name' => '', 'nida' => '', 'phone' => ''];

ob_start();
?>
<div class="space-y-6">
    <div class="sm:flex sm:items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Tenants</h1>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="?action=add" class="flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Tenant
            </a>
        </div>
    </div>

    <?php if ($new_creds): ?>
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
        <div class="flex items-start space-x-3">
            <svg class="w-6 h-6 text-emerald-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <h3 class="text-emerald-800 font-semibold">Tenant Created Successfully</h3>
                <p class="text-emerald-700 text-sm mt-1">Share these credentials with the tenant:</p>
                <div class="mt-3 bg-white rounded-lg border border-emerald-200 p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 font-medium">Username</span>
                        <span class="text-sm font-mono font-bold text-gray-900 bg-gray-100 px-3 py-1 rounded"><?= hsc($new_creds['username']) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 font-medium">Password</span>
                        <span class="text-sm font-mono font-bold text-gray-900 bg-gray-100 px-3 py-1 rounded"><?= hsc($new_creds['password']) ?></span>
                    </div>
                </div>
                <p class="text-emerald-600 text-xs mt-2">Tenant will be prompted to change password on first login.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIDA</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php foreach ($tenants as $t): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900"><?= hsc($t['name']) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"><?= hsc($t['nida'] ?? '-') ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900"><?= hsc($t['phone']) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <a href="?edit=<?= $t['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <a href="?delete=<?= $t['id'] ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:text-red-900">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($tenants) === 0): ?>
                    <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">No tenants found. Add one to get started.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($show_modal && !$edit_tenant): ?>
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Add Tenant</h3>
            <a href="tenants.php" class="text-gray-400 hover:text-gray-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></a>
        </div>
        <div class="p-6">
            <form method="post" class="space-y-4">
                <input type="hidden" name="editing_id" value="">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="name" required value="<?= hsc($form_data['name']) ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">NIDA Number</label>
                    <input type="text" name="nida" value="<?= hsc($form_data['nida']) ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="tel" name="phone" required value="<?= hsc($form_data['phone']) ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-xs text-blue-700">
                    System will auto-generate username &amp; password. Credentials will be shown after creation.
                </div>
                <div class="flex justify-end space-x-3 pt-2">
                    <a href="tenants.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Create Tenant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($edit_tenant): ?>
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Edit Tenant</h3>
            <a href="tenants.php" class="text-gray-400 hover:text-gray-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></a>
        </div>
        <div class="p-6">
            <form method="post" class="space-y-4">
                <input type="hidden" name="editing_id" value="<?= hsc($edit_tenant['id']) ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="name" required value="<?= hsc($edit_tenant['name']) ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">NIDA Number</label>
                    <input type="text" name="nida" value="<?= hsc($edit_tenant['nida'] ?? '') ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="tel" name="phone" required value="<?= hsc($edit_tenant['phone']) ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div class="flex justify-end space-x-3 pt-2">
                    <a href="tenants.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Capture page content and render inside the shared layout
$content = ob_get_clean();
$page_title = 'Tenants';
require __DIR__ . '/includes/layout.php';
