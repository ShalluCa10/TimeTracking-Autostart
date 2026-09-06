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

function callPython(string $method, string $path, ?array $payload = null, int $timeoutSeconds = 5): array
{
    $method = strtoupper($method);
    $url = 'http://127.0.0.1:5000' . $path;
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => max(1, min(2, $timeoutSeconds)),
        CURLOPT_TIMEOUT => max(1, $timeoutSeconds),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_CUSTOMREQUEST => $method,
    ]);

    if ($method === 'POST' && $payload !== null) {
        $body = json_encode($payload);
        if ($body === false) {
            curl_close($ch);
            return [
                'ok' => false,
                'http_code' => 0,
                'error' => 'Python payload could not be encoded as JSON',
                'details' => json_last_error_msg(),
                'response' => null,
                'raw_response' => null,
            ];
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $rawResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($rawResponse === false) {
        return [
            'ok' => false,
            'http_code' => 500,
            'error' => 'Python could not be reached',
            'details' => $curlError !== '' ? $curlError : 'cURL request failed',
            'raw_response' => null,
            'response' => null,
        ];
    }

    $decoded = json_decode($rawResponse, true);

    if ($httpCode >= 400 || !is_array($decoded)) {
        return [
            'ok' => false,
            'http_code' => $httpCode >= 400 ? $httpCode : 500,
            'error' => $httpCode >= 400 ? 'Python returned an HTTP error' : 'Python returned invalid JSON',
            'details' => null,
            'raw_response' => $rawResponse,
            'response' => is_array($decoded) ? $decoded : null,
        ];
    }

    return [
        'ok' => true,
        'http_code' => $httpCode,
        'error' => null,
        'details' => null,
        'raw_response' => $rawResponse,
        'response' => $decoded,
    ];
}

function quoteForCmd(string $value): string
{
    return '"' . str_replace('"', '""', $value) . '"';
}

function startPythonApiProcess(): array
{
    $pythonExe = defined('AUTOSTART_PYTHON_EXE') ? AUTOSTART_PYTHON_EXE : '';
    $apiScript = defined('AUTOSTART_API_SCRIPT') ? AUTOSTART_API_SCRIPT : '';
    $logFile = defined('AUTOSTART_API_LOG') ? AUTOSTART_API_LOG : '';

    if (!file_exists($pythonExe)) {
        return [
            'ok' => false,
            'error' => 'Python executable not found',
            'details' => $pythonExe,
        ];
    }

    if (!file_exists($apiScript)) {
        return [
            'ok' => false,
            'error' => 'Python API script not found',
            'details' => $apiScript,
        ];
    }

    $command = 'cmd /C start "" /B '
        . quoteForCmd($pythonExe)
        . ' '
        . quoteForCmd($apiScript)
        . ' >> '
        . quoteForCmd($logFile)
        . ' 2>&1';

    shell_exec($command);

    return [
        'ok' => true,
        'error' => null,
        'details' => null,
    ];
}

function ensurePythonApiRunning(): array
{
    $statusCheck = callPython('GET', '/status', null, 1);
    if ($statusCheck['ok']) {
        return [
            'ok' => true,
            'started_now' => false,
            'error' => null,
            'details' => null,
        ];
    }

    $startAttempt = startPythonApiProcess();
    if (!$startAttempt['ok']) {
        return [
            'ok' => false,
            'started_now' => false,
            'error' => $startAttempt['error'],
            'details' => $startAttempt['details'],
        ];
    }

    for ($i = 0; $i < 8; $i++) {
        usleep(250000);
        $statusCheck = callPython('GET', '/status', null, 1);
        if ($statusCheck['ok']) {
            return [
                'ok' => true,
                'started_now' => true,
                'error' => null,
                'details' => null,
            ];
        }
    }

    return [
        'ok' => false,
        'started_now' => true,
        'error' => 'Python API did not become ready in time',
        'details' => null,
    ];
}
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

    if ($action === 'status') {
        $statusResult = callPython('GET', '/status');

        if (!$statusResult['ok']) {
            // Don't auto-start Python on status polling; only report the status.
            // This prevents duplicate api.py processes from spawning during regular polling.
            http_response_code(503);
            echo json_encode([
                'success' => false,
                'error' => 'Python service is unavailable',
                'running' => false,
                'state' => 'ERROR',
                'session_id' => null,
                'remaining_seconds' => 0,
            ]);
            exit();
        }

        echo json_encode([
            'success' => true,
            'status' => $statusResult['response'],
        ]);
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

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Request body must be valid JSON'
    ]);
    exit();
}

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

