<?php
// ============================================================
//  pages/event_form.php  —  Create / Edit an event
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
checkSessionTimeout();

$pdo      = getDB();
$eventId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit   = $eventId > 0;
$errors   = [];
$values   = ['event_name' => '', 'event_date' => '', 'location' => '', 'notes' => ''];

// Load existing event for edit mode
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE event_id = ? LIMIT 1');
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    if (!$event) {
        setFlash('error', 'Event not found.');
        redirect('pages/dashboard.php');
    }
    $values = $event;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $values['event_name'] = trim($_POST['event_name'] ?? '');
    $values['event_date'] = trim($_POST['event_date'] ?? '');
    $values['location']   = trim($_POST['location']   ?? '');
    $values['notes']      = trim($_POST['notes']      ?? '');

    // Validation
    if ($values['event_name'] === '') $errors[] = 'Event name is required.';
    if ($values['event_date'] === '') {
        $errors[] = 'Please enter a valid date.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $values['event_date'])) {
        $errors[] = 'Date must be in YYYY-MM-DD format.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            $stmt = $pdo->prepare(
                'UPDATE events SET event_name=?, event_date=?, location=?, notes=? WHERE event_id=?'
            );
            $stmt->execute([
                $values['event_name'], $values['event_date'],
                $values['location'],   $values['notes'], $eventId
            ]);
            setFlash('success', 'Event updated.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO events (event_name, event_date, location, notes) VALUES (?,?,?,?)'
            );
            $stmt->execute([
                $values['event_name'], $values['event_date'],
                $values['location'],   $values['notes']
            ]);
            setFlash('success', 'Event created successfully.');
        }
        redirect('pages/dashboard.php');
    }
}

$pageTitle = $isEdit ? 'Edit Event' : 'Create Event';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="back-link">← Back to Events</a>
</div>
<h2><?= $isEdit ? 'Edit Event' : 'Create New Event' ?></h2>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="" class="card-form">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div class="form-group">
        <label for="event_name">Event Name <span class="required">*</span></label>
        <input type="text" id="event_name" name="event_name"
               value="<?= e($values['event_name']) ?>" required>
    </div>

    <div class="form-group">
        <label for="event_date">Date <span class="required">*</span></label>
        <input type="date" id="event_date" name="event_date"
               value="<?= e($values['event_date']) ?>" required>
    </div>

    <div class="form-group">
        <label for="location">Location</label>
        <input type="text" id="location" name="location"
               value="<?= e($values['location']) ?>">
    </div>

    <div class="form-group">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="3"><?= e($values['notes']) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">
            <?= $isEdit ? 'Save Changes' : 'Create Event' ?>
        </button>
        <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn btn--outline">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
