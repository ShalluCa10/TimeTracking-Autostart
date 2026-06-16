<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$id       = (int) ($_POST['event_id'] ?? 0);
$status   = $_POST['status'] ?? '';
$redirect = $_POST['redirect'] ?? 'dashboard.php';

$allowed_redirects = ['dashboard.php', 'manage_events.php'];
if (!in_array($redirect, $allowed_redirects)) {
    $redirect = 'dashboard.php';
}

if ($id && in_array($status, ['auto', 'live', 'completed'])) {
    $conn = getConnection();
    $stmt = $conn->prepare('UPDATE events SET status = ? WHERE event_id = ?');
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    setFlash('success', 'Event status updated.');
}

header('Location: ../pages/' . $redirect);
exit;