$pythonReady = ensurePythonApiRunning();
if (!$pythonReady['ok']) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $pythonReady['error'] ?? 'Could not start Python API',
        'python_error' => $pythonReady['details'] ?? null,
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| Get team, track, formula and assist from the selected schedule
|--------------------------------------------------------------------------
*/

$scheduleStmt = $conn->prepare(
    'SELECT team, event, racer, formula, assist
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
$formula = trim($schedule['formula'] ?? '');
$assist = trim($schedule['assist'] ?? '');

// Validate required automation fields
if (empty($car)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Schedule is missing team'
    ]);
    exit();
}

if (empty($track)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Schedule is missing event/track'
    ]);
    exit();
}

if (empty($formula)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Schedule is missing formula'
    ]);
    exit();
}

if (empty($assist)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Schedule is missing assist level'
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| 1. Create session in MySQL
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    'INSERT INTO sessions
    (schedule_id, participant_name, f1_version, formula, assist, team, event, best_lap_time)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);

$stmt->bind_param(
    'isssssss',
    $scheduleId,
    $participantName,
    $f1Version,
    $formula,
    $assist,
    $car,
    $track,
    $bestLapTime
);

$stmt->execute();

$newId = $stmt->insert_id;

$stmt->close();
$conn->close();

/*
|--------------------------------------------------------------------------
| 2. Check Python status before starting the session
|--------------------------------------------------------------------------
*/

$statusResult = callPython('GET', '/status');

if (!$statusResult['ok']) {
    http_response_code($statusResult['http_code'] >= 400 ? $statusResult['http_code'] : 500);
    echo json_encode([
        'success' => false,
        'session_id' => $newId,
        'error' => $statusResult['error'] ?? 'Python status check failed',
        'python_response' => $statusResult['response'] ?? null,
        'python_raw_response' => $statusResult['raw_response'] ?? null,
        'python_error' => $statusResult['details'] ?? null,
    ]);
    exit();
}

$status = $statusResult['response'];

if (($status['state'] ?? null) !== 'READY' || !empty($status['running'])) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'session_id' => $newId,
        'error' => 'Python reports the rig is busy',
        'python_status' => $status,
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| 3. Send session information to Python
|--------------------------------------------------------------------------
*/

$pythonPayload = [
    'session_id' => $newId,
    'schedule_id' => $scheduleId,
    'participant_name' => $participantName,
    'f1_version' => $f1Version,
    'formula' => $formula,
    'assist' => $assist,
    'team' => $car,
    'track' => $track,
    'duration_seconds' => 300,
];

$startResult = callPython('POST', '/start', $pythonPayload);

if (!$startResult['ok']) {
    http_response_code($startResult['http_code'] >= 400 ? $startResult['http_code'] : 500);
    echo json_encode([
        'success' => false,
        'session_id' => $newId,
        'error' => $startResult['error'] ?? 'Python start request failed',
        'python_status' => $status,
        'python_response' => $startResult['response'] ?? null,
        'python_raw_response' => $startResult['raw_response'] ?? null,
        'python_error' => $startResult['details'] ?? null,
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| 4. Return combined result
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'session_id' => $newId,
    'python' => $startResult['response'],
    'python_status' => $status,
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
