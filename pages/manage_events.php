<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    $delId = (int) ($_POST['event_id'] ?? 0);
    if ($delId > 0) {
        $stmt = $conn->prepare('DELETE FROM events WHERE event_id = ?');
        $stmt->bind_param('i', $delId);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Event deleted.'];
    }
    $conn->close();
    header('Location: manage_events.php');
    exit();
}

$events = $conn->query('
    SELECT e.*,
           COUNT(s.session_id) AS session_count,
           gv.name             AS version_name
    FROM   events e
    LEFT JOIN sessions      s  ON s.event_id  = e.event_id
    LEFT JOIN game_versions gv ON gv.id        = e.version_id
    GROUP BY e.event_id
    ORDER BY e.event_date DESC
')->fetch_all(MYSQLI_ASSOC);

$conn->close();

$pageTitle = 'Manage Events';
include __DIR__ . '/../includes/header.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>

<?php if (isset($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= h($flash['type']) ?> mb-4">
        <?= h($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <h2>Manage Events</h2>
    <a href="event_form.php" class="btn btn-primary">+ New Event</a>
</div>

<div class="card">
    <?php if (empty($events)): ?>
        <p class="empty-state">No events yet. Create one to get started.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Details</th>
                        <th>Sessions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $event):
                    [$statusKey, $statusLabel, $statusClass] = resolveEventStatus(
                        $event['status'] ?? 'auto',
                        $event['event_date']
                    );
                ?>
                    <tr>
                        <td><strong><?= h($event['event_name']) ?></strong></td>
                        <td><?= h($event['event_date']) ?></td>
                        <td>
                            <div class="event-details">
                                <?php if (!empty($event['version_name'])): ?>
                                    <span class="detail-tag detail-version">
                                        <?= h($event['version_name']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($event['track'])): ?>
                                    <span class="detail-tag detail-track">
                                        <?= h($event['track']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($event['car'])): ?>
                                    <span class="detail-tag detail-car">
                                        <?= h($event['car']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($event['racer'])): ?>
                                    <span class="detail-tag detail-racer">
                                        <?= h($event['racer']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= (int) $event['session_count'] ?></td>
                        <td>
                            <span class="status-badge status-<?= $statusKey ?>">
                                <span class="status-dot"></span>
                                <?= h($statusLabel) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="event_form.php?id=<?= $event['event_id'] ?>"
                                    class="btn btn-secondary btn-sm">Edit</a>
                                <a href="sessions.php?event_id=<?= $event['event_id'] ?>"
                                    class="btn btn-secondary btn-sm">Sessions</a>
                                <?php if ($statusKey === 'live'): ?>
                                    <form method="POST" action="event_status.php">
                                        <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <input type="hidden" name="redirect" value="manage_events.php">
                                        <button type="submit" class="btn btn-secondary btn-sm">End Event</button>
                                    </form>
                                <?php elseif ($statusKey === 'upcoming'): ?>
                                    <form method="POST" action="event_status.php">
                                        <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                        <input type="hidden" name="status" value="live">
                                        <input type="hidden" name="redirect" value="manage_events.php">
                                        <button type="submit" class="btn btn-secondary btn-sm">Go Live</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST"
                                    onsubmit="return confirm('Delete this event and all its sessions? This cannot be undone.')">
                                    <input type="hidden" name="_action" value="delete">
                                    <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
