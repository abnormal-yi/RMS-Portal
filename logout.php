<?php
/**
 * logout.php
 * Destroys the current user session via the logout() helper, then
 * redirects back to the login page.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
logout();
header('Location: login.php');
exit;
