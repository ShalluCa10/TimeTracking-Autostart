<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$conn = getConnection();

// ── Handle DELETE ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    $delId = (int) ($_POST['event_id'] ?? 0);
    if ($delId > 0) {
        $stmt = $conn->prepare('DELETE FROM events WHERE event_id = ?');
        $stmt->bind_param('i', $delId);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Event deleted.');
    }
    $conn->close();
    header('Location: manage_events.php');
    exit();
}

// ── Load all events ───────────────────────────────────────
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

$flash     = getFlash();
$pageTitle = 'Manage Events';
include __DIR__ . '/../includes/header.php';
?>

<main class="container">
    <div class="page-heading">
        <h1 class="page-title">Manage Events</h1>
        <a href="event_form.php" class="btn btn--primary">+ New Event</a>
    </div>

    <?php if ($flash): ?>
        <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <section class="card">
        <?php if (empty($events)): ?>
            <p class="empty-state">No events yet. Create one to get started.</p>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Version</th>
                        <th>Track</th>
                        <th>Car</th>
                        <th>Racer</th>
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
                        <td><strong><?= htmlspecialchars($event['event_name']) ?></strong></td>
                        <td><?= htmlspecialchars($event['event_date']) ?></td>
                        <td><?= htmlspecialchars($event['version_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($event['track'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($event['car']   ?? '—') ?></td>
                        <td><?= htmlspecialchars($event['racer'] ?? '—') ?></td>
                        <td><?= (int) $event['session_count'] ?></td>
                        <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                        <td class="actions">

                            <a href="event_form.php?id=<?= $event['event_id'] ?>"
                               class="btn btn--outline btn--sm">Edit</a>

                            <a href="sessions.php?event_id=<?= $event['event_id'] ?>"
                               class="btn btn--outline btn--sm">Sessions</a>

                            <?php if ($statusKey === 'live'): ?>
                            <form method="POST" action="event_status.php">
                                <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                <input type="hidden" name="status"   value="completed">
                                <input type="hidden" name="redirect" value="manage_events.php">
                                <button type="submit" class="btn btn--outline btn--sm">End Event</button>
                            </form>

                            <?php elseif ($statusKey === 'upcoming'): ?>
                            <form method="POST" action="event_status.php">
                                <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                <input type="hidden" name="status"   value="live">
                                <input type="hidden" name="redirect" value="manage_events.php">
                                <button type="submit" class="btn btn--outline btn--sm">Force Live</button>
                            </form>
                            <?php endif; ?>

                            <?php if ($statusKey !== 'canceled' && $statusKey !== 'completed'): ?>
                            <form method="POST" action="event_status.php"
                                  onsubmit="return confirm('Cancel this event?')">
                                <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                <input type="hidden" name="status"   value="canceled">
                                <input type="hidden" name="redirect" value="manage_events.php">
                                <button type="submit" class="btn btn--danger btn--sm">Cancel</button>
                            </form>
                            <?php else: ?>
                                <button class="btn btn--danger btn--sm btn--disabled" disabled>Cancel</button>
                            <?php endif; ?>

                            <form method="POST"
                                  onsubmit="return confirm('Delete this event and all its sessions? This cannot be undone.')">
                                <input type="hidden" name="_action"  value="delete">
                                <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                <button type="submit" class="btn btn--danger btn--sm">Delete</button>
                            </form>

                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
