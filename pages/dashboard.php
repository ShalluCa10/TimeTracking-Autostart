<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$conn   = getConnection();
$result = $conn->query('SELECT * FROM events ORDER BY event_date DESC');
$events = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

$flash     = getFlash();
$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Events</h2>
    <a href="event_form.php" class="btn btn--primary">+ New Event</a>
</div>
<div class="dashboard-intro">
    <p>Game Manager: create events, choose a track, car and F1 version for each session, then start simulations directly from an event.</p>
</div>

<?php if ($flash) { ?>
    <p class="alert alert--<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></p>
<?php } ?>

<?php if (count($events) == 0) { ?>
    <p class="empty-state">No events yet. Create one to get started.</p>
<?php } else { ?>
    <table class="table">
        <thead>
            <tr>
                <th>Event</th>
                <th>Date</th>
                <th>Location</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event) { ?>
            <tr>
                <td>
                    <a href="event_detail.php?id=<?php echo $event['event_id']; ?>">
                        <?php echo htmlspecialchars($event['event_name']); ?>
                    </a>
                </td>
                <td><?php echo date('M j, Y', strtotime($event['event_date'])); ?></td>
                <td><?php echo htmlspecialchars($event['location']); ?></td>
                <td class="actions">
                    <a href="event_form.php?id=<?php echo $event['event_id']; ?>" class="btn btn--outline">Edit</a>
                    <form method="POST" action="event_delete.php" style="display:inline">
                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                        <button type="submit" class="btn btn--danger"
                            onclick="return confirm('Delete this event and all its sessions?')">
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
