<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

requireLogin();

$conn = getConnection();

$scheduleId = (int) ($_POST['schedule_id'] ?? $_GET['schedule_id'] ?? 0);
$sessionId = (int) ($_GET['id'] ?? 0);
$isEdit = $sessionId > 0;

$schedules = $conn->query('SELECT schedule_id, schedule_name FROM schedules ORDER BY schedule_date DESC')->fetch_all(MYSQLI_ASSOC);
$versions = $conn->query('SELECT id, name FROM game_versions ORDER BY name ASC')->fetch_all(MYSQLI_ASSOC);

$schedule = null;
$participantName = '';
$bestLapTime = '';
$f1Version = '';
$car = '';
$track = '';
$selectedVersion = 0;
$cars = [];
$tracks = [];
$error = '';

if ($scheduleId > 0) {
    $stmt = $conn->prepare('SELECT schedule_id, schedule_name FROM schedules WHERE schedule_id = ? LIMIT 1');
    $stmt->bind_param('i', $scheduleId);
    $stmt->execute();
    $schedule = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($isEdit) {
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
        $conn->close();
        header('Location: ../dashboard.php');
        exit();
    }

    $participantName = $session['participant_name'];
    $bestLapTime = $session['best_lap_time'];
    $f1Version = $session['f1_version'] ?? '';
    $car = $session['car'];
    $track = $session['track'];
    $scheduleId = $session['schedule_id'];

    if (!$schedule) {
        $stmt = $conn->prepare('SELECT schedule_id, schedule_name FROM schedules WHERE schedule_id = ? LIMIT 1');
        $stmt->bind_param('i', $scheduleId);
        $stmt->execute();
        $schedule = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    foreach ($versions as $v) {
        if ($v['name'] === $f1Version) {
            $selectedVersion = $v['id'];
            break;
        }
    }
}

if ($selectedVersion > 0) {
    $stmt = $conn->prepare('SELECT name FROM game_teams WHERE version_id = ? ORDER BY name ASC');
    $stmt->bind_param('i', $selectedVersion);
    $stmt->execute();
    $cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT name FROM game_events WHERE version_id = ? ORDER BY name ASC');
    $stmt->bind_param('i', $selectedVersion);
    $stmt->execute();
    $tracks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participant_name'])) {
    $participantName = trim($_POST['participant_name'] ?? '');
    $scheduleId = (int) ($_POST['schedule_id'] ?? 0);

    if ($participantName === '') {
        $error = 'Participant name is required.';
    } elseif ($isEdit) {
        $stmt = $conn->prepare('UPDATE sessions SET participant_name = ? WHERE session_id = ?');
        $stmt->bind_param('si', $participantName, $sessionId);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        setFlash('success', 'Participant name updated.');
        header('Location: ../sessions/sessions.php?id=' . $scheduleId);
        exit();
    } elseif ($scheduleId === 0) {
        $error = 'Please select a schedule.';
    } else {
        $bestLapTime = trim($_POST['best_lap_time'] ?? '');
        $f1Version = trim($_POST['f1_version'] ?? '');
        $car = trim($_POST['car'] ?? '');
        $track = trim($_POST['track'] ?? '');

        if ($f1Version === '') {
            $error = 'Please select a game version.';
        } else {
            $stmt = $conn->prepare('INSERT INTO sessions (schedule_id, participant_name, best_lap_time, f1_version, team, event) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isssss', $scheduleId, $participantName, $bestLapTime, $f1Version, $car, $track);
            $stmt->execute();
            $stmt->close();
            $conn->close();

            setFlash('success', 'Session added.');
            header('Location: ../schedules/schedule_detail.php?id=' . $scheduleId);
            exit();
        }
    }
}

$conn->close();

$pageTitle = $isEdit ? 'Edit Session' : 'Add Session';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="page-header">
    <a href="../schedules/schedule_detail.php?id=<?= $scheduleId ?>" class="back-link">← Back to Schedule</a>
    <h2><?= $isEdit ? 'Edit Session' : 'Add Session' ?></h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST" id="sessionForm">

                    <?php if (!$isEdit): ?>
                        <div class="mb-3">
                            <label for="schedule_id" class="form-label">Schedule</label>
                            <select id="schedule_id" name="schedule_id" class="form-select" required onchange="window.location.href='session_form.php?schedule_id=' + this.value">
                                <option value="">- Select Schedule -</option>
                                <?php foreach ($schedules as $sc): ?>
                                    <option value="<?= $sc['schedule_id'] ?>" <?= $sc['schedule_id'] == $scheduleId ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sc['schedule_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label">Schedule</label>
                            <div class="form-control-plaintext fw-semibold"><?= htmlspecialchars($schedule['schedule_name'] ?? '-') ?></div>
                        </div>
                        <input type="hidden" name="schedule_id" value="<?= $scheduleId ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="participant_name" class="form-label">Participant</label>
                        <input type="text" id="participant_name" name="participant_name" class="form-control" value="<?= htmlspecialchars($participantName) ?>" placeholder="e.g. Oscar Piastri" required>
                    </div>

                    <?php if (!$isEdit): ?>
                        <div class="mb-3">
                            <label for="sel-version" class="form-label">Game Version</label>
                            <select id="sel-version" name="f1_version" class="form-select" required>
                                <option value="">- Select Version -</option>
                                <?php foreach ($versions as $v): ?>
                                    <option value="<?= htmlspecialchars($v['name']) ?>" data-id="<?= $v['id'] ?>" <?= $v['name'] === $f1Version ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="sel-track" class="form-label">Event</label>
                            <select id="sel-track" name="track" class="form-select" <?= $selectedVersion === 0 ? 'disabled' : '' ?>>
                                <option value="">- Select Version First -</option>
                                <?php foreach ($tracks as $t): ?>
                                    <option value="<?= htmlspecialchars($t['name']) ?>" <?= $t['name'] === $track ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="sel-car" class="form-label">Team</label>
                            <select id="sel-car" name="car" class="form-select" <?= $selectedVersion === 0 ? 'disabled' : '' ?>>
                                <option value="">- Select Version First -</option>
                                <?php foreach ($cars as $c): ?>
                                    <option value="<?= htmlspecialchars($c['name']) ?>" <?= $c['name'] === $car ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="best_lap_time" class="form-label">Best Lap Time</label>
                            <input type="text" id="best_lap_time" name="best_lap_time" class="form-control" value="<?= htmlspecialchars($bestLapTime) ?>" placeholder="e.g. 1:23.456">
                            <div class="form-text">Leave blank if session hasn't been run yet.</div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label">Game Version</label>
                            <div class="form-control-plaintext fw-semibold"><?= htmlspecialchars($f1Version ?: '-') ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Event</label>
                            <div class="form-control-plaintext fw-semibold"><?= htmlspecialchars($track ?: '-') ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Team</label>
                            <div class="form-control-plaintext fw-semibold"><?= htmlspecialchars($car ?: '-') ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Best Lap Time</label>
                            <div class="form-control-plaintext fw-semibold"><?= htmlspecialchars($bestLapTime ?: '-') ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Participant' : 'Save Session' ?></button>
                        <a href="../schedules/schedule_detail.php?id=<?= $scheduleId ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!$isEdit): ?>
<script>
    (function () {
        const selVersion = document.getElementById('sel-version');
        const selTrack = document.getElementById('sel-track');
        const selCar = document.getElementById('sel-car');

        const savedTrack = <?= json_encode($track) ?>;
        const savedCar = <?= json_encode($car) ?>;

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
            resetSelect(selTrack, '- Loading... -');
            resetSelect(selCar, '- Loading... -');

            const [tracks, cars] = await Promise.all([
                fetch(`/api/get_options.php?type=tracks&version_id=${versionId}`).then(r => r.json()),
                fetch(`/api/get_options.php?type=cars&version_id=${versionId}`).then(r => r.json()),
            ]);

            populate(selTrack, tracks, savedTrack, tracks.length ? '- Select Schedule -' : '- No schedules -');
            populate(selCar, cars, savedCar, cars.length ? '- Select Team -' : '- No teams -');
        }

        selVersion.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const versionId = opt.dataset.id;
            if (versionId) {
                loadOptions(versionId);
            } else {
                resetSelect(selTrack, '- Select Version First -');
                resetSelect(selCar, '- Select Version First -');
            }
        });

        <?php if ($selectedVersion > 0): ?>
            loadOptions(<?= $selectedVersion ?>);
        <?php endif; ?>
    })();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>