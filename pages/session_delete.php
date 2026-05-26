<?php
// pages/session_delete.php  —  POST-only action
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
checkSessionTimeout();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/dashboard.php');
}

verifyCsrf();

$sessionId = (int)($_POST['session_id'] ?? 0);
$eventId   = (int)($_POST['event_id']   ?? 0);

if ($sessionId > 0) {
    $pdo  = getDB();
    $stmt = $pdo->prepare('DELETE FROM sessions WHERE session_id = ? AND event_id = ?');
    $stmt->execute([$sessionId, $eventId]);
    setFlash('success', 'Session deleted.');
} else {
    setFlash('error', 'Invalid session.');
}

redirect('pages/event_detail.php?id=' . $eventId);
