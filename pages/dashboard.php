<?php
// ============================================================
//  pages/dashboard.php  —  Event list (dashboard)
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
checkSessionTimeout();

$pdo    = getDB();
$events = $pdo->query(
    'SELECT * FROM events ORDER BY event_date DESC, created_at DESC'
)->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h2>Events</h2>
    <a href="<?= BASE_URL ?>/pages/event_form.php" class="btn btn--primary">+ Create New Event</a>
</div>

<?php if (empty($events)): ?>
    <div class="empty-state">
        <p>No events yet. Click <strong>+ Create New Event</strong> to get started.</p>
    </div>
<?php else: ?>
    <div class="event-list">
        <?php foreach ($events as $ev): ?>
        <div class="event-card">
            <div class="event-card__info">
                <h3><?= e($ev['event_name']) ?></h3>
                <p>
                    <?= e(date('F j, Y', strtotime($ev['event_date']))) ?>
                    <?php if ($ev['location']): ?> | <?= e($ev['location']) ?><?php endif; ?>
                </p>
            </div>
            <div class="event-card__actions">
                <a href="<?= BASE_URL ?>/pages/event_detail.php?id=<?= $ev['event_id'] ?>"
                   class="btn btn--sm">View</a>
                <a href="<?= BASE_URL ?>/pages/event_form.php?id=<?= $ev['event_id'] ?>"
                   class="btn btn--sm btn--outline">Edit</a>
                <button class="btn btn--sm btn--danger"
                        onclick="confirmDelete(<?= $ev['event_id'] ?>, '<?= e(addslashes($ev['event_name'])) ?>')">
                    Delete
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Hidden delete form -->
<form id="deleteEventForm" method="POST" action="<?= BASE_URL ?>/pages/event_delete.php">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="event_id" id="deleteEventId">
</form>

<script>
function confirmDelete(id, name) {
    if (confirm('Are you sure you want to delete "' + name + '"?\nThis will also remove ALL associated sessions.')) {
        document.getElementById('deleteEventId').value = id;
        document.getElementById('deleteEventForm').submit();
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
