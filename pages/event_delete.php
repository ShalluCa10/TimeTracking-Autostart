<?php
// pages/event_delete.php  —  POST-only action
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

$eventId = (int)($_POST['event_id'] ?? 0);
if ($eventId > 0) {
    $pdo  = getDB();
    $stmt = $pdo->prepare('DELETE FROM events WHERE event_id = ?');
    $stmt->execute([$eventId]);
    setFlash('success', 'Event deleted.');
} else {
    setFlash('error', 'Invalid event.');
}

redirect('pages/dashboard.php');
