<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
$conn = getConnection();
ensureGameTables($conn);

$action = $_GET['action'] ?? 'versions';

if ($action === 'versions' || $action === 'games') {
    $versions = $conn->query('SELECT * FROM game_versions ORDER BY name ASC')->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'versions' => $versions]);
    exit();
}

if ($action === 'version' && !empty($_GET['version_id'])) {
    $versionId = (int) $_GET['version_id'];

    $stmt = $conn->prepare('SELECT * FROM game_versions WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $versionId);
    $stmt->execute();
    $version = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$version) {
        echo json_encode(['success' => false, 'message' => 'Version not found']);
        exit();
    }

    $stmt = $conn->prepare('SELECT id, name FROM game_teams WHERE version_id = ? ORDER BY sort_order ASC, name ASC');
    $stmt->bind_param('i', $versionId);
    $stmt->execute();
    $cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT id, name FROM game_events WHERE version_id = ? ORDER BY sort_order ASC, name ASC');
    $stmt->bind_param('i', $versionId);
    $stmt->execute();
    $tracks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'game' => $version, 'cars' => $cars, 'tracks' => $tracks]);
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit();
