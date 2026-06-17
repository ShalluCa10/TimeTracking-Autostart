<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$conn = getConnection();
$eventId = (int) ($_GET['id'] ?? 0);
$isEdit = $eventId > 0;
$errors = [];
$values = [
    'event_name' => '',
    'event_date' => date('Y-m-d'),
    'location' => '',
    'notes' => '',
    'version_id' => 0,
    'car' => '',
    'track' => '',
    'racer' => '',
    'status' => 'auto',
];

$versions = $conn->query('SELECT id, name FROM game_versions ORDER BY name ASC')
    ->fetch_all(MYSQLI_ASSOC);

if ($isEdit) {
    $stmt = $conn->prepare('SELECT * FROM events WHERE event_id = ? LIMIT 1');
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$event) {
        $conn->close();
        header('Location: dashboard.php');
        exit();
    }
    $values = array_merge($values, $event);
}

$cars = [];
$tracks = [];
$racers = [];
$selectedVersion = (int) ($values['version_id'] ?? 0);

if ($selectedVersion > 0) {
    $stmt = $conn->prepare('SELECT name FROM game_cars WHERE version_id = ? ORDER BY sort_order ASC, name ASC');
    $stmt->bind_param('i', $selectedVersion);
    $stmt->execute();
    $cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $stmt = $conn->prepare('SELECT name FROM game_tracks WHERE version_id = ? ORDER BY sort_order ASC, name ASC');
    $stmt->bind_param('i', $selectedVersion);
    $stmt->execute();
    $tracks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $stmt = $conn->prepare('SELECT name FROM game_racers WHERE version_id = ? ORDER BY sort_order ASC, name ASC');
    $stmt->bind_param('i', $selectedVersion);
    $stmt->execute();
    $racers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['event_name'] = trim($_POST['event_name'] ?? '');
    $values['event_date'] = trim($_POST['event_date'] ?? '');
    $values['location'] = trim($_POST['location'] ?? '');
    $values['notes'] = trim($_POST['notes'] ?? '');
    $values['version_id'] = (int) ($_POST['version_id'] ?? 0);
    $values['car'] = trim($_POST['car'] ?? '');
    $values['track'] = trim($_POST['track'] ?? '');
    $values['racer'] = trim($_POST['racer'] ?? '');
    $values['status'] = trim($_POST['status'] ?? 'auto');
    if ($values['event_name'] === '')
        $errors[] = 'Event name is required.';
    if ($values['event_date'] === '')
        $errors[] = 'Date is required.';
    if ($values['version_id'] === 0)
        $errors[] = 'Please select a game version.';
    if ($values['car'] === '')
        $errors[] = 'Please select a car.';
    if ($values['track'] === '')
        $errors[] = 'Please select a track.';
    if ($values['racer'] === '')
        $errors[] = 'Please select a racer.';
    $allowed = ['auto', 'live', 'completed'];
    if (!in_array($values['status'], $allowed))
        $errors[] = 'Invalid status selected.';
    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $conn->prepare('
                UPDATE events
                SET event_name = ?, event_date = ?, location = ?,
                    notes = ?, version_id = ?, car = ?, track = ?, racer = ?,
                    status = ?
                WHERE event_id = ?
            ');
            $stmt->bind_param(
                'ssssissssi',
                $values['event_name'],
                $values['event_date'],
                $values['location'],
                $values['notes'],
                $values['version_id'],
                $values['car'],
                $values['track'],
                $values['racer'],
                $values['status'],
                $eventId
            );
        } else {
            $stmt = $conn->prepare('
                INSERT INTO events (event_name, event_date, location, notes, version_id, car, track, racer, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->bind_param(
                'ssssissss',
                $values['event_name'],
                $values['event_date'],
                $values['location'],
                $values['notes'],
                $values['version_id'],
                $values['car'],
                $values['track'],
                $values['racer'],
                $values['status']
            );
        }
        $stmt->execute();
        $stmt->close();
        $conn->close();
        $_SESSION['flash'] = ['type' => 'success', 'message' => $isEdit ? 'Event updated.' : 'Event created.'];
        header('Location: dashboard.php');
        exit();
    }
}

$conn->close();

$pageTitle = $isEdit ? 'Edit Event' : 'New Event';
include __DIR__ . '/../includes/header.php';

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
    <a href="dashboard.php" class="btn btn-secondary">← Back</a>
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
                            <label for="event_name" class="form-label">Event Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" id="event_name" name="event_name" class="form-control"
                                value="<?= h($values['event_name']) ?>" required autofocus
                                placeholder="e.g. Monaco GP Night">
                        </div>
                        <div class="col-md-4">
                            <label for="event_date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" id="event_date" name="event_date" class="form-control"
                                value="<?= h($values['event_date']) ?>" required>
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
                            <label for="sel-track" class="form-label">Track <span class="text-danger">*</span></label>
                            <select id="sel-track" name="track"
                                class="form-select <?= emptyClass($values['track'] === '') ?>" required
                                <?= dis($selectedVersion === 0) ?>>
                                <option value="" disabled selected>
                                    <?= $selectedVersion === 0 ? 'Select Version First' : 'Select Track' ?>
                                </option>
                                <?php foreach ($tracks as $t): ?>
                                    <option value="<?= h($t['name']) ?>" <?= sel($values['track'], $t['name']) ?>>
                                        <?= h($t['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="sel-car" class="form-label">Car <span class="text-danger">*</span></label>
                            <select id="sel-car" name="car" class="form-select <?= emptyClass($values['car'] === '') ?>"
                                required <?= dis($selectedVersion === 0) ?>>
                                <option value="" disabled selected>
                                    <?= $selectedVersion === 0 ? 'Select Version First' : 'Select Car' ?>
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
                        <label for="sel-racer" class="form-label">Racer <span class="text-danger">*</span></label>
                        <select id="sel-racer" name="racer"
                            class="form-select <?= emptyClass($values['racer'] === '') ?>" required
                            <?= dis($selectedVersion === 0) ?>>
                            <option value="" disabled selected>
                                <?= $selectedVersion === 0 ? 'Select Version First' : 'Select Racer' ?>
                            </option>
                            <?php foreach ($racers as $r): ?>
                                <option value="<?= h($r['name']) ?>" <?= sel($values['racer'], $r['name']) ?>>
                                    <?= h($r['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($isEdit): ?>
                        <div class="mb-1 mt-4">
                            <span class="form-section__label">Event Status</span>
                        </div>
                        <div class="mb-3">
                            <label for="sel-status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select id="sel-status" name="status" class="form-select" required>
                                <option value="auto" <?= sel($values['status'], 'auto') ?>>Upcoming</option>
                                <option value="live" <?= sel($values['status'], 'live') ?>>Live</option>
                                <option value="completed" <?= sel($values['status'], 'completed') ?>>Completed</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="mb-4">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3"
                            placeholder="Any extra details..."><?= h($values['notes']) ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?= $isEdit ? 'Save Changes' : 'Create Event' ?>
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
        const selRacer = document.getElementById('sel-racer');
        const savedTrack = <?= json_encode($values['track']) ?>;
        const savedCar = <?= json_encode($values['car']) ?>;
        const savedRacer = <?= json_encode($values['racer']) ?>;
        const API_URL = window.location.origin
            + window.location.pathname.replace(/\/pages\/[^\/]+$/, '')
            + '/api/get_options.php';

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
            resetSelect(selRacer, 'Loading...');
            try {
                const [tracks, cars, racers] = await Promise.all([
                    fetch(`${API_URL}?type=tracks&version_id=${versionId}`).then(r => r.json()),
                    fetch(`${API_URL}?type=cars&version_id=${versionId}`).then(r => r.json()),
                    fetch(`${API_URL}?type=racers&version_id=${versionId}`).then(r => r.json()),
                ]);
                populate(selTrack, tracks, restoreTrack, tracks.length ? 'Select Track' : 'No tracks');
                populate(selCar, cars, restoreCar, cars.length ? 'Select Car' : 'No cars');
                populate(selRacer, racers, restoreRacer, racers.length ? 'Select Racer' : 'No racers');
            } catch (err) {
                console.error('get_options failed:', err);
                resetSelect(selTrack, 'Error loading');
                resetSelect(selCar, 'Error loading');
                resetSelect(selRacer, 'Error loading');
            }
        }

        selVersion.addEventListener('change', function () {
            selVersion.classList.toggle('empty', this.value === '');
            if (this.value) {
                loadOptions(this.value);
            } else {
                resetSelect(selTrack, 'Select Version First');
                resetSelect(selCar, 'Select Version First');
                resetSelect(selRacer, 'Select Version First');
            }
        });

        [selTrack, selCar, selRacer].forEach(el => {
            el.addEventListener('change', function () {
                this.classList.toggle('empty', this.value === '');
            });
        });

        if (selVersion.value) {
            loadOptions(selVersion.value, savedTrack, savedCar, savedRacer);
        }
    })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>