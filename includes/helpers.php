<?php
// ============================================================
//  includes/helpers.php  —  Utility functions
// ============================================================

/**
 * Sanitise output for HTML display.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Validate lap time format mm:ss.mmm
 */
function isValidLapTime(string $time): bool {
    return (bool) preg_match(LAP_TIME_REGEX, $time);
}

/**
 * Flash message helpers — store one message in session, read it once.
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render the flash banner HTML (call inside page body).
 */
function renderFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $cls = $flash['type'] === 'success' ? 'alert-success' : 'alert-error';
    echo '<div class="alert ' . $cls . '">' . e($flash['message']) . '</div>';
}

/**
 * Redirect helper.
 */
function redirect(string $path): void {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

/**
 * Simple CSRF token generation and validation.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token mismatch.');
    }
}
