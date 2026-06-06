<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
$conn = getConnection();
ensureGameTables($conn);

$eventId   = isset($_POST['event_id']) ? (int)$_POST['event_id'] : (int)(isset($_GET['event_id']) ? $_GET['event_id'] : 0);
$sessionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit    = $sessionId > 0;

$events = [];
$res = $conn->query('SELECT event_id, event_name FROM events ORDER BY event_date DESC');
if ($res) {
    $events = $res->fetch_all(MYSQLI_ASSOC);
}

$event = null;
$eventDefaults = null;
$gameName = '';
$car = '';
$track = '';
$driver = '';
$error = '';
$participantName = '';
$bestLapTime = '';

if ($eventId > 0) {
    $eventStmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
    $eventStmt->bind_param("i", $eventId);
    $eventStmt->execute();
    $event = $eventStmt->get_result()->fetch_assoc();
    $eventStmt->close();

    if ($event) {
        $eventDefaults = getEventGameDefaults($conn, $eventId);
        if ($eventDefaults) {
            $game = getGameItemById($conn, 'games', 'game_id', $eventDefaults['game_id']);
            $gameName = $game['name'] ?? '';
            $car = getGameItemById($conn, 'game_cars', 'car_id', $eventDefaults['car_id'])['name'] ?? '';
            $track = getGameItemById($conn, 'game_tracks', 'track_id', $eventDefaults['track_id'])['name'] ?? '';
            $driver = getGameItemById($conn, 'game_drivers', 'driver_id', $eventDefaults['driver_id'])['name'] ?? '';
        }
    }
}

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM sessions WHERE session_id = ?");
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();
    if ($session) {
        $participantName = $session['participant_name'];
        $bestLapTime = $session['best_lap_time'];
        $eventId = $session['event_id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $participantName = trim($_POST['participant_name'] ?? '');
    $bestLapTime = trim($_POST['best_lap_time'] ?? '');
    $eventId = (int) ($_POST['event_id'] ?? $eventId);

    if (empty($participantName)) {
        $error = 'Participant name is required.';
    } elseif ($eventId === 0) {
        $error = 'Please select an event.';
    } else {
        $eventDefaults = getEventGameDefaults($conn, $eventId);
        if (!$eventDefaults) {
            $error = 'Selected event does not have game settings configured.';
        } else {
            $game = getGameItemById($conn, 'games', 'game_id', $eventDefaults['game_id']);
            $gameName = $game['name'] ?? '';
            $car = getGameItemById($conn, 'game_cars', 'car_id', $eventDefaults['car_id'])['name'] ?? '';
            $track = getGameItemById($conn, 'game_tracks', 'track_id', $eventDefaults['track_id'])['name'] ?? '';
            $driver = getGameItemById($conn, 'game_drivers', 'driver_id', $eventDefaults['driver_id'])['name'] ?? '';

            if ($isEdit) {
                $stmt = $conn->prepare("UPDATE sessions SET participant_name = ?, best_lap_time = ? WHERE session_id = ?");
                $stmt->bind_param("ssi", $participantName, $bestLapTime, $sessionId);
            } else {
                $stmt = $conn->prepare("INSERT INTO sessions (event_id, participant_name, best_lap_time, f1_version, car, track) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $eventId, $participantName, $bestLapTime, $gameName, $car, $track);
            }
            $stmt->execute();
            $stmt->close();
            header('Location: event_detail.php?id=' . $eventId . '&success=1');
            exit();
        }
    }
}

$pageTitle = $isEdit ? 'Edit Session' : 'Add Session';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h2><?= $isEdit ? 'Edit Session' : 'Add Session' ?></h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="sessionForm">
        <?php if (!$isEdit): ?>
            <div class="form-group">
                <label for="event_id">Event</label>
                <select id="event_id" name="event_id" required onchange="window.location.href='session_form.php?event_id=' + this.value;">
                    <option value="">Select event</option>
                    <?php foreach ($events as $ev): ?>
                        <option value="<?= $ev['event_id'] ?>" <?= ($ev['event_id'] == $eventId) ? 'selected' : '' ?>><?= htmlspecialchars($ev['event_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($event): ?>
                <div class="form-group readonly-field">
                    <strong>Game:</strong> <?= htmlspecialchars($gameName) ?><br>
                    <strong>Car:</strong> <?= htmlspecialchars($car) ?><br>
                    <strong>Track:</strong> <?= htmlspecialchars($track) ?><br>
                    <strong>Driver:</strong> <?= htmlspecialchars($driver) ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p>Event: <strong><?= htmlspecialchars($event['event_name'] ?? '') ?></strong></p>
            <div class="form-group readonly-field">
                <strong>Game:</strong> <?= htmlspecialchars($gameName) ?><br>
                <strong>Car:</strong> <?= htmlspecialchars($car) ?><br>
                <strong>Track:</strong> <?= htmlspecialchars($track) ?><br>
                <strong>Driver:</strong> <?= htmlspecialchars($driver) ?>
            </div>
        <?php endif; ?>

        <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId) ?>">

        <div class="form-group">
            <label for="participant_name">Participant Name</label>
            <input type="text" id="participant_name" name="participant_name"
                   value="<?= htmlspecialchars($participantName) ?>" required>
        </div>

        <!-- Timer UI -->
        <?php if (!$isEdit): ?>
        <div class="timer-box">
            <div class="timer-display" id="timerDisplay">5:00</div>
            <div class="timer-controls">
                <button type="button" id="startBtn" class="btn btn-success">▶ Start Session</button>
                <button type="button" id="stopBtn"  class="btn btn-danger" disabled>⏹ Stop</button>
            </div>
            <p class="timer-hint">Start the timer when the player begins. Hit Stop when they finish 2 laps.</p>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="best_lap_time">Best Lap Time</label>
            <input type="text" id="best_lap_time" name="best_lap_time"
                   value="<?= htmlspecialchars($bestLapTime) ?>"
                   placeholder="e.g. 01:43"
                   <?= !$isEdit ? 'readonly' : '' ?>>
            <?php if (!$isEdit): ?>
                <small>Filled automatically by the timer. Admin can edit it manually if needed.</small>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">
            <?= $isEdit ? 'Update Session' : 'Save Session' ?>
        </button>
        <a href="event_detail.php?id=<?= $eventId ?>" class="btn btn-secondary">Cancel</a>

    </form>
</div>

<?php if (!$isEdit): ?>
<script>
(function () {
    const MAX_SECONDS = 300; // 5 minutes

    let timerInterval = null;
    let elapsedSeconds = 0;
    let running = false;

    const display  = document.getElementById('timerDisplay');
    const startBtn = document.getElementById('startBtn');
    const stopBtn  = document.getElementById('stopBtn');
    const lapInput = document.getElementById('best_lap_time');

    function formatTime(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }

    function updateDisplay() {
        const remaining = MAX_SECONDS - elapsedSeconds;
        display.textContent = formatTime(remaining);

        if (remaining <= 30) {
            display.classList.add('timer-danger');
        }
    }

    function stopTimer(timedOut = false) {
        clearInterval(timerInterval);
        running = false;

        const recorded = timedOut ? MAX_SECONDS : elapsedSeconds;
        lapInput.value = formatTime(recorded);

        startBtn.disabled = true;
        stopBtn.disabled  = true;

        if (timedOut) {
            display.textContent = '00:00';
            display.classList.add('timer-danger');
        }
    }

    startBtn.addEventListener('click', function () {
        if (running) return;
        running = true;
        elapsedSeconds = 0;

        startBtn.disabled = true;
        stopBtn.disabled  = false;
        lapInput.value    = '';
        display.classList.remove('timer-danger');

        timerInterval = setInterval(function () {
            elapsedSeconds++;
            updateDisplay();

            if (elapsedSeconds >= MAX_SECONDS) {
                stopTimer(true);
            }
        }, 1000);
    });

    stopBtn.addEventListener('click', function () {
        stopTimer(false);
    });
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
