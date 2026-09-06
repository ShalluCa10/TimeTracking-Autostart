<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

requireLogin();

$conn = getConnection();
ensureScheduleTimerColumn($conn);
$scheduleId = (int) ($_GET['id'] ?? 0);
$isEdit = $scheduleId > 0;
$errors = [];
$values = [
    'schedule_name' => '',
    'schedule_date' => date('Y-m-d'),
    'location' => '',
    'notes' => '',
    'version_id' => 0,
    'formula' => '',
    'assist' => '',
    'car' => '',
    'track' => '',
    'racer' => '',
    'status' => 'auto',
    'timer_minutes' => '',
];

$versions = $conn->query('SELECT id, name FROM game_versions ORDER BY name ASC')
    ->fetch_all(MYSQLI_ASSOC);

if ($isEdit) {
    $stmt = $conn->prepare('
        SELECT schedule_id, schedule_name, schedule_date,
               location, version_id, formula, assist, team AS car, event AS track, racer, notes, created_at, status, timer_minutes
        FROM schedules WHERE schedule_id = ? LIMIT 1
    ');
    $stmt->bind_param('i', $scheduleId);
    $stmt->execute();
    $schedule = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$schedule) {
        $conn->close();
        header('Location: ../dashboard.php');
        exit();
    }
    $values = array_merge($values, $schedule);
}

$cars = [];
$tracks = [];
$racers = [];
$selectedVersion = (int) ($values['version_id'] ?? 0);

