<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

requireAuth();
$user = getCurrentUser();

if ($user['role'] === 'admin') {
    require __DIR__ . '/dashboard.php';
} else {
    require __DIR__ . '/tenant_dashboard.php';
}
