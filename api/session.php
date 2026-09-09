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

/**
 * Spawn-guard only: true when any Windows process already looks like api.py.
 * /status stays the primary readiness check; this only prevents a duplicate
 * spawn while an existing api.py is still booting (or hung).
 */
function isPythonApiProcessRunning(): bool
{
    $ps = "Get-CimInstance Win32_Process | Where-Object { \$_.CommandLine -match 'api\.py' } | Select-Object -First 1 -ExpandProperty ProcessId";
    $output = shell_exec('powershell -NoProfile -NonInteractive -Command ' . quoteForCmd($ps));

    return is_string($output) && trim($output) !== '';
}

/**
 * Log autostart events to the PHP error log AND the configured API log file
 * (best effort) so startup diagnostics live next to the Flask output.
 */
function pythonAutostartLog(string $message): void
{
    $line = '[PYTHON AUTOSTART] ' . $message;
    error_log($line);

    $logFile = defined('AUTOSTART_API_LOG') ? AUTOSTART_API_LOG : '';
    if ($logFile !== '') {
        @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
    }
}

function startPythonApiProcess(): array
{
    $pythonExe = defined('AUTOSTART_PYTHON_EXE') ? AUTOSTART_PYTHON_EXE : '';
    $apiScript = defined('AUTOSTART_API_SCRIPT') ? AUTOSTART_API_SCRIPT : '';
    $logFile = defined('AUTOSTART_API_LOG') ? AUTOSTART_API_LOG : '';

    // Same project copy for exe + script, resolved from config.php (no hardcoded duplicates).
    pythonAutostartLog('python=' . $pythonExe);
    pythonAutostartLog('script=' . $apiScript);
    pythonAutostartLog('log=' . $logFile);

    if ($pythonExe === '' || !file_exists($pythonExe)) {
        return [
            'ok' => false,
            'error' => 'Python executable not found',
            'details' => $pythonExe,
        ];
    }

    if ($apiScript === '' || !file_exists($apiScript)) {
        return [
            'ok' => false,
            'error' => 'Python API script not found',
            'details' => $apiScript,
        ];
    }

    // Windows-safe detached launch:
    //  - start "" /B         -> no visible window, returns immediately
    //  - /D <dir>            -> api.py runs with its own folder as CWD (config.txt etc.)
    //  - >> log 2>&1         -> stdout/stderr appended to the configured log file
    //  - popen() + pclose()  -> PHP waits only for cmd.exe (which exits right after
    //                           'start'), NEVER for api.py. shell_exec() would hang
    //                           here: the detached python inherits PHP's stdout pipe,
    //                           so shell_exec never sees EOF while api.py is alive.
    $command = 'cmd /C start "" /B /D '
        . quoteForCmd(dirname($apiScript))
        . ' '
        . quoteForCmd($pythonExe)
        . ' '
        . quoteForCmd($apiScript)
        . ' >> '
        . quoteForCmd($logFile)
        . ' 2>&1';

    pythonAutostartLog('command=' . $command);

    pclose(popen($command, 'r'));

    return [
        'ok' => true,
        'error' => null,
        'details' => null,
    ];
}

function pollPythonApiStatus(int $attempts, int $sleepMicros, bool $requireReady): bool
{
    for ($i = 0; $i < $attempts; $i++) {
        usleep($sleepMicros);
        $statusCheck = callPython('GET', '/status', null, 1);
        if ($statusCheck['ok'] && (!$requireReady || ($statusCheck['response']['state'] ?? null) === 'READY')) {
            return true;
        }
    }

    return false;
}

