<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

$table = $_POST['table'] ?? '';
$id = (int) ($_POST['id'] ?? 0);
$allowed = ['game_tracks', 'game_cars', 'game_racers'];

if (!in_array($table, $allowed) || $id <= 0) {
  echo json_encode(['error' => 'Invalid table or id.']);
  exit;
}

if (empty($_FILES['image']['tmp_name'])) {
  echo json_encode(['error' => 'No file uploaded.']);
  exit;
}

$file = $_FILES['image'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

if (!in_array($ext, $allowed_ext)) {
  echo json_encode(['error' => 'Invalid file type.']);
  exit;
}

if ($file['size'] > 2 * 1024 * 1024) {
  echo json_encode(['error' => 'File too large (max 2MB).']);
  exit;
}

$uploadDir = __DIR__ . '/../assets/uploads/';
if (!is_dir($uploadDir))
  mkdir($uploadDir, 0755, true);

$filename = $table . '_' . $id . '_' . time() . '.' . $ext;
$dest = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
  echo json_encode(['error' => 'Upload failed.']);
  exit;
}

$conn = getConnection();
$stmt = $conn->prepare("UPDATE `$table` SET image = ? WHERE id = ?");
$path = '/assets/uploads/' . $filename;
$stmt->bind_param('si', $path, $id);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'path' => $path]);
