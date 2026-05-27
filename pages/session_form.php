<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
$conn = getConnection();

$eventId   = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$sessionId = isset($_GET['id'])       ? (int)$_GET['id']       : 0;
$isEdit    = $sessionId > 0;

// Fetch event
$eventStmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
$eventStmt->bind_param("i", $eventId);
$eventStmt->execute();
$event = $eventStmt->get_result()->fetch_assoc();

if (!$event) {
    header('Location: dashboard.php');
    exit();
}

$participantName = '';
$bestLapTime     = '';
$f1Version       = '';
$car             = '';
$track           = '';
$error           = '';

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM sessions WHERE session_id = ?");
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();
    if ($session) {
        $participantName = $session['participant_name'];
        $bestLapTime     = $session['best_lap_time'];
        $f1Version       = $session['f1_version'];
        $car             = $session['car'];
        $track           = $session['track'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $participantName = trim($_POST['participant_name'] ?? '');
    $bestLapTime = trim($_POST['best_lap_time'] ?? '');
    $f1Version = trim($_POST['f1_version']  ?? '');
    $car = trim($_POST['car'] ?? '');
    $track = trim($_POST['track'] ?? '');

    if (empty($participantName)) {
        $error = 'Participant name is required.';
    } else {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE sessions SET participant_name = ?, best_lap_time = ?, f1_version = ?, car = ?, track = ? WHERE session_id = ?");
            $stmt->bind_param("sssssi", $participantName, $bestLapTime, $f1Version, $car, $track, $sessionId);
        } else {
            $stmt = $conn->prepare("INSERT INTO sessions (event_id, participant_name, best_lap_time, f1_version, car, track) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $eventId, $participantName, $bestLapTime, $f1Version, $car, $track);
        }
        $stmt->execute();
        header('Location: event_detail.php?id=' . $eventId . '&success=1');
        exit();
    }
}

$pageTitle = $isEdit ? 'Edit Session' : 'Add Session';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h2><?= $isEdit ? 'Edit Session' : 'Add Session' ?></h2>
    <p>Event: <strong><?= htmlspecialchars($event['event_name']) ?></strong></p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="sessionForm">

        <div class="form-group">
            <label for="f1_version">F1 Version</label>
            <input type="text" id="f1_version" name="f1_version"
                   value="<?= htmlspecialchars($f1Version) ?>"
                   placeholder="e.g. F1 24">
        </div>

        <div class="form-group">
            <label for="car">Car</label>
            <input type="text" id="car" name="car"
                   value="<?= htmlspecialchars($car) ?>"
                   placeholder="e.g. Red Bull RB20">
        </div>

        <div class="form-group">
            <label for="track">Track</label>
            <input type="text" id="track" name="track"
                   value="<?= htmlspecialchars($track) ?>"
                   placeholder="e.g. Silverstone">
        </div>

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
