<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

requireLogin();

$id = (int) ($_POST['event_id'] ?? 0);
$status = $_POST['status'] ?? '';
$redirect = $_POST['redirect'] ?? '../dashboard.php';

$allowed_redirects = ['../dashboard.php', 'manage_schedules.php'];
if (!in_array($redirect, $allowed_redirects)) {
    $redirect = '../dashboard.php';
}

if ($id && in_array($status, ['auto', 'live', 'completed'])) {
    $conn = getConnection();
    $stmt = $conn->prepare('UPDATE schedules SET status = ? WHERE schedule_id = ?');
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    setFlash('success', 'Schedule status updated.');
}

header('Location: ' . $redirect);
exit;
