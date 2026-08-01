<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit();
}

$eventId = (int) ($_POST['event_id'] ?? 0);

if ($eventId === 0) {
    header('Location: ../dashboard.php');
    exit();
}

$conn = getConnection();
$stmt = $conn->prepare('DELETE FROM schedules WHERE schedule_id = ?');
$stmt->bind_param('i', $eventId);
$stmt->execute();
$stmt->close();
$conn->close();

setFlash('success', 'Schedule deleted.');
header('Location: ../dashboard.php');
exit();
