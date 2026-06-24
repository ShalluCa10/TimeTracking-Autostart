<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
// Public API: do not require admin login for creating sessions or saving laps

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$action = $data['action'] ?? 'create';

$conn = getConnection();

// CREATE SESSION 
if ($action === 'create') {
    $eventId = (int) ($data['event_id'] ?? 0);

    if ($eventId === 0) {
        echo json_encode(['error' => 'event_id is required.']);
        exit();
    }

    // Pull selected options from the event
    $stmt = $conn->prepare('
        SELECT e.car, e.track, e.racer, gv.name AS f1_version
        FROM events e
        LEFT JOIN game_versions gv ON gv.id = e.version_id
        WHERE e.event_id = ?
        LIMIT 1
    ');
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$event) {
        echo json_encode(['error' => 'Event not found.']);
        exit();
    }

    $car = $event['car'] ?? '';
    $track = $event['track'] ?? '';
    $racer = $event['racer'] ?? '';
    $f1Version = $event['f1_version'] ?? '';

    $stmt = $conn->prepare('
        INSERT INTO sessions (event_id, participant_name, f1_version, car, track, best_lap_time)
        VALUES (?, ?, ?, ?, ?, \'\')
    ');
    $stmt->bind_param('issss', $eventId, $racer, $f1Version, $car, $track);
    $stmt->execute();
    $sessionId = $stmt->insert_id;
    $stmt->close();
    $conn->close();

    echo json_encode(['session_id' => $sessionId]);
    exit();
}

// SAVE LAP
if ($action === 'save_lap') {
    $sessionId = (int) ($data['session_id'] ?? 0);
    $lapNumber = (int) ($data['lap_number'] ?? 0);
    $lapTimeMs = (int) ($data['lap_time_ms'] ?? 0);
    $lapTime = $data['lap_time'] ?? '';

    if ($sessionId === 0) {
        echo json_encode(['error' => 'session_id required.']);
        exit();
    }

    $stmt = $conn->prepare('
        INSERT INTO laps (session_id, lap_number, lap_time_ms, lap_time)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE lap_time_ms = VALUES(lap_time_ms), lap_time = VALUES(lap_time)
    ');
    $stmt->bind_param('iiis', $sessionId, $lapNumber, $lapTimeMs, $lapTime);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode(['ok' => true]);
    exit();
}

// SAVE BEST LAP
if ($action === 'save_best_lap') {
    $sessionId = (int) ($data['session_id'] ?? 0);
    $bestLapTime = $data['best_lap_time'] ?? '';

    if ($sessionId === 0) {
        echo json_encode(['error' => 'session_id required.']);
        exit();
    }

    $stmt = $conn->prepare('UPDATE sessions SET best_lap_time = ? WHERE session_id = ?');
    $stmt->bind_param('si', $bestLapTime, $sessionId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode(['ok' => true]);
    exit();
}

echo json_encode(['error' => 'Unknown action.']);