function ensurePythonApiRunning(): array
{
    // 1) Primary gate: if /status answers, reuse the existing API. Never spawn.
    $statusCheck = callPython('GET', '/status', null, 1);
    if ($statusCheck['ok']) {
        return [
            'ok' => true,
            'started_now' => false,
            'error' => null,
            'details' => null,
        ];
    }

    // 2) Cross-request mutex: exactly one PHP request acts as the "starter".
    // Concurrent requests skip the lock and simply wait for /status instead.
    $logFile = defined('AUTOSTART_API_LOG') ? AUTOSTART_API_LOG : (sys_get_temp_dir() . '\\python_api.log');
    $lockHandle = fopen($logFile . '.lock', 'c');
    $isStarter = $lockHandle !== false && flock($lockHandle, LOCK_EX | LOCK_NB);

    if (!$isStarter) {
        if ($lockHandle !== false) {
            fclose($lockHandle);
        }

        $becameReady = pollPythonApiStatus(20, 500000, false);

        return $becameReady
            ? ['ok' => true, 'started_now' => false, 'error' => null, 'details' => null]
            : ['ok' => false, 'started_now' => false, 'error' => 'Python API did not become ready in time', 'details' => null];
    }

    try {
        // Re-check inside the lock: a previous request may have started it already.
        $statusCheck = callPython('GET', '/status', null, 1);
        $startedNow = false;

        if (!$statusCheck['ok']) {
            if (isPythonApiProcessRunning()) {
                // api.py exists but is not answering yet (booting or hung).
                // Do NOT spawn a duplicate - just wait for it below.
                pythonAutostartLog('api.py process already exists - waiting instead of spawning a duplicate');
            } else {
                $startAttempt = startPythonApiProcess();
                if (!$startAttempt['ok']) {
                    return [
                        'ok' => false,
                        'started_now' => false,
                        'error' => $startAttempt['error'],
                        'details' => $startAttempt['details'],
                    ];
                }
                $startedNow = true;
            }
        }

        // 3) Bounded readiness wait: continue only when Python reports READY.
        if (pollPythonApiStatus(20, 500000, true)) {
            return [
                'ok' => true,
                'started_now' => $startedNow,
                'error' => null,
                'details' => null,
            ];
        }

        return [
            'ok' => false,
            'started_now' => $startedNow,
            'error' => 'Python API did not become ready in time',
            'details' => null,
        ];
    } finally {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'events';

    // ── GET /api/session.php?action=sessions&schedule_id=1 ──
    if ($action === 'sessions' && !empty($_GET['schedule_id'])) {
        $scheduleId = (int) $_GET['schedule_id'];
        $stmt = $conn->prepare('
            SELECT session_id, schedule_id, f1_version, formula, assist, participant_name,
                   team AS car, event AS track, best_lap_time, timer_minutes, created_at, status
            FROM sessions WHERE schedule_id = ? ORDER BY session_id DESC
        ');
        $stmt->bind_param('i', $scheduleId);
        $stmt->execute();
        $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'sessions' => $sessions]);
        exit();
    }

    // ── GET /api/session.php?action=session&session_id=1 ──
    if ($action === 'session' && !empty($_GET['session_id'])) {
        $sessionId = (int) $_GET['session_id'];

        $stmt = $conn->prepare('
            SELECT session_id, schedule_id, f1_version, formula, assist, participant_name,
                   team, team AS car, event, event AS track, best_lap_time, timer_minutes, created_at, status
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

        $session['duration_seconds'] = !empty($session['timer_minutes']) ? ((int)$session['timer_minutes'] * 60) : 300;

        // Include laps
        $lapsStmt = $conn->prepare('SELECT * FROM laps WHERE session_id = ? ORDER BY lap_number ASC');
        $lapsStmt->bind_param('i', $sessionId);
        $lapsStmt->execute();
        $laps = $lapsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $lapsStmt->close();

        // Include schedule
        $schedule = null;
        if (!empty($session['schedule_id'])) {
            $e = $conn->prepare('SELECT schedule_id, schedule_name, formula, assist, team AS car, event AS track, racer, status FROM schedules WHERE schedule_id = ? LIMIT 1');
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

    // ── GET /api/session.php?action=active / active_session ──
    if ($action === 'active' || $action === 'active_session') {
        $stmt = $conn->prepare("
            SELECT session_id, schedule_id, f1_version, formula, assist, participant_name,
                   team, team AS car, event, event AS track, best_lap_time, timer_minutes, created_at, status
            FROM sessions
            WHERE status IN ('starting', 'running')
            ORDER BY session_id DESC LIMIT 1
        ");
        $stmt->execute();
        $activeSession = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($activeSession) {
            $activeSession['duration_seconds'] = !empty($activeSession['timer_minutes']) ? ((int)$activeSession['timer_minutes'] * 60) : 300;
        }

        echo json_encode([
            'success' => true,
            'active_session' => $activeSession,
        ]);
        exit();
    }

    // ── GET /api/session.php?action=next ──
    if ($action === 'next') {
        $stmt = $conn->prepare('SELECT session_id, schedule_id, f1_version, formula, assist, participant_name, team AS car, event AS track, best_lap_time, created_at, status FROM sessions ORDER BY session_id DESC LIMIT 1');
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

// ── Complete Session Action ──
if (($data['action'] ?? '') === 'complete' && !empty($data['session_id'])) {
    $completeSessionId = (int) $data['session_id'];
    $stmt = $conn->prepare("UPDATE sessions SET status = 'completed' WHERE session_id = ?");
    $stmt->bind_param('i', $completeSessionId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Session marked completed',
        'session_id' => $completeSessionId,
    ]);
    exit();
}

// ── Fail Session Action ──
if (($data['action'] ?? '') === 'fail' && !empty($data['session_id'])) {
    $failSessionId = (int) $data['session_id'];
    $stmt = $conn->prepare("UPDATE sessions SET status = 'failed' WHERE session_id = ?");
    $stmt->bind_param('i', $failSessionId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Session marked failed',
        'session_id' => $failSessionId,
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
    pythonAutostartLog('FAILED: ' . ($pythonReady['error'] ?? 'unknown') . ' ' . ($pythonReady['details'] ?? ''));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Python automation service could not be started.',
        'python_error' => $pythonReady['error'] ?? null,
        'details' => $pythonReady['details'] ?? null,
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| Get team, track, formula and assist from the selected schedule
|--------------------------------------------------------------------------
*/

$scheduleStmt = $conn->prepare(
    'SELECT s.team, s.event, s.racer, s.formula, s.assist, gv.name AS game_version
     FROM schedules s
     LEFT JOIN game_versions gv ON gv.id = s.version_id
     WHERE s.schedule_id = ?
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
$gameVersion = trim($schedule['game_version'] ?? '') ?: $f1Version;

// Validate required automation fields - never silently default formula/assist here;
// an empty value means the schedule itself is misconfigured (see Issue 3 dropdown fix).
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
        'error' => 'Schedule is missing formula (F1/F2). Edit the schedule and re-save it.'
    ]);
    exit();
}

if (empty($assist)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Schedule is missing assist level. Edit the schedule and re-save it.'
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| Check Python status before starting to prevent duplicate sessions
|--------------------------------------------------------------------------
*/

$statusResult = callPython('GET', '/status');

if (!$statusResult['ok']) {
    http_response_code($statusResult['http_code'] >= 400 ? $statusResult['http_code'] : 500);
    echo json_encode([
        'success' => false,
        'error' => $statusResult['error'] ?? 'Python status check failed',
        'python_response' => $statusResult['response'] ?? null,
        'python_raw_response' => $statusResult['raw_response'] ?? null,
        'python_error' => $statusResult['details'] ?? null,
    ]);
    exit();
}

$status = $statusResult['response'];
$pythonState = $status['state'] ?? 'UNKNOWN';

// STARTING / PLAYING / ENDING (or Python reporting running) means a session
// is already active - never start a second one.
if (!empty($status['running']) || in_array($pythonState, ['STARTING', 'PLAYING', 'ENDING'], true)) {
    http_response_code(409);
    echo json_encode([
        'success' => false,
        'error' => 'Rig is currently busy.',
        'python_status' => $status,
    ]);
    exit();
}

/*
|--------------------------------------------------------------------------
| 1. Create session in MySQL
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "INSERT INTO sessions
    (schedule_id, participant_name, f1_version, formula, assist, team, event, best_lap_time, timer_minutes, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 5, 'running')"
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
| 2. Send session information to Python
|--------------------------------------------------------------------------
*/

$pythonPayload = [
    'session_id' => $newId,
    'schedule_id' => $scheduleId,
    'participant_name' => $participantName,
    'game_version' => $gameVersion,
    'f1_version' => $gameVersion,
    'formula' => $formula,
    'assist' => $assist,
    'team' => $car,
    'track' => $track,
    'duration_seconds' => 300,
];

error_log('[SESSION START] ' . json_encode($pythonPayload));

$startResult = callPython('POST', '/start', $pythonPayload);

if (!$startResult['ok']) {
    // Mark session failed in DB
    $failConn = getConnection();
    $failStmt = $failConn->prepare("UPDATE sessions SET status = 'failed' WHERE session_id = ?");
    $failStmt->bind_param('i', $newId);
    $failStmt->execute();
    $failStmt->close();
    $failConn->close();

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
| 3. Return combined result
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'session_id' => $newId,
    'status' => 'running',
    'python' => $startResult['response'],
    'python_status' => $status,
]);
exit();

// echo json_encode(['success' => true, 'session_id' => $newId]);
