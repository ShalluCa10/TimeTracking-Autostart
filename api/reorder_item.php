<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

$allowed = ['game_tracks', 'game_cars', 'game_racers'];
$table = $_POST['table'] ?? '';
$ids = $_POST['ids'] ?? [];

if (!in_array($table, $allowed) || !is_array($ids)) {
  echo json_encode(['ok' => false, 'error' => 'Invalid input']);
  exit();
}

$conn = getConnection();
$stmt = $conn->prepare("UPDATE `$table` SET sort_order = ? WHERE id = ?");

foreach ($ids as $order => $id) {
  $order = (int) $order;
  $id = (int) $id;
  $stmt->bind_param('ii', $order, $id);
  $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(['ok' => true]);
