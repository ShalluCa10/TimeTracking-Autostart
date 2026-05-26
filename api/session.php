<?php
// ============================================================
//  api/session.php  —  Python AutoStart POST endpoint (Phase 2)
//  Method: POST
//  Content-Type: application/json
//  Payload: { event_id, participant_name, car, track, best_lap_time, api_key }
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Parse JSON body
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

// Validate API key
$apiKey = $body['api_key'] ?? '';
if (!hash_equals(API_SECRET_KEY, $apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Validate required fields
$required = ['event_id', 'participant_name', 'best_lap_time'];
foreach ($required as $field) {
    if (empty($body[$field])) {
        http_response_code(422);
        echo json_encode(['error' => 'Validation failed', 'missing' => $field]);
        exit;
    }
}

$eventId        = (int)$body['event_id'];
$participantName = trim($body['participant_name']);
$car            = trim($body['car']   ?? '');
$track          = trim($body['track'] ?? '');
$lapTime        = trim($body['best_lap_time']);

// Validate lap time format
if (!isValidLapTime($lapTime)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid lap time format. Use mm:ss.mmm']);
    exit;
}

// Verify event exists
$pdo  = getDB();
$stmt = $pdo->prepare('SELECT event_id FROM events WHERE event_id = ? LIMIT 1');
$stmt->execute([$eventId]);
if (!$stmt->fetch()) {
    http_response_code(422);
    echo json_encode(['error' => 'Event not found']);
    exit;
}

// Insert session
$stmt = $pdo->prepare(
    'INSERT INTO sessions (event_id, participant_name, car, track, best_lap_time, source)
     VALUES (?,?,?,?,?,\'api\')'
);
$stmt->execute([$eventId, $participantName, $car, $track, $lapTime]);
$sessionId = (int)$pdo->lastInsertId();

http_response_code(200);
echo json_encode(['status' => 'ok', 'session_id' => $sessionId]);
