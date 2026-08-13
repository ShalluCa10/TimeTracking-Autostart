<?php
// API endpoint for simulator session lookup
// GET actions:
//   ?action=events
//   ?action=sessions&schedule_id=1
//   ?action=session&session_id=1
//   ?action=next

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'events';

    // ── GET /api/session.php?action=sessions&schedule_id=1 ──
    if ($action === 'sessions' && !empty($_GET['schedule_id'])) {
        $scheduleId = (int) $_GET['schedule_id'];
        $stmt = $conn->prepare('
            SELECT session_id, schedule_id, f1_version, participant_name,
                   team AS car, event AS track, best_lap_time, created_at
            FROM sessions WHERE schedule_id = ? ORDER BY session_id DESC
        ');
        $stmt->bind_param('i', $scheduleId);
        $stmt->execute();
        $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'sessions' => $sessions]);
        exit();
    }

  
    if ($action === 'session' && !empty($_GET['session_id'])) {
        $sessionId = (int) $_GET['session_id'];

        $stmt = $conn->prepare('
            SELECT session_id, schedule_id, f1_version, participant_name,
                   team AS car, event AS track, best_lap_time, created_at
            FROM sessions WHERE session_id = ? LIMIT 1
        ');
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

        // Include schedule
        $schedule = null;
        if (!empty($session['schedule_id'])) {
            $e = $conn->prepare('SELECT schedule_id, schedule_name, team AS car, event AS track, racer FROM schedules WHERE schedule_id = ? LIMIT 1');
            $e->bind_param('i', $session['schedule_id']);
            $e->execute();
            $schedule = $e->get_result()->fetch_assoc();
            $e->close();
        }

        echo json_encode([
            'success' => true,
            'session' => $session,
            'laps' => $laps,
            'schedule' => $schedule,
        ]);
        exit();
    }

    
    if ($action === 'next') {
        $stmt = $conn->prepare('SELECT session_id, schedule_id, f1_version, participant_name, team AS car, event AS track, best_lap_time, created_at FROM sessions ORDER BY session_id DESC LIMIT 1');
        $stmt->execute();
        $nextSession = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$nextSession) {
            echo json_encode(['success' => false, 'message' => 'No sessions found']);
            exit();
        }

        $schedule = null;
        if (!empty($nextSession['schedule_id'])) {
            $e = $conn->prepare('SELECT schedule_id, schedule_name, team AS car, event AS track, racer FROM schedules WHERE schedule_id = ? LIMIT 1');
            $e->bind_param('i', $nextSession['schedule_id']);
            $e->execute();
            $schedule = $e->get_result()->fetch_assoc();
            $e->close();
        }

        echo json_encode(['success' => true, 'next' => $nextSession, 'schedule' => $schedule]);
        exit();
    }

    $result = $conn->query('SELECT schedule_id, schedule_name, schedule_date, team AS car, event AS track, racer, status FROM schedules ORDER BY schedule_date DESC');
    $schedules = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'schedules' => $schedules]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (($data['api_key'] ?? '') !== 'changeme123') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit();
}

$scheduleId = (int) ($data['schedule_id'] ?? 0);
$participantName = trim($data['participant_name'] ?? '');
$f1Version = trim($data['f1_version'] ?? '');
$bestLapTime = trim($data['best_lap_time'] ?? '');

if ($scheduleId === 0 || $participantName === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'schedule_id and participant_name are required'
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| Get team and track from the selected schedule
|--------------------------------------------------------------------------
*/

$scheduleStmt = $conn->prepare(
    'SELECT team, event, racer
     FROM schedules
     WHERE schedule_id = ?
     LIMIT 1'
);

$scheduleStmt->bind_param('i', $scheduleId);
$scheduleStmt->execute();

$schedule = $scheduleStmt->get_result()->fetch_assoc();

$scheduleStmt->close();

if (!$schedule) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Schedule not found'
    ]);
    exit();
}

$car = trim($schedule['team'] ?? '');
$track = trim($schedule['event'] ?? '');

/*
|--------------------------------------------------------------------------
| 1. Create session in MySQL
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    'INSERT INTO sessions
    (schedule_id, participant_name, f1_version, team, event, best_lap_time)
    VALUES (?, ?, ?, ?, ?, ?)'
);

$stmt->bind_param(
    'isssss',
    $scheduleId,
    $participantName,
    $f1Version,
    $car,
    $track,
    $bestLapTime
);

$stmt->execute();

$newId = $stmt->insert_id;

$stmt->close();


/*
|--------------------------------------------------------------------------
| 2. Send session information to Python
|--------------------------------------------------------------------------
*/

$pythonPayload = [
    'session_id' => $newId,
    'schedule_id' => $scheduleId,
    'participant_name' => $participantName,
    'f1_version' => $f1Version,
    'team' => $car,
    'track' => $track
];


$ch = curl_init('http://127.0.0.1:5000/start');

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode($pythonPayload)
);

curl_setopt($ch, CURLOPT_TIMEOUT, 5);


$pythonResponse = curl_exec($ch);


/*
|--------------------------------------------------------------------------
| 3. Handle Python connection error
|--------------------------------------------------------------------------
*/

if ($pythonResponse === false) {

    $pythonError = curl_error($ch);

    curl_close($ch);
    $conn->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'session_id' => $newId,
        'error' => 'Session was created, but Python could not be reached',
        'python_error' => $pythonError
    ]);

    exit();
}


$pythonHttpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

curl_close($ch);

$conn->close();


/*
|--------------------------------------------------------------------------
| 4. Return combined result
|--------------------------------------------------------------------------
*/

$pythonData = json_decode($pythonResponse, true);

if ($pythonHttpCode >= 400) {

    http_response_code($pythonHttpCode);

    echo json_encode([
        'success' => false,
        'session_id' => $newId,
        'error' => 'Python returned an error',
        'python_response' => $pythonData
    ]);

    exit();
}


echo json_encode([
    'success' => true,
    'session_id' => $newId,
    'python' => $pythonData
]);
// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     http_response_code(405);
//     echo json_encode(['error' => 'Method not allowed']);
//     exit();
// }

// $data = json_decode(file_get_contents('php://input'), true);

// if (($data['api_key'] ?? '') !== 'changeme123') {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized']);
//     exit();
// }

// $scheduleId = (int) ($data['schedule_id'] ?? 0);
// $participantName = trim($data['participant_name'] ?? '');
// $f1Version = trim($data['f1_version'] ?? '');
// $car = trim($data['car'] ?? '');
// $track = trim($data['track'] ?? '');
// $bestLapTime = trim($data['best_lap_time'] ?? ''); 

// if ($scheduleId === 0 || $participantName === '') {
//     http_response_code(400);
//     echo json_encode(['error' => 'schedule_id and participant_name are required']);
//     exit();
// }

// $stmt = $conn->prepare(
//     'INSERT INTO sessions (schedule_id, participant_name, f1_version, team, event, best_lap_time) VALUES (?, ?, ?, ?, ?, ?)'
// );
// $stmt->bind_param('isssss', $scheduleId, $participantName, $f1Version, $car, $track, $bestLapTime);
// $stmt->execute();
// $newId = $stmt->insert_id;
// $stmt->close();
// $conn->close();

// echo json_encode(['success' => true, 'session_id' => $newId]);
