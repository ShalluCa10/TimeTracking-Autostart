<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/helpers.php';

$type = $_GET['type'] ?? '';
$versionId = (int) ($_GET['version_id'] ?? 0);

if ($versionId === 0 || !in_array($type, ['tracks', 'cars'])) {
    echo json_encode([]);
    exit();
}

$tableMap = [
    'tracks' => 'game_events',
    'cars' => 'game_teams',
];

$table = $tableMap[$type] ?? null;
if (!$table) {
    echo json_encode([]);
    exit();
}

$table = $tableMap[$type];
$conn = getConnection();
ensureGameTables($conn);

$stmt = $conn->prepare("SELECT name FROM `$table` WHERE version_id = ? ORDER BY name ASC");
$stmt->bind_param('i', $versionId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

echo json_encode($rows);
