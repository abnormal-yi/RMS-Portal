<?php
/**
 * index.php
 * Landing page after login. Enforces authentication and routes users
 * to the admin dashboard or tenant dashboard based on their role.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Verify user is logged in; redirects to login.php if not
requireAuth();
$user = getCurrentUser();

// Route to appropriate dashboard based on user role
if ($user['role'] === 'admin') {
    require __DIR__ . '/dashboard.php';
} elseif ($user['role'] === 'landlord') {
    require __DIR__ . '/landlord_dashboard.php';
} else {
    require __DIR__ . '/tenant_dashboard.php';
}
