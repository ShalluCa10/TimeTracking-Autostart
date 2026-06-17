<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

$sessionId = (int) ($_POST['session_id'] ?? 0);

if ($sessionId === 0) {
    header('Location: dashboard.php');
    exit();
}

$conn = getConnection();
$stmt = $conn->prepare('DELETE FROM sessions WHERE session_id = ?');
$stmt->bind_param('i', $sessionId);
$stmt->execute();
$stmt->close();
$conn->close();

setFlash('success', 'Session deleted.');

$redirect = trim($_POST['redirect'] ?? '');

$allowed = ['dashboard.php', 'sessions.php', 'manage_events.php'];
$base = strtok($redirect, '?');

if (in_array($base, $allowed) && $redirect === strip_tags($redirect)) {
    header('Location: ' . $redirect);
} else {
    header('Location: dashboard.php');
}
exit();
