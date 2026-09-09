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
    <div class="d-flex align-items-center gap-2">
        <span id="rig-status-badge" class="badge bg-secondary">Rig status: checking...</span>
        <button id="stop-f1-btn" type="button" class="btn btn-outline-danger btn-sm d-none">Stop F1</button>
        <a href="/leaderboard.php" class="btn btn-outline-primary btn-sm">Leaderboard</a>
        <a href="schedules/schedule_form.php" class="btn btn-primary">+ New Schedule</a>
    </div>
</div>

<div id="session-completed-banner" class="alert alert-success d-none mb-4 d-flex justify-content-between align-items-center">
    <div>
        <strong id="completed-session-text">Session completed!</strong> Results are saved and ready to view.
    </div>
    <a id="view-leaderboard-btn" href="/leaderboard.php" class="btn btn-sm btn-success">View Leaderboard</a>
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script>
    const startButtons = Array.from(document.querySelectorAll('.start-f1-btn'));
    const rigStatusBadge = document.getElementById('rig-status-badge');
    const stopF1Button = document.getElementById('stop-f1-btn');
    const sessionCompletedBanner = document.getElementById('session-completed-banner');
    const completedSessionText = document.getElementById('completed-session-text');
    const viewLeaderboardBtn = document.getElementById('view-leaderboard-btn');
    let lastKnownCompletedId = null;
    let previousState = null;
    let rigStatusTimer = null;

    function setAllStartButtonsState(disabled, label = 'Start F1') {
        startButtons.forEach(button => {
            button.disabled = disabled;
            button.textContent = label;
        });
    }

    function formatStateLabel(status) {
        const state = (status && status.state) ? String(status.state).toUpperCase() : 'UNKNOWN';

        switch (state) {
            case 'READY':
                return 'Rig ready — Waiting for session';
            case 'STARTING':
                return 'Starting soon';
            case 'PLAYING':
                if (typeof status.remaining_seconds === 'number') {
                    const minutes = Math.floor(status.remaining_seconds / 60);
                    const seconds = Math.max(0, Math.round(status.remaining_seconds % 60));
                    return `In progress — ${minutes}:${String(seconds).padStart(2, '0')} remaining`;
                }
                return 'In progress';
            case 'ENDING':
                return 'Ending session & saving results';
            case 'ERROR':
                return status && status.error ? status.error : 'Rig error';
            default:
                return 'Checking rig status...';
        }
    }

    function applyRigStatus(status) {
        const state = status && typeof status === 'object' ? status : {};
        const readyState = (state.state || '').toUpperCase();
        const running = !!state.running;
        const isBusy = running || readyState === 'STARTING' || readyState === 'PLAYING' || readyState === 'ENDING';

        if (rigStatusBadge) {
            rigStatusBadge.textContent = `Rig status: ${formatStateLabel(state)}`;
            rigStatusBadge.className = 'badge ' + (
                readyState === 'READY' ? 'bg-success' :
                readyState === 'ERROR' ? 'bg-danger' :
                'bg-warning text-dark'
            );
        }

        if (stopF1Button) {
            const showStop = ['STARTING', 'PLAYING', 'ENDING'].includes(readyState) || running;
            stopF1Button.classList.toggle('d-none', !showStop);
            stopF1Button.disabled = !showStop;
        }

        // Handle completed session notification
        if (state.last_completed_session_id && state.last_completed_session_id !== lastKnownCompletedId) {
            lastKnownCompletedId = state.last_completed_session_id;
            if (sessionCompletedBanner) {
                completedSessionText.textContent = `Session #${lastKnownCompletedId} completed!`;
                if (state.schedule_id) {
                    viewLeaderboardBtn.href = `/leaderboard.php?schedule_id=${state.schedule_id}`;
                } else {
                    viewLeaderboardBtn.href = '/leaderboard.php';
                }
                sessionCompletedBanner.classList.remove('d-none');
            }
        }

        previousState = readyState;

        if (isBusy) {
            setAllStartButtonsState(true, 'Start F1');
            return;
        }

        setAllStartButtonsState(false, 'Start F1');
    }

    async function pollRigStatus() {
        try {
            const response = await fetch('/api/session.php?action=status', {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                if (rigStatusBadge) {
                    rigStatusBadge.textContent = `Rig status: ${result.error || 'Unable to reach rig status'}`;
                    rigStatusBadge.className = 'badge bg-danger';
                }
                return;
            }

            applyRigStatus(result.status || {});
        } catch (error) {
            console.error('Rig status poll failed:', error);
            if (rigStatusBadge) {
                rigStatusBadge.textContent = 'Rig status: unable to contact rig';
                rigStatusBadge.className = 'badge bg-danger';
            }
        }
    }

    startButtons.forEach(button => {
        button.addEventListener('click', async function () {
            const scheduleId = this.dataset.scheduleId;
            const scheduleName = this.dataset.scheduleName;
            const confirmed = confirm(`Start F1 for "${scheduleName}"?`);

            if (!confirmed) {
                return;
            }

            setAllStartButtonsState(true, 'Starting...');

            try {
                const response = await fetch('/api/session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
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
                    alert('Failed to start F1.\n\n' + (result.error || 'Unknown error'));
                    setAllStartButtonsState(false, 'Start F1');
                    return;
                }

                alert('F1 session started successfully!\n\nSession ID: ' + result.session_id);

                if (rigStatusTimer) {
                    clearInterval(rigStatusTimer);
                }

                rigStatusTimer = setInterval(pollRigStatus, 2000);
                await pollRigStatus();
            } catch (error) {
                console.error(error);
                alert('Could not connect to the PHP server.');
                setAllStartButtonsState(false, 'Start F1');
            }
        });
    });

    if (stopF1Button) {
        stopF1Button.addEventListener('click', async function () {
            try {
                const response = await fetch('/api/stop_python.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    alert('Failed to stop F1.\n\n' + (result.error || 'Unknown error'));
                    return;
                }

                alert('F1 stop requested.');
                await pollRigStatus();
            } catch (error) {
                console.error(error);
                alert('Could not contact the rig stop endpoint.');
            }
        });
    }

    pollRigStatus();
    rigStatusTimer = setInterval(pollRigStatus, 2000);
</script>