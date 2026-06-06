<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
$sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;

if ($eventId === 0) {
    header('Location: dashboard.php');
    exit();
}

$conn = getConnection();
$eventStmt = $conn->prepare('SELECT event_id FROM events WHERE event_id = ? LIMIT 1');
$eventStmt->bind_param('i', $eventId);
$eventStmt->execute();
$eventExists = $eventStmt->get_result()->fetch_assoc();
$eventStmt->close();

if (!$eventExists) {
    $conn->close();
    header('Location: dashboard.php');
    exit();
}

if ($sessionId === 0) {
    $defaultDriver = $_SESSION['username'] ?? 'Simulator';
    $defaultBestLap = '';

    // load defaults from event_settings
    $s = $conn->prepare('SELECT f1_version, default_car, default_track, default_driver FROM event_settings WHERE event_id = ? LIMIT 1');
    $s->bind_param('i', $eventId);
    $s->execute();
    $defaults = $s->get_result()->fetch_assoc();
    $s->close();

    $defaultGame = $defaults['f1_version'] ?? 'F1 2026';
    $defaultCar = $defaults['default_car'] ?? 'Red Bull RB20';
    $defaultTrack = $defaults['default_track'] ?? 'Silverstone';
    if (!empty($defaults['default_driver'])) $defaultDriver = $defaults['default_driver'];

    $insertStmt = $conn->prepare(
        'INSERT INTO sessions (event_id, participant_name, best_lap_time, f1_version, car, track) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertStmt->bind_param('isssss', $eventId, $defaultDriver, $defaultBestLap, $defaultGame, $defaultCar, $defaultTrack);
    $insertStmt->execute();
    $sessionId = $conn->insert_id;
    $insertStmt->close();

    $conn->close();
    header('Location: simulation.php?event_id=' . $eventId . '&session_id=' . $sessionId);
    exit();
}

$conn->close();

$pageTitle = 'Simulation';
include __DIR__ . '/../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/simulation.css">


<div class="sim-wrapper">

    <h1 class="sim-title">F1 LAP SIMULATOR</h1>
    <p class="sim-subtitle">Session #<?= $sessionId ?> &nbsp;|&nbsp; Event #<?= $eventId ?></p>

    <!-- Track -->
    <div class="track-line-wrapper">
        <span class="flag start">START</span>
        <span class="flag finish">FINISH</span>
        <div class="track-line" id="trackLine">
            <div class="finish-marker"></div>
            <div id="car-dot"></div>
        </div>
    </div>

    <!-- Timer -->
    <div class="timer-display" id="timerDisplay">00:00</div>
    <div class="lap-counter" id="lapCounter">LAP 0</div>

    <!-- Buttons -->
    <div class="sim-controls">
        <button class="btn-sim btn-start" id="startBtn">START</button>
        <button id="btn-complete-lap" disabled>COMPLETE LAP</button>
        <button class="btn-sim btn-end" id="endBtn" disabled>END SESSION</button>
    </div>

    <!-- Lap list -->
    <div class="lap-list">
        <h3>LAP TIMES</h3>
        <div id="lapList"></div>
    </div>

</div>

<script src="../assets/js/simulation.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>