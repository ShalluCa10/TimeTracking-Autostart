<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$conn = getConnection();

$eventId = (int) ($_POST['event_id'] ?? $_GET['event_id'] ?? 0);
$sessionId = (int) ($_GET['id'] ?? 0);
$isEdit = $sessionId > 0;

// Load all events for the dropdown
$events = $conn->query('SELECT event_id, event_name FROM events ORDER BY event_date DESC')
    ->fetch_all(MYSQLI_ASSOC);

// Load all game versions for dropdowns 
$versions = $conn->query('SELECT id, name FROM game_versions ORDER BY name ASC')
    ->fetch_all(MYSQLI_ASSOC);

// Defaults
$event = null;
$participantName = '';
$bestLapTime = '';
$f1Version = '';
$car = '';
$track = '';
$selectedVersion = 0;
$cars = [];
$tracks = [];
$racers = [];
$error = '';

//Load event
if ($eventId > 0) {
    $stmt = $conn->prepare('SELECT * FROM events WHERE event_id = ? LIMIT 1');
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($isEdit) {
    $stmt = $conn->prepare('SELECT * FROM sessions WHERE session_id = ? LIMIT 1');
    $stmt->bind_param('i', $sessionId);
    $stmt->execute();
    $session = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($session) {
        $participantName = $session['participant_name'];
        $bestLapTime = $session['best_lap_time'];
        $f1Version = $session['f1_version'] ?? '';
        $car = $session['car'];
        $track = $session['track'];
        $eventId = $session['event_id'];

        // Load event if not already loaded
        if (!$event) {
            $stmt = $conn->prepare('SELECT * FROM events WHERE event_id = ? LIMIT 1');
            $stmt->bind_param('i', $eventId);
            $stmt->execute();
            $event = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        // Find matching version id for dropdowns
        foreach ($versions as $v) {
            if ($v['name'] === $f1Version) {
                $selectedVersion = $v['id'];
                break;
            }
        }
    } else {
        header('Location: dashboard.php');
        exit();
    }
}

if ($selectedVersion > 0) {
    $stmt = $conn->prepare('SELECT name FROM game_cars WHERE version_id = ? ORDER BY name ASC');
    $stmt->bind_param('i', $selectedVersion);
    $stmt->execute();
    $cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT name FROM game_tracks WHERE version_id = ? ORDER BY name ASC');
    $stmt->bind_param('i', $selectedVersion);
    $stmt->execute();
    $tracks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT name FROM game_racers WHERE version_id = ? ORDER BY name ASC');
    $stmt->bind_param('i', $selectedVersion);
    $stmt->execute();
    $racers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participant_name'])) {
    $participantName = trim($_POST['participant_name'] ?? '');
    $bestLapTime = trim($_POST['best_lap_time'] ?? '');
    $f1Version = trim($_POST['f1_version'] ?? '');
    $car = trim($_POST['car'] ?? '');
    $track = trim($_POST['track'] ?? '');
    $eventId = (int) ($_POST['event_id'] ?? 0);

    if ($participantName === '') {
        $error = 'Participant name is required.';
    } elseif ($eventId === 0) {
        $error = 'Please select an event.';
    } elseif ($f1Version === '') {
        $error = 'Please select a game version.';
    } elseif ($car === '') {
        $error = 'Please select a car.';
    } elseif ($track === '') {
        $error = 'Please select a track.';
    } else {
        if ($isEdit) {
            $stmt = $conn->prepare(
                'UPDATE sessions SET participant_name = ?, best_lap_time = ?, f1_version = ?, car = ?, track = ? WHERE session_id = ?'
            );
            $stmt->bind_param('sssssi', $participantName, $bestLapTime, $f1Version, $car, $track, $sessionId);
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO sessions (event_id, participant_name, best_lap_time, f1_version, car, track) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('isssss', $eventId, $participantName, $bestLapTime, $f1Version, $car, $track);
        }
        $stmt->execute();
        $stmt->close();
        $conn->close();

        setFlash('success', $isEdit ? 'Session updated.' : 'Session added.');
        header('Location: event_detail.php?id=' . $eventId);
        exit();
    }
}

$conn->close();

$pageTitle = $isEdit ? 'Edit Session' : 'Add Session';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <a href="event_detail.php?id=<?= $eventId ?>" class="back-link">← Back to Event</a>
    <h2><?= $isEdit ? 'Edit Session' : 'Add Session' ?></h2>
</div>

<?php if ($error): ?>
    <div class="alert alert--danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" id="sessionForm" class="form-card">

    <!-- Event -->
    <?php if (!$isEdit): ?>
        <div class="form-group">
            <label for="event_id">Event</label>
            <select id="event_id" name="event_id" required
                onchange="window.location.href='session_form.php?event_id=' + this.value">
                <option value="">— Select Event —</option>
                <?php foreach ($events as $ev): ?>
                    <option value="<?= $ev['event_id'] ?>" <?= $ev['event_id'] == $eventId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ev['event_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php else: ?>
        <p class="form-readonly">Event: <strong><?= htmlspecialchars($event['event_name'] ?? '—') ?></strong></p>
        <input type="hidden" name="event_id" value="<?= $eventId ?>">
    <?php endif; ?>

    <!-- Game Version -->
    <div class="form-group">
        <label for="sel-version">🎮 Game Version</label>
        <select id="sel-version" name="f1_version" required>
            <option value="">— Select Version —</option>
            <?php foreach ($versions as $v): ?>
                <option value="<?= htmlspecialchars($v['name']) ?>" data-id="<?= $v['id'] ?>" <?= $v['name'] === $f1Version ? 'selected' : '' ?>>
                    <?= htmlspecialchars($v['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Track -->
    <div class="form-group">
        <label for="sel-track">🏁 Track</label>
        <select id="sel-track" name="track" required <?= $selectedVersion === 0 ? 'disabled' : '' ?>>
            <option value="">— Select Version First —</option>
            <?php foreach ($tracks as $t): ?>
                <option value="<?= htmlspecialchars($t['name']) ?>" <?= $t['name'] === $track ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Car -->
    <div class="form-group">
        <label for="sel-car">🚗 Car</label>
        <select id="sel-car" name="car" required <?= $selectedVersion === 0 ? 'disabled' : '' ?>>
            <option value="">— Select Version First —</option>
            <?php foreach ($cars as $c): ?>
                <option value="<?= htmlspecialchars($c['name']) ?>" <?= $c['name'] === $car ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Racer -->
    <div class="form-group">
        <label for="sel-racer">👤 Racer / Participant</label>
        <select id="sel-racer" name="participant_name" required <?= $selectedVersion === 0 ? 'disabled' : '' ?>>
            <option value="">— Select Version First —</option>
            <?php foreach ($racers as $r): ?>
                <option value="<?= htmlspecialchars($r['name']) ?>" <?= $r['name'] === $participantName ? 'selected' : '' ?>>
                    <?= htmlspecialchars($r['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Best Lap Time -->
    <div class="form-group">
        <label for="best_lap_time">Best Lap Time</label>
        <input type="text" id="best_lap_time" name="best_lap_time" value="<?= htmlspecialchars($bestLapTime) ?>"
            placeholder="e.g. 1:23.456">
        <small>Leave blank if session hasn't been run yet.</small>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">
            <?= $isEdit ? 'Update Session' : 'Save Session' ?>
        </button>
        <a href="event_detail.php?id=<?= $eventId ?>" class="btn btn--outline">Cancel</a>
    </div>

</form>

<script>
    (function () {
        const selVersion = document.getElementById('sel-version');
        const selTrack = document.getElementById('sel-track');
        const selCar = document.getElementById('sel-car');
        const selRacer = document.getElementById('sel-racer');

        const savedTrack = <?= json_encode($track) ?>;
        const savedCar = <?= json_encode($car) ?>;
        const savedRacer = <?= json_encode($participantName) ?>;

        function resetSelect(el, placeholder) {
            el.innerHTML = `<option value="">${placeholder}</option>`;
            el.disabled = true;
        }

        function populate(el, items, savedValue, placeholder) {
            el.innerHTML = `<option value="">${placeholder}</option>`;
            items.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.name;
                opt.textContent = item.name;
                if (item.name === savedValue) opt.selected = true;
                el.appendChild(opt);
            });
            el.disabled = items.length === 0;
        }

        async function loadOptions(versionId) {
            resetSelect(selTrack, '— Loading... —');
            resetSelect(selCar, '— Loading... —');
            resetSelect(selRacer, '— Loading... —');

            const [tracks, cars, racers] = await Promise.all([
                fetch(`../api/get_options.php?type=tracks&version_id=${versionId}`).then(r => r.json()),
                fetch(`../api/get_options.php?type=cars&version_id=${versionId}`).then(r => r.json()),
                fetch(`../api/get_options.php?type=racers&version_id=${versionId}`).then(r => r.json()),
            ]);

            populate(selTrack, tracks, savedTrack, tracks.length ? '— Select Track —' : '— No tracks —');
            populate(selCar, cars, savedCar, cars.length ? '— Select Car —' : '— No cars —');
            populate(selRacer, racers, savedRacer, racers.length ? '— Select Racer —' : '— No racers —');
        }

        selVersion.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const versionId = opt.dataset.id;
            if (versionId) {
                loadOptions(versionId);
            } else {
                resetSelect(selTrack, '— Select Version First —');
                resetSelect(selCar, '— Select Version First —');
                resetSelect(selRacer, '— Select Version First —');
            }
        });

        <?php if ($selectedVersion > 0): ?>
            loadOptions(<?= $selectedVersion ?>);
        <?php endif; ?>
    })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>