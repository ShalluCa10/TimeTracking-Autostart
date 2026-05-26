<?php
// ============================================================
//  includes/auth.php  —  Authentication helpers
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Require a valid session; redirect to login if absent.
 */
function requireLogin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/index.php?msg=session_expired');
        exit;
    }
}

/**
 * Attempt login. Returns true on success, false on failure.
 */
function attemptLogin(string $username, string $password): bool {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT admin_id, password_hash FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row  = $stmt->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']  = $row['admin_id'];
        $_SESSION['username']  = $username;
        $_SESSION['last_active'] = time();
        return true;
    }
    return false;
}

/**
 * Destroy the current session (logout).
 */
function logout(): void {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php?msg=logged_out');
    exit;
}

/**
 * Check session expiry on every authenticated page.
 */
function checkSessionTimeout(): void {
    if (!empty($_SESSION['last_active']) &&
        (time() - $_SESSION['last_active']) > SESSION_LIFETIME) {
        logout();
    }
    $_SESSION['last_active'] = time();
}