if ($selectedVersion > 0) {
    $stmt = $conn->prepare('SELECT name FROM game_teams WHERE version_id = ? ORDER BY sort_order ASC, name ASC');
    $stmt->bind_param('i', $selectedVersion);
    $stmt->execute();
    $cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $stmt = $conn->prepare('SELECT name FROM game_events WHERE version_id = ? ORDER BY sort_order ASC, name ASC');
    $stmt->bind_param('i', $selectedVersion);
    $stmt->execute();
    $tracks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $racers = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['schedule_name'] = trim($_POST['schedule_name'] ?? '');
    $values['schedule_date'] = trim($_POST['schedule_date'] ?? '');
    $values['location'] = trim($_POST['location'] ?? '');
    $values['notes'] = trim($_POST['notes'] ?? '');
    $values['version_id'] = (int) ($_POST['version_id'] ?? 0);
    $values['formula'] = trim($_POST['formula'] ?? '');
    $values['assist'] = trim($_POST['assist'] ?? '');
    $values['car'] = trim($_POST['car'] ?? '');
    $values['track'] = trim($_POST['track'] ?? '');
    $values['racer'] = trim($_POST['racer'] ?? '');
    $values['status'] = trim($_POST['status'] ?? 'auto');
    $values['timer_minutes'] = trim($_POST['timer_minutes'] ?? '');
    if ($values['schedule_name'] === '')
        $errors[] = 'Schedule name is required.';
    if ($values['schedule_date'] === '')
        $errors[] = 'Date is required.';
    if ($values['version_id'] === 0)
        $errors[] = 'Please select a game version.';
    $allowed = ['auto', 'live', 'completed'];
    if (!in_array($values['status'], $allowed))
        $errors[] = 'Invalid status selected.';
    if ($values['timer_minutes'] !== '' && (!ctype_digit($values['timer_minutes']) || (int) $values['timer_minutes'] < 1))
        $errors[] = 'Session timer must be a whole number of minutes (1 or more).';
    if (empty($errors)) {
        $timerMinutesParam = $values['timer_minutes'] === '' ? null : (int) $values['timer_minutes'];
        if ($isEdit) {
            $stmt = $conn->prepare('
                UPDATE schedules
                SET schedule_name = ?, schedule_date = ?, location = ?,
                    notes = ?, version_id = ?, formula = ?, assist = ?, team = ?, event = ?, racer = ?,
                    status = ?, timer_minutes = ?
                WHERE schedule_id = ?
            ');
            $stmt->bind_param(
                'sssisssssssii',
                $values['schedule_name'],
                $values['schedule_date'],
                $values['location'],
                $values['notes'],
                $values['version_id'],
                $values['formula'],
                $values['assist'],
                $values['car'],
                $values['track'],
                $values['racer'],
                $values['status'],
                $timerMinutesParam,
                $scheduleId
            );
        } else {
            $stmt = $conn->prepare('
                INSERT INTO schedules (schedule_name, schedule_date, location, notes, version_id, formula, assist, team, event, racer, status, timer_minutes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->bind_param(
                'sssisssssssi',
                $values['schedule_name'],
                $values['schedule_date'],
                $values['location'],
                $values['notes'],
                $values['version_id'],
                $values['formula'],
                $values['assist'],
                $values['car'],
                $values['track'],
                $values['racer'],
                $values['status'],
                $timerMinutesParam
            );
        }
        $stmt->execute();
        $stmt->close();
        $conn->close();
        $_SESSION['flash'] = ['type' => 'success', 'message' => $isEdit ? 'Schedule updated.' : 'Schedule created.'];
        header('Location: ../dashboard.php');
        exit();
    }
}

$conn->close();

$pageTitle = $isEdit ? 'Edit Schedule' : 'New Schedule';
include __DIR__ . '/../../../includes/header.php';

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function sel(mixed $a, mixed $b): string
{
    return $a == $b ? 'selected' : '';
}
function dis(bool $condition): string
{
    return $condition ? 'disabled' : '';
}
function emptyClass(bool $condition): string
{
    return $condition ? 'empty' : '';
}
?>

<?php if (isset($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash'];
    unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= h($flash['type']) ?> mb-4">
        <?= h($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <h2><?= $pageTitle ?></h2>
    <a href="../dashboard.php" class="btn btn-secondary">← Back</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-7">
            <div class="alert alert-danger mb-4">
                <?php foreach ($errors as $err): ?>
                    <p class="mb-1"><?= h($err) ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card">
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="schedule_name" class="form-label">Schedule Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" id="schedule_name" name="schedule_name" class="form-control"
                                value="<?= h($values['schedule_name']) ?>" required autofocus
                                placeholder="e.g. Monaco GP Night">
                        </div>
                        <div class="col-md-4">
                            <label for="schedule_date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" id="schedule_date" name="schedule_date" class="form-control"
                                value="<?= h($values['schedule_date']) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" id="location" name="location" class="form-control"
                            value="<?= h($values['location']) ?>" placeholder="e.g. Montreal, QC">
                    </div>
                    <div class="mb-1 mt-4">
                        <span class="form-section__label">Game Setup</span>
                    </div>
                    <div class="mb-3">
                        <label for="sel-version" class="form-label">Game Version <span
                                class="text-danger">*</span></label>
                        <select id="sel-version" name="version_id"
                            class="form-select <?= emptyClass($selectedVersion === 0) ?>" required>
                            <option value="" disabled <?= $selectedVersion === 0 ? 'selected' : '' ?>>Select Version
                            </option>
                            <?php foreach ($versions as $v): ?>
                                <option value="<?= (int) $v['id'] ?>" <?= sel($values['version_id'], $v['id']) ?>>
                                    <?= h($v['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="sel-formula" class="form-label">Formula <span class="text-danger">*</span></label>
                            <select id="sel-formula" name="formula"
                                class="form-select <?= emptyClass($values['formula'] === '') ?>"
                                <?= dis($selectedVersion === 0) ?>>
                                <option value="" disabled selected>
                                    <?= $selectedVersion === 0 ? 'Select Version First' : 'Select Formula' ?>
                                </option>
                                <option value="F1" <?= sel($values['formula'], 'F1') ?>>F1</option>
                                <option value="F2" <?= sel($values['formula'], 'F2') ?>>F2</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="sel-assist" class="form-label">Assist Level <span class="text-danger">*</span></label>
                            <select id="sel-assist" name="assist"
                                class="form-select <?= emptyClass($values['assist'] === '') ?>"
                                <?= dis($selectedVersion === 0) ?>>
                                <option value="" disabled selected>
                                    <?= $selectedVersion === 0 ? 'Select Version First' : 'Select Assist' ?>
                                </option>
                                <option value="Beginner" <?= sel($values['assist'], 'Beginner') ?>>Beginner</option>
                                <option value="Amateur" <?= sel($values['assist'], 'Amateur') ?>>Amateur</option>
                                <option value="Experienced" <?= sel($values['assist'], 'Experienced') ?>>Experienced</option>
                                <option value="Professional" <?= sel($values['assist'], 'Professional') ?>>Professional</option>
                                <option value="Elite" <?= sel($values['assist'], 'Elite') ?>>Elite</option>
                                <option value="Event Build" <?= sel($values['assist'], 'Event Build') ?>>Event Build</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="sel-track" class="form-label">Event <span class="text-danger">*</span></label>
                            <select id="sel-track" name="track"
                                class="form-select <?= emptyClass($values['track'] === '') ?>"
                                <?= dis($selectedVersion === 0) ?>>
                                <option value="" disabled selected>
                                    <?= $selectedVersion === 0 ? 'Select Version First' : 'Select Event' ?>
                                </option>
                                <?php foreach ($tracks as $t): ?>
                                    <option value="<?= h($t['name']) ?>" <?= sel($values['track'], $t['name']) ?>>
                                        <?= h($t['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="sel-car" class="form-label">Team <span class="text-danger">*</span></label>
                            <select id="sel-car" name="car" class="form-select <?= emptyClass($values['car'] === '') ?>"
                                <?= dis($selectedVersion === 0) ?>>
                                <option value="" disabled selected>
                                    <?= $selectedVersion === 0 ? 'Select Version First' : 'Select Team' ?>
                                </option>
                                <?php foreach ($cars as $c): ?>
                                    <option value="<?= h($c['name']) ?>" <?= sel($values['car'], $c['name']) ?>>
                                        <?= h($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="racer" class="form-label">Participant</label>
                        <input type="text" id="racer" name="racer" class="form-control <?= emptyClass($values['racer'] === '') ?>"
                            value="<?= h($values['racer']) ?>" placeholder="e.g. Oscar Piastri">
                    </div>
                    <div class="mb-1 mt-4">
                        <span class="form-section__label">Schedule Status</span>
                    </div>
                    <div class="mb-3">
                        <label for="sel-status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="sel-status" name="status" class="form-select" required>
                            <option value="auto" <?= sel($values['status'], 'auto') ?>>Upcoming</option>
                            <option value="live" <?= sel($values['status'], 'live') ?>>Live</option>
                            <option value="completed" <?= sel($values['status'], 'completed') ?>>Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="timer_minutes" class="form-label">Session Timer (minutes)</label>
                        <input type="number" id="timer_minutes" name="timer_minutes" class="form-control" min="1"
                            value="<?= h((string) $values['timer_minutes']) ?>" placeholder="Leave blank for no timer">
                        <div class="form-text">Each session created for this schedule auto-ends once this time runs out.</div>
                    </div>
                    <div class="mb-4">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3"
                            placeholder="Any extra details..."><?= h($values['notes']) ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?= $isEdit ? 'Save Schedule' : 'Create Schedule' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const selVersion = document.getElementById('sel-version');
        const selTrack = document.getElementById('sel-track');
        const selCar = document.getElementById('sel-car');
        const selRacer = document.getElementById('racer');
        const savedTrack = <?= json_encode($values['track']) ?>;
        const savedCar = <?= json_encode($values['car']) ?>;
        const savedRacer = <?= json_encode($values['racer']) ?>;
        const API_URL = '/api/get_options.php';

        function resetSelect(el, placeholder) {
            el.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
            el.disabled = true;
            el.classList.add('empty');
        }

        function populate(el, items, savedValue, emptyLabel) {
            el.innerHTML = `<option value="" disabled selected>${emptyLabel}</option>`;
            items.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.name;
                opt.textContent = item.name;
                if (item.name === savedValue) opt.selected = true;
                el.appendChild(opt);
            });
            el.disabled = items.length === 0;
            el.classList.toggle('empty', el.value === '');
        }

        async function loadOptions(versionId, restoreTrack = '', restoreCar = '', restoreRacer = '') {
            if (!versionId) return;
            resetSelect(selTrack, 'Loading...');
            resetSelect(selCar, 'Loading...');
            try {
                const [tracks, cars] = await Promise.all([
                    fetch(`${API_URL}?type=tracks&version_id=${versionId}`).then(r => r.json()),
                    fetch(`${API_URL}?type=cars&version_id=${versionId}`).then(r => r.json()),
                ]);
                populate(selTrack, tracks, restoreTrack, tracks.length ? 'Select Event' : 'No events');
                populate(selCar, cars, restoreCar, cars.length ? 'Select Team' : 'No teams');
                if (selRacer.value === '') {
                    selRacer.value = restoreRacer || '';
                }
            } catch (err) {
                console.error('get_options failed:', err);
                resetSelect(selTrack, 'Error loading');
                resetSelect(selCar, 'Error loading');
            }
        }

        selVersion.addEventListener('change', function () {
            selVersion.classList.toggle('empty', this.value === '');
            if (this.value) {
                loadOptions(this.value);
            } else {
                resetSelect(selTrack, 'Select Version First');
                resetSelect(selCar, 'Select Version First');
            }
        });

        [selTrack, selCar].forEach(el => {
            el.addEventListener('change', function () {
                this.classList.toggle('empty', this.value === '');
            });
        });

        if (selVersion.value) {
            loadOptions(selVersion.value, savedTrack, savedCar, savedRacer);
        }
    })();
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>