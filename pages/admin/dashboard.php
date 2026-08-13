<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

$conn = getConnection();
requireLogin();

$schedules = $conn->query('
    SELECT e.schedule_id, e.schedule_name, e.schedule_date,
           e.location, e.version_id, e.team AS team, e.event AS event, e.racer, e.notes,
           e.created_at, e.status,
           COUNT(s.session_id) AS session_count,
           gv.name             AS version_name
    FROM   schedules e
    LEFT JOIN sessions      s  ON s.schedule_id  = e.schedule_id
    LEFT JOIN game_versions gv ON gv.id           = e.version_id
    GROUP BY e.schedule_id
    ORDER BY e.schedule_date DESC
')->fetch_all(MYSQLI_ASSOC);

$recentLaps = $conn->query('
    SELECT l.lap_number, l.lap_time, l.lap_time_ms,
           s.session_id,
           e.schedule_name,
        e.team AS team,
        e.event AS event
    FROM   laps     l
    JOIN   sessions  s ON s.session_id  = l.session_id
    JOIN   schedules e ON e.schedule_id = s.schedule_id
    ORDER BY l.id DESC
    LIMIT 5
')->fetch_all(MYSQLI_ASSOC);

$conn->close();

$pageTitle = 'Dashboard';
include __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash'];
    unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> mb-4">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h2 class="h4 fw-bold text-uppercase mb-0">Dashboard</h2>
    <a href="schedules/schedule_form.php" class="btn btn-primary">+ New Schedule</a>
</div>

<!-- Schedules -->
<div class="card mb-4">
    <div class="card-header">
        <h3>Schedules</h3>
        <span class="text-muted"
            style="font-size:0.75rem; font-family:'Barlow Condensed',sans-serif; letter-spacing:0.05em;">
            <?= count($schedules) ?> total
        </span>
    </div>

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
                            <td><strong><?= htmlspecialchars($schedule['schedule_name']) ?></strong></td>
                            <td><?= htmlspecialchars($schedule['schedule_date']) ?></td>
                            <td>
                                <div class="schedule-details">
                                    <?php if (!empty($schedule['version_name'])): ?>
                                        <span class="detail-tag detail-version">
                                            <?= htmlspecialchars($schedule['version_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($schedule['event'])): ?>
                                        <span class="detail-tag detail-event">
                                            <?= htmlspecialchars($schedule['event']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($schedule['team'])): ?>
                                        <span class="detail-tag detail-team">
                                            <?= htmlspecialchars($schedule['team']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= (int) $schedule['session_count'] ?></td>
                            <td>
                                <span class="status-badge status-<?= $statusKey ?>">
                                    <span class="status-dot"></span>
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="schedules/schedule_form.php?id=<?= $schedule['schedule_id'] ?>"
                                        class="btn btn-secondary btn-sm">Edit</a>
                                    <a href="sessions/sessions.php?schedule_id=<?= $schedule['schedule_id'] ?>"
                                        class="btn btn-secondary btn-sm">Sessions</a>


                                    <?php if ($statusKey === 'live'): ?>
                                        <form method="POST" action="schedules/schedule_status.php">
                                            <input type="hidden" name="schedule_id" value="<?= $schedule['schedule_id'] ?>">
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="btn btn-secondary btn-sm">End Schedule</button>
                                        </form>
                                    <?php elseif ($statusKey === 'upcoming'): ?>
                                        <form method="POST" action="schedules/schedule_status.php">
                                            <input type="hidden" name="schedule_id" value="<?= $schedule['schedule_id'] ?>">
                                            <input type="hidden" name="status" value="live">
                                            <button type="submit" class="btn btn-secondary btn-sm">Force Live</button>
                                        </form>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-primary btn-sm start-f1-btn"
                                        data-schedule-id="<?= (int) $schedule['schedule_id'] ?>"
                                        data-schedule-name="<?= htmlspecialchars($schedule['schedule_name'], ENT_QUOTES) ?>">
                                        Start F1
                                    </button>
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
        <p class="text-muted py-4 mb-0">No laps recorded yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <thead>
                    <tr>
                        <th>Schedule</th>
                        <th>Details</th>
                        <th>Lap #</th>
                        <th>Lap Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLaps as $lap): ?>
                        <tr>
                            <td><?= htmlspecialchars($lap['schedule_name']) ?></td>
                            <td>
                                <div class="schedule-details">
                                    <?php if (!empty($lap['event'])): ?>
                                        <span class="detail-tag detail-event">
                                            <?= htmlspecialchars($lap['event']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($lap['team'])): ?>
                                        <span class="detail-tag detail-team">
                                            <?= htmlspecialchars($lap['team']) ?>
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
<script>
    document.querySelectorAll('.start-f1-btn').forEach(button => {

        button.addEventListener('click', async function () {

            const scheduleId = this.dataset.scheduleId;
            const scheduleName = this.dataset.scheduleName;

            const confirmed = confirm(
                `Start F1 for "${scheduleName}"?`
            );

            if (!confirmed) {
                return;
            }

            this.disabled = true;
            this.textContent = 'Starting...';

            try {

                const response = await fetch('/api/session.php', {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify({
                        api_key: 'changeme123',
                        schedule_id: Number(scheduleId),
                        participant_name: 'Staff',
                        f1_version: 'F1 24',
                        best_lap_time: ''
                    })
                });

                const result = await response.json();

                console.log('Session response:', result);

                if (!response.ok || !result.success) {

                    alert(
                        'Failed to start F1.\n\n' +
                        (result.error || 'Unknown error')
                    );

                    this.disabled = false;
                    this.textContent = 'Start F1';

                    return;
                }

                alert(
                    'F1 session started successfully!\n\n' +
                    'Session ID: ' + result.session_id
                );

                this.textContent = 'F1 Starting...';

            } catch (error) {

                console.error(error);

                alert(
                    'Could not connect to the PHP server.'
                );

                this.disabled = false;
                this.textContent = 'Start F1';
            }
        });

    });
</script>