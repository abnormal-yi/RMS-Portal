<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();
requireAnyRole(['admin', 'landlord']);

$delete_id = $_GET['delete'] ?? '';

if ($delete_id) {
    $stmt = db()->prepare("SELECT id FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$delete_id]);
    if ($stmt->fetch()) {
        db()->prepare("DELETE FROM users WHERE id = ?")->execute([$delete_id]);
    }
    header('Location: users.php');
    exit;
}

$users = db()->query("SELECT id, username, role, tenant_id, created_at FROM users ORDER BY FIELD(role, 'admin', 'landlord', 'tenant'), username")->fetchAll();

$role_colors = [
    'admin' => 'bg-purple-100 text-purple-800',
    'landlord' => 'bg-blue-100 text-blue-800',
    'tenant' => 'bg-green-100 text-green-800',
];

ob_start();
?>
<div class="space-y-6">
    <div class="sm:flex sm:items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">System Users</h1>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900"><?= hsc($u['username']) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $role_colors[$u['role']] ?? 'bg-gray-100 text-gray-800' ?>">
                                <?= hsc(ucfirst($u['role'])) ?>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"><?= hsc($u['tenant_id'] ?: '—') ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <?php if ($u['role'] !== 'admin'): ?>
                            <a href="?delete=<?= $u['id'] ?>" onclick="return confirm('Delete user <?= hsc($u['username']) ?>?')" class="text-red-600 hover:text-red-900">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = 'System Users';
require __DIR__ . '/includes/layout.php';
