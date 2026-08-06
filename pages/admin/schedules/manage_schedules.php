<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

requireLogin();

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    $delId = (int) ($_POST['schedule_id'] ?? 0);
    if ($delId > 0) {
        $stmt = $conn->prepare('DELETE FROM schedules WHERE schedule_id = ?');
        $stmt->bind_param('i', $delId);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Schedule deleted.'];
    }
    $conn->close();
    header('Location: manage_schedules.php');
    exit();
}

$schedules = $conn->query('
    SELECT e.schedule_id, e.schedule_name, e.schedule_date,
           e.location, e.version_id, e.team AS team, e.event AS event, e.racer, e.notes,
           e.created_at, e.status,
           COUNT(s.session_id) AS session_count,
           gv.name AS version_name
    FROM   schedules e
    LEFT JOIN sessions  s  ON s.schedule_id  = e.schedule_id
    LEFT JOIN game_versions gv ON gv.id  = e.version_id
    GROUP BY e.schedule_id
    ORDER BY e.schedule_date DESC
')->fetch_all(MYSQLI_ASSOC);

$conn->close();

$pageTitle = 'Manage Schedules';
include __DIR__ . '/../../../includes/header.php';

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>

<?php if (isset($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash'];
    unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= h($flash['type']) ?> mb-4">
        <?= h($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h2 class="h4 fw-bold text-uppercase mb-0">Manage Schedules</h2>
    <a href="schedule_form.php" class="btn btn-primary">+ New Schedule</a>
</div>

<div class="card">
    <?php if (empty($schedules)): ?>
        <p class="text-muted py-4 mb-0">No schedules yet. Create one to get started.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <thead>
                    <tr>
                        <th>Schedule</th>
                        <th>Date</th>
                        <th>Details</th>
                        <th>Sessions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                        <?php foreach ($schedules as $schedule):
                            [$statusKey, $statusLabel, $statusClass] = resolveScheduleStatus(
                                $schedule['status'] ?? 'auto',
                                $schedule['schedule_date']
                            );
                            ?>
                        <tr>
                            <td><strong><?= h($schedule['schedule_name']) ?></strong></td>
                            <td><?= h($schedule['schedule_date']) ?></td>
                            <td>
                                <div class="schedule-details">
                                        <?php if (!empty($schedule['version_name'])): ?>
                                        <span class="detail-tag detail-version">
                                                <?= h($schedule['version_name']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if (!empty($schedule['event'])): ?>
                                        <span class="detail-tag detail-event">
                                            <?= h($schedule['event']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if (!empty($schedule['team'])): ?>
                                        <span class="detail-tag detail-team">
                                            <?= h($schedule['team']) ?>
                                        </span>
                                        <?php endif; ?>
                                </div>
                            </td>
                            <td><?= (int) $schedule['session_count'] ?></td>
                            <td>
                                <span class="status-badge status-<?= $statusKey ?>">
                                    <span class="status-dot"></span>
                                        <?= h($statusLabel) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="schedule_form.php?id=<?= $schedule['schedule_id'] ?>"
                                        class="btn btn-secondary btn-sm">Edit</a>
                                    <a href="../sessions/sessions.php?schedule_id=<?= $schedule['schedule_id'] ?>"
                                        class="btn btn-secondary btn-sm">Sessions</a>
                                        <?php if ($statusKey === 'live'): ?>
                                        <form method="POST" action="schedule_status.php">
                                            <input type="hidden" name="schedule_id" value="<?= $schedule['schedule_id'] ?>">
                                            <input type="hidden" name="status" value="completed">
                                            <input type="hidden" name="redirect" value="manage_schedules.php">
                                            <button type="submit" class="btn btn-secondary btn-sm">End Schedule</button>
                                        </form>
                                        <?php elseif ($statusKey === 'upcoming'): ?>
                                        <form method="POST" action="schedule_status.php">
                                            <input type="hidden" name="schedule_id" value="<?= $schedule['schedule_id'] ?>">
                                            <input type="hidden" name="status" value="live">
                                            <input type="hidden" name="redirect" value="manage_schedules.php">
                                            <button type="submit" class="btn btn-secondary btn-sm">Go Live</button>
                                        </form>
                                        <?php endif; ?>
                                    <form method="POST"
                                        onsubmit="return confirm('Delete this schedule and all its sessions? This cannot be undone.')">
                                        <input type="hidden" name="_action" value="delete">
                                        <input type="hidden" name="schedule_id" value="<?= $schedule['schedule_id'] ?>">
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

<?php include __DIR__ . '/../../../includes/footer.php'; ?>