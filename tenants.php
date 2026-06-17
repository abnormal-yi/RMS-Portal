<?php
/**
 * tenants.php
 * Admin CRUD page for managing tenants. Supports listing, adding,
 * editing, and deleting tenant records with a modal form.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();
requireRole('landlord');

// Parse URL parameters for action, edit target, and delete target
$action = $_GET['action'] ?? '';
$edit_id = $_GET['edit'] ?? '';
$delete_id = $_GET['delete'] ?? '';

// Handle delete: remove tenant and redirect back to list
if ($delete_id) {
    db()->prepare("DELETE FROM tenants WHERE id = ?")->execute([$delete_id]);
    header('Location: tenants.php');
    exit;
}

// Handle form submission: create or update a tenant record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $editing_id = $_POST['editing_id'] ?? '';

    if ($editing_id) {
        // Update existing record
        $stmt = db()->prepare("UPDATE tenants SET name=?, email=?, phone=? WHERE id=?");
        $stmt->execute([$name, $email, $phone, $editing_id]);
    } else {
        // Insert new record with a timestamp-based ID
        $id = 't' . round(microtime(true) * 1000);
        $stmt = db()->prepare("INSERT INTO tenants (id, name, email, phone) VALUES (?,?,?,?)");
        $stmt->execute([$id, $name, $email, $phone]);
    }
    header('Location: tenants.php');
    exit;
}

// Fetch all tenants for table display
$tenants = db()->query("SELECT * FROM tenants ORDER BY name")->fetchAll();
// Load tenant data if editing an existing record
$edit_tenant = null;
if ($edit_id) {
    $stmt = db()->prepare("SELECT * FROM tenants WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_tenant = $stmt->fetch();
}

// Determine whether to show the add/edit modal and populate form data
$show_modal = $action === 'add' || $edit_id;
$form_data = $edit_tenant ?: ['id' => '', 'name' => '', 'email' => '', 'phone' => ''];

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

    <!-- Tenants data table with edit/delete action links -->
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php foreach ($tenants as $t): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900"><?= hsc($t['name']) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"><?= hsc($t['email']) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900"><?= hsc($t['phone']) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <a href="?edit=<?= $t['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <a href="?delete=<?= $t['id'] ?>" onclick="return confirm('Are you sure you want to delete this tenant?')" class="text-red-600 hover:text-red-900">
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

<!-- Add / Edit tenant modal overlay -->
<?php if ($show_modal): ?>
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900"><?= $edit_tenant ? 'Edit Tenant' : 'Add Tenant' ?></h3>
            <a href="tenants.php" class="text-gray-400 hover:text-gray-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></a>
        </div>
        <div class="p-6 overflow-y-auto">
            <form method="post" class="space-y-4">
                <input type="hidden" name="editing_id" value="<?= hsc($form_data['id']) ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="name" required value="<?= hsc($form_data['name']) ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="email" required value="<?= hsc($form_data['email']) ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="tel" name="phone" required value="<?= hsc($form_data['phone']) ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <a href="tenants.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Save Tenant
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
