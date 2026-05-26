<?php
// ============================================================
//  pages/event_detail.php  —  Sessions list for one event
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
checkSessionTimeout();

$pdo     = getDB();
$eventId = (int)($_GET['id'] ?? 0);

// Load event
$stmt = $pdo->prepare('SELECT * FROM events WHERE event_id = ? LIMIT 1');
$stmt->execute([$eventId]);
$event = $stmt->fetch();
if (!$event) {
    setFlash('error', 'Event not found.');
    redirect('pages/dashboard.php');
}

// Load sessions sorted by best lap time ASC
$stmt = $pdo->prepare(
    'SELECT * FROM sessions WHERE event_id = ? ORDER BY best_lap_time ASC'
);
$stmt->execute([$eventId]);
$sessions = $stmt->fetchAll();

$pageTitle = e($event['event_name']);
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <a href="<?= BASE_URL ?>/pages/dashboard.php" class="back-link">← Back to Events</a>
</div>

<div class="event-heading">
    <div>
        <h2><?= e($event['event_name']) ?></h2>
        <p class="event-meta">
            <?= e(date('F j, Y', strtotime($event['event_date']))) ?>
            <?php if ($event['location']): ?> &nbsp;|&nbsp; <?= e($event['location']) ?><?php endif; ?>
            &nbsp;|&nbsp; <?= count($sessions) ?> session<?= count($sessions) !== 1 ? 's' : '' ?> recorded
        </p>
        <?php if ($event['notes']): ?>
            <p class="event-notes"><?= e($event['notes']) ?></p>
        <?php endif; ?>
    </div>
    <a href="<?= BASE_URL ?>/pages/session_form.php?event_id=<?= $eventId ?>" class="btn btn--primary">
        + Add Session
    </a>
</div>

<?php if (empty($sessions)): ?>
    <div class="empty-state">
        <p>No sessions recorded yet. Click <strong>+ Add Session</strong> to log the first run.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Participant Name</th>
                    <th>Car</th>
                    <th>Track</th>
                    <th>Lap Time</th>
                    <th>Source</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $i => $s): ?>
                <tr>
                    <td class="rank">
                        <?php if ($i === 0): ?>
                            <span class="medal gold">🥇</span>
                        <?php elseif ($i === 1): ?>
                            <span class="medal silver">🥈</span>
                        <?php elseif ($i === 2): ?>
                            <span class="medal bronze">🥉</span>
                        <?php else: ?>
                            <?= $i + 1 ?>th
                        <?php endif; ?>
                    </td>
                    <td><?= e($s['participant_name']) ?></td>
                    <td><?= e($s['car'] ?? '—') ?></td>
                    <td><?= e($s['track'] ?? '—') ?></td>
                    <td class="laptime"><?= e($s['best_lap_time']) ?></td>
                    <td><span class="badge badge--<?= $s['source'] ?>"><?= $s['source'] ?></span></td>
                    <td class="actions">
                        <a href="<?= BASE_URL ?>/pages/session_form.php?id=<?= $s['session_id'] ?>&event_id=<?= $eventId ?>"
                           class="btn btn--sm btn--outline">Edit</a>
                        <button class="btn btn--sm btn--danger"
                                onclick="confirmSessionDelete(<?= $s['session_id'] ?>, '<?= e(addslashes($s['participant_name'])) ?>')">
                            Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Hidden delete form -->
<form id="deleteSessionForm" method="POST" action="<?= BASE_URL ?>/pages/session_delete.php">
    <input type="hidden" name="csrf_token"  value="<?= csrfToken() ?>">
    <input type="hidden" name="event_id"    value="<?= $eventId ?>">
    <input type="hidden" name="session_id"  id="deleteSessionId">
</form>

<script>
function confirmSessionDelete(id, name) {
    if (confirm('Delete session for "' + name + '"? This cannot be undone.')) {
        document.getElementById('deleteSessionId').value = id;
        document.getElementById('deleteSessionForm').submit();
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
