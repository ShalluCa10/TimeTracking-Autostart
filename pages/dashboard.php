<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$conn = getConnection();
requireLogin();

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

$recentLaps = $conn->query('
    SELECT l.lap_number, l.lap_time, l.lap_time_ms,
           s.session_id,
           e.event_name,
           e.racer,
           e.track
    FROM   laps     l
    JOIN   sessions s ON s.session_id = l.session_id
    JOIN   events   e ON e.event_id   = s.event_id
    ORDER BY l.id DESC
    LIMIT 5
')->fetch_all(MYSQLI_ASSOC);

$conn->close();

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash'];
    unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> mb-4">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <h2>Dashboard</h2>
    <a href="event_form.php" class="btn btn-primary">+ New Event</a>
</div>

<!-- Events -->
<div class="card mb-4">
    <div class="card-header">
        <h3>Events</h3>
        <span class="text-muted"
            style="font-size:0.75rem; font-family:'Barlow Condensed',sans-serif; letter-spacing:0.05em;">
            <?= count($events) ?> total
        </span>
    </div>

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
                            <td><strong><?= htmlspecialchars($event['event_name']) ?></strong></td>
                            <td><?= htmlspecialchars($event['event_date']) ?></td>
                            <td>
                                <div class="event-details">
                                    <?php if (!empty($event['version_name'])): ?>
                                        <span class="detail-tag detail-version">
                                            <?= htmlspecialchars($event['version_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($event['track'])): ?>
                                        <span class="detail-tag detail-track">
                                            <?= htmlspecialchars($event['track']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($event['racer'])): ?>
                                        <span class="detail-tag detail-racer">
                                            <?= htmlspecialchars($event['racer']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= (int) $event['session_count'] ?></td>
                            <td>
                                <span class="status-badge status-<?= $statusKey ?>">
                                    <span class="status-dot"></span>
                                    <?= $statusLabel ?>
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
                                            <button type="submit" class="btn btn-secondary btn-sm">End Event</button>
                                        </form>
                                    <?php elseif ($statusKey === 'upcoming'): ?>
                                        <form method="POST" action="event_status.php">
                                            <input type="hidden" name="event_id" value="<?= $event['event_id'] ?>">
                                            <input type="hidden" name="status" value="live">
                                            <button type="submit" class="btn btn-secondary btn-sm">Force Live</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Laps -->
<div class="card mb-4">
    <div class="card-header">
        <h3>Recent Laps</h3>
        <span class="text-muted"
            style="font-size:0.75rem; font-family:'Barlow Condensed',sans-serif; letter-spacing:0.05em;">
            Last 5
        </span>
    </div>

    <?php if (empty($recentLaps)): ?>
        <p class="empty-state">No laps recorded yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Details</th>
                        <th>Lap #</th>
                        <th>Lap Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLaps as $lap): ?>
                        <tr>
                            <td><?= htmlspecialchars($lap['event_name']) ?></td>
                            <td>
                                <div class="event-details">
                                    <?php if (!empty($lap['track'])): ?>
                                        <span class="detail-tag detail-track">
                                            <?= htmlspecialchars($lap['track']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($lap['racer'])): ?>
                                        <span class="detail-tag detail-racer">
                                            <?= htmlspecialchars($lap['racer']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= (int) $lap['lap_number'] ?></td>
                            <td><strong><?= htmlspecialchars($lap['lap_time']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>