<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$conn    = getConnection();
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit  = $eventId > 0;
$errors  = [];
$values  = ['event_name' => '', 'event_date' => '', 'location' => '', 'notes' => ''];

// Load existing event if editing
if ($isEdit) {
    $stmt = $conn->prepare('SELECT * FROM events WHERE event_id = ? LIMIT 1');
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();

    if (!$event) {
        header('Location: dashboard.php');
        exit();
    }

    $values = $event;
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $values['event_name'] = trim($_POST['event_name'] ?? '');
    $values['event_date'] = trim($_POST['event_date'] ?? '');
    $values['location'] = trim($_POST['location']   ?? '');
    $values['notes']  = trim($_POST['notes']      ?? '');

    if ($values['event_name'] == '') $errors[] = 'Event name is required.';
    if ($values['event_date'] == '') $errors[] = 'Date is required.';

    if (count($errors) == 0) {
        if ($isEdit) {
            $stmt = $conn->prepare(
                'UPDATE events SET event_name=?, event_date=?, location=?, notes=? WHERE event_id=?'
            );
            $stmt->bind_param(
                'ssssi',
                $values['event_name'],
                $values['event_date'],
                $values['location'],
                $values['notes'],
                $eventId
            );
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO events (event_name, event_date, location, notes) VALUES (?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'ssss',
                $values['event_name'],
                $values['event_date'],
                $values['location'],
                $values['notes']
            );
        }

        $stmt->execute();
        $stmt->close();
        $conn->close();

        setFlash('success', $isEdit ? 'Event updated.' : 'Event created.');
        header('Location: dashboard.php');
        exit();
    }
}

$conn->close();

$pageTitle = $isEdit ? 'Edit Event' : 'New Event';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <a href="dashboard.php" class="back-link">← Back</a>
    <h2><?php echo $pageTitle; ?></h2>
</div>

<?php if (count($errors) > 0) { ?>
    <div class="alert alert--error">
        <?php foreach ($errors as $err) { ?>
            <p><?php echo $err; ?></p>
        <?php } ?>
    </div>
<?php } ?>

<form method="POST" class="form">
    <div class="form-group">
        <label>Event Name</label>
        <input type="text" name="event_name"
               value="<?php echo htmlspecialchars($values['event_name']); ?>" required>
    </div>
    <div class="form-group">
        <label>Date</label>
        <input type="date" name="event_date"
               value="<?php echo htmlspecialchars($values['event_date']); ?>" required>
    </div>
    <div class="form-group">
        <label>Location</label>
        <input type="text" name="location"
               value="<?php echo htmlspecialchars($values['location']); ?>">
    </div>
    <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" rows="4"><?php echo htmlspecialchars($values['notes']); ?></textarea>
    </div>
    <button type="submit" class="btn btn--primary">
        <?php echo $isEdit ? 'Save Changes' : 'Create Event'; ?>
    </button>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
