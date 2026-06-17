<?php
// API endpoint for simulator session lookup
// GET actions:
//   ?action=events
//   ?action=sessions&event_id=1
//   ?action=session&session_id=1
//   ?action=next

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'events';

    // ── GET /api/session.php?action=sessions&event_id=1 ──
    if ($action === 'sessions' && !empty($_GET['event_id'])) {
        $eventId = (int) $_GET['event_id'];
        $stmt = $conn->prepare('SELECT * FROM sessions WHERE event_id = ? ORDER BY session_id DESC');
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'sessions' => $sessions]);
        exit();
    }

  
    if ($action === 'session' && !empty($_GET['session_id'])) {
        $sessionId = (int) $_GET['session_id'];

        $stmt = $conn->prepare('SELECT * FROM sessions WHERE session_id = ? LIMIT 1');
        $stmt->bind_param('i', $sessionId);
        $stmt->execute();
        $session = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$session) {
            echo json_encode(['success' => false, 'error' => 'Session not found']);
            exit();
        }

        // Include laps
        $lapsStmt = $conn->prepare('SELECT * FROM laps WHERE session_id = ? ORDER BY lap_number ASC');
        $lapsStmt->bind_param('i', $sessionId);
        $lapsStmt->execute();
        $laps = $lapsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $lapsStmt->close();

        // Include event
        $event = null;
        if (!empty($session['event_id'])) {
            $e = $conn->prepare('SELECT * FROM events WHERE event_id = ? LIMIT 1');
            $e->bind_param('i', $session['event_id']);
            $e->execute();
            $event = $e->get_result()->fetch_assoc();
            $e->close();
        }

        echo json_encode([
            'success' => true,
            'session' => $session,
            'laps' => $laps,
            'event' => $event,
        ]);
        exit();
    }

    
    if ($action === 'next') {
        $stmt = $conn->prepare('SELECT * FROM sessions ORDER BY session_id DESC LIMIT 1');
        $stmt->execute();
        $nextSession = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$nextSession) {
            echo json_encode(['success' => false, 'message' => 'No sessions found']);
            exit();
        }

        $event = null;
        if (!empty($nextSession['event_id'])) {
            $e = $conn->prepare('SELECT * FROM events WHERE event_id = ? LIMIT 1');
            $e->bind_param('i', $nextSession['event_id']);
            $e->execute();
            $event = $e->get_result()->fetch_assoc();
            $e->close();
        }

        echo json_encode(['success' => true, 'next' => $nextSession, 'event' => $event]);
        exit();
    }

    $result = $conn->query('SELECT * FROM events ORDER BY event_date DESC');
    $events = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'events' => $events]);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (($data['api_key'] ?? '') !== 'changeme123') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$eventId = (int) ($data['event_id'] ?? 0);
$participantName = trim($data['participant_name'] ?? '');
$f1Version = trim($data['f1_version'] ?? '');
$car = trim($data['car'] ?? '');
$track = trim($data['track'] ?? '');
$bestLapTime = trim($data['best_lap_time'] ?? ''); 

if ($eventId === 0 || $participantName === '') {
    http_response_code(400);
    echo json_encode(['error' => 'event_id and participant_name are required']);
    exit();
}

$stmt = $conn->prepare(
    'INSERT INTO sessions (event_id, participant_name, f1_version, car, track, best_lap_time) VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('isssss', $eventId, $participantName, $f1Version, $car, $track, $bestLapTime);
$stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'session_id' => $newId]);
