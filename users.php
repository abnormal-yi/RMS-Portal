<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();
requireRole('admin');

$action = $_GET['action'] ?? '';
$target_id = $_GET['id'] ?? '';

if ($action === 'approve' && $target_id) {
    if (approveLandlord($target_id)) {
        header('Location: users.php?msg=approved');
    } else {
        header('Location: users.php?msg=error');
    }
    exit;
}

if ($action === 'delete' && $target_id) {
    $stmt = db()->prepare("SELECT role FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$target_id]);
    if ($stmt->fetch()) {
        db()->prepare("DELETE FROM users WHERE id = ?")->execute([$target_id]);
    }
    header('Location: users.php');
    exit;
}

$msg = $_GET['msg'] ?? '';
$pending = db()->query("SELECT * FROM users WHERE role = 'landlord' AND approved = 0 ORDER BY created_at DESC")->fetchAll();
$all_users = db()->query("SELECT * FROM users ORDER BY FIELD(role, 'admin', 'landlord', 'tenant'), username")->fetchAll();

$role_colors = [
    'admin' => 'bg-purple-100 text-purple-800',
    'landlord' => 'bg-blue-100 text-blue-800',
    'tenant' => 'bg-green-100 text-green-800',
];

ob_start();
?>
<div class="space-y-8">
    <?php if ($msg === 'approved'): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">✓ Landlord approved and property created successfully.</div>
    <?php elseif ($msg === 'error'): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">✗ Could not approve landlord. Ensure they have a property address.</div>
    <?php endif; ?>

    <?php if (count($pending) > 0): ?>
    <div>
        <h2 class="text-xl font-bold text-gray-900 mb-4">Pending Landlord Approvals</h2>
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIDA</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Property Address</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach ($pending as $p): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900"><?= hsc($p['full_name'] ?: $p['username']) ?></td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"><?= hsc($p['nida'] ?? '—') ?></td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"><?= hsc($p['phone'] ?? '—') ?></td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"><?= hsc($p['email'] ?? '—') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs break-words"><?= hsc($p['property_address'] ?? '—') ?></td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <a href="?action=approve&id=<?= $p['id'] ?>"
                                   onclick="return confirm('Approve <?= hsc($p['full_name'] ?: $p['username']) ?> and create their property?')"
                                   class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs font-medium">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Approve
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div>
        <h2 class="text-xl font-bold text-gray-900 mb-4">All System Users</h2>
        <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach ($all_users as $u): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900"><?= hsc($u['username']) ?></td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"><?= hsc($u['full_name'] ?? '—') ?></td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $role_colors[$u['role']] ?? 'bg-gray-100 text-gray-800' ?>">
                                    <?= hsc(ucfirst($u['role'])) ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <?php if ($u['approved']): ?>
                                <span class="text-green-600 font-medium">Yes</span>
                                <?php else: ?>
                                <span class="text-yellow-600 font-medium">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <?php if ($u['role'] !== 'admin'): ?>
                                <a href="?action=delete&id=<?= $u['id'] ?>" onclick="return confirm('Delete user <?= hsc($u['username']) ?>?')" class="text-red-600 hover:text-red-900">
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
</div>

<?php
$content = ob_get_clean();
$page_title = 'System Users';
require __DIR__ . '/includes/layout.php';
