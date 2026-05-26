<?php
// ============================================================
//  pages/session_form.php  —  Add / Edit a session
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
checkSessionTimeout();

$pdo       = getDB();
$sessionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$eventId   = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
$isEdit    = $sessionId > 0;
$errors    = [];
$values    = ['participant_name' => '', 'car' => '', 'track' => '', 'best_lap_time' => ''];

// Validate event exists
$stmt = $pdo->prepare('SELECT event_id, event_name FROM events WHERE event_id = ? LIMIT 1');
$stmt->execute([$eventId]);
$event = $stmt->fetch();
if (!$event) {
    setFlash('error', 'Event not found.');
    redirect('pages/dashboard.php');
}

// Load existing session for edit
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM sessions WHERE session_id = ? AND event_id = ? LIMIT 1');
    $stmt->execute([$sessionId, $eventId]);
    $session = $stmt->fetch();
    if (!$session) {
        setFlash('error', 'Session not found.');
        redirect('pages/event_detail.php?id=' . $eventId);
    }
    $values = $session;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $values['participant_name'] = trim($_POST['participant_name'] ?? '');
    $values['car']              = trim($_POST['car']              ?? '');
    $values['track']            = trim($_POST['track']            ?? '');
    $values['best_lap_time']    = trim($_POST['best_lap_time']    ?? '');

    if ($values['participant_name'] === '') $errors[] = 'Participant name is required.';
    if ($values['best_lap_time']    === '') {
        $errors[] = 'Lap time is required.';
    } elseif (!isValidLapTime($values['best_lap_time'])) {
        $errors[] = 'Lap time format is invalid. Use mm:ss.mmm (e.g., 01:23.456).';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $pdo->prepare(
                'UPDATE sessions SET participant_name=?, car=?, track=?, best_lap_time=? WHERE session_id=?'
            );
            $stmt->execute([
                $values['participant_name'], $values['car'],
                $values['track'], $values['best_lap_time'], $sessionId
            ]);
            setFlash('success', 'Session updated.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO sessions (event_id, participant_name, car, track, best_lap_time, source)
                 VALUES (?,?,?,?,?,\'manual\')'
            );
            $stmt->execute([
                $eventId, $values['participant_name'],
                $values['car'], $values['track'], $values['best_lap_time']
            ]);
            setFlash('success', 'Session recorded successfully.');
        }
        redirect('pages/event_detail.php?id=' . $eventId);
    }
}

$pageTitle = $isEdit ? 'Edit Session' : 'Add Session';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <a href="<?= BASE_URL ?>/pages/event_detail.php?id=<?= $eventId ?>" class="back-link">
        ← Back to <?= e($event['event_name']) ?>
    </a>
</div>
<h2><?= $isEdit ? 'Edit Session' : 'Add New Session' ?></h2>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="" class="card-form">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="event_id"   value="<?= $eventId ?>">

    <div class="form-group">
        <label for="participant_name">Participant Name <span class="required">*</span></label>
        <input type="text" id="participant_name" name="participant_name"
               value="<?= e($values['participant_name']) ?>" required autofocus>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="car">Car <span class="optional">(optional)</span></label>
            <input type="text" id="car" name="car"
                   placeholder="e.g. Red Bull RB20"
                   value="<?= e($values['car'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="track">Track <span class="optional">(optional)</span></label>
            <input type="text" id="track" name="track"
                   placeholder="e.g. Monza"
                   value="<?= e($values['track'] ?? '') ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="best_lap_time">Best Lap Time <span class="required">*</span> <small>(mm:ss.mmm)</small></label>
        <input type="text" id="best_lap_time" name="best_lap_time"
               placeholder="01:23.456"
               pattern="\d{2}:\d{2}\.\d{3}"
               value="<?= e($values['best_lap_time']) ?>" required>
        <span class="field-hint">Format: mm:ss.mmm — e.g. 01:23.456</span>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">
            <?= $isEdit ? 'Save Changes' : 'Save Session' ?>
        </button>
        <a href="<?= BASE_URL ?>/pages/event_detail.php?id=<?= $eventId ?>" class="btn btn--outline">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
