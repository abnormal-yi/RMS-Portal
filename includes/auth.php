<?php
/*----------------------------------------------------------------------
  auth.php  —  Authentication helper functions
  Provides login, logout, session-checking, and role-based access
  control for the Rental Management System.
----------------------------------------------------------------------*/

require_once __DIR__ . '/../config/database.php';

/**
 * isLoggedIn()  —  Check whether the current user has an active session.
 * Returns true if the user_id key exists in the session.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * getCurrentUser()  —  Fetch the authenticated user's record from the DB.
 * Returns an associative array (id, username, role, tenant_id) or null.
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $stmt = db()->prepare("SELECT id, username, role, tenant_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * requireAuth()  —  Redirect to the login page if the user is not
 * authenticated. Call this at the top of any protected page.
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * requireRole()  —  Redirect to the dashboard if the user does not have
 * the specified role (e.g. 'admin' or 'tenant').
 */
function requireRole(string $role): void {
    $user = getCurrentUser();
    if (!$user || $user['role'] !== $role) {
        header('Location: index.php');
        exit;
    }
}

/**
 * requireAnyRole()  —  Allow multiple roles to access a page.
 * Redirects to index.php if the user's role is not in the list.
 */
function requireAnyRole(array $roles): void {
    $user = getCurrentUser();
    if (!$user || !in_array($user['role'], $roles)) {
        header('Location: index.php');
        exit;
    }
}

/**
 * login()  —  Attempt to authenticate a user by username and password.
 * On success, populates session variables and returns true.
 * On failure, returns false.
 */
function login(string $username, string $password): bool {
    $stmt = db()->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['tenant_id'] = $user['tenant_id'];
        return true;
    }
    return false;
}

/**
 * logout()  —  Destroy the session and effectively log the user out.
 */
function logout(): void {
    session_destroy();
}
