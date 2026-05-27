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

// Load sessions for this event
$stmt = $conn->prepare('SELECT * FROM sessions WHERE event_id = ? ORDER BY best_lap_time ASC');
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
    <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
    <a href="session_form.php?event_id=<?php echo $eventId; ?>" class="btn btn--primary">
        + Add Session</a>

</div>

<p class="event-meta">
    <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
    <?php if ($event['location'] != '') { ?>
        &mdash; <?php echo htmlspecialchars($event['location']); ?>
    <?php } ?>
</p>

<?php if ($flash) { ?>
    <p class="alert alert--<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></p>
<?php } ?>

<?php if (count($sessions) == 0) { ?>
    <p class="empty-state">No sessions yet for this event.</p>
<?php } else { ?>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Participant</th>
                <th>Car</th>
                <th>Track</th>
                <th>Best Lap</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $rank = 1;
            foreach ($sessions as $session) { ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo htmlspecialchars($session['participant_name']); ?></td>
                    <td><?php echo htmlspecialchars($session['car']); ?></td>
                    <td><?php echo htmlspecialchars($session['track']); ?></td>
                    <td><strong><?php echo htmlspecialchars($session['best_lap_time']); ?></strong></td>
                    <td class="actions">
                        <a href="session_form.php?id=<?php echo $session['session_id']; ?>&event_id=<?php echo $eventId; ?>"
                            class="btn btn--outline">Edit</a>
                        <form method="POST" action="session_delete.php" style="display:inline">
                            <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                            <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                            <button type="submit" class="btn btn--danger" onclick="return confirm('Delete this session?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php } ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>