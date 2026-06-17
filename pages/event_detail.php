<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$conn = getConnection();
$eventId = (int) ($_GET['id'] ?? 0);

// Load event
$stmt = $conn->prepare('SELECT * FROM events WHERE event_id = ? LIMIT 1');
$stmt->bind_param('i', $eventId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    header('Location: dashboard.php');
    exit();
}

$stmt = $conn->prepare('SELECT * FROM sessions WHERE event_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $eventId);
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$flash = getFlash();
$pageTitle = htmlspecialchars($event['event_name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <a href="dashboard.php" class="back-link">← Back to Events</a>
    <h2><?= htmlspecialchars($event['event_name']) ?></h2>
    <div class="event-actions">
        <a href="session_form.php?event_id=<?= $eventId ?>" class="btn btn--primary">+ Add Session</a>
        <a href="simulation.php?event_id=<?= $eventId ?>" class="btn btn--success">▶ Start Simulation</a>
    </div>
</div>

<p class="event-meta">
    <?= date('F j, Y', strtotime($event['event_date'])) ?>
    <?php if ($event['location'] !== ''): ?>
        &mdash; <?= htmlspecialchars($event['location']) ?>
    <?php endif; ?>
</p>

<?php if ($flash): ?>
    <p class="alert alert--<?= $flash['type'] ?>"><?= $flash['message'] ?></p>
<?php endif; ?>

<?php if (count($sessions) === 0): ?>
    <p class="empty-state">No sessions yet for this event.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Game</th>
                <th>Driver</th>
                <th>Car</th>
                <th>Track</th>
                <th>Best Lap</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $rank = 1;
            foreach ($sessions as $session): ?>
                <tr>
                    <td><?= $rank++ ?></td>
                    <td><?= htmlspecialchars($session['f1_version'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($session['participant_name']) ?></td>
                    <td><?= htmlspecialchars($session['car']) ?></td>
                    <td><?= htmlspecialchars($session['track']) ?></td>
                    <td><strong><?= $session['best_lap_time'] !== '' ? htmlspecialchars($session['best_lap_time']) : '—' ?></strong>
                    </td>
                    <td class="actions">
                        <a href="session_form.php?id=<?= $session['session_id'] ?>&event_id=<?= $eventId ?>"
                            class="btn btn--outline">Edit</a>
                        <form method="POST" action="session_delete.php" style="display:inline">
                            <input type="hidden" name="session_id" value="<?= $session['session_id'] ?>">
                            <input type="hidden" name="event_id" value="<?= $eventId ?>">
                            <button type="submit" class="btn btn--danger"
                                onclick="return confirm('Delete session #<?= $session['session_id'] ?>?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>