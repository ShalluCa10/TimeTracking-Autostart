<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$schedule_id = (int) ($_GET['schedule_id'] ?? 0);

$conn = getConnection();

// Schedules for filter dropdown
$schedules = $conn->query("SELECT schedule_id, schedule_name FROM schedules ORDER BY schedule_date DESC")->fetch_all(MYSQLI_ASSOC);

// Prepare main query: get each session's best lap, optionally filtered by schedule
if ($schedule_id > 0) {
    $stmt = $conn->prepare(
        "SELECT l.id, l.session_id, l.lap_number, l.lap_time_ms, l.lap_time, s.participant_name, s.schedule_id
         FROM laps l
         JOIN sessions s ON s.session_id = l.session_id
         WHERE l.lap_time_ms = (
            SELECT MIN(l2.lap_time_ms) FROM laps l2 WHERE l2.session_id = l.session_id
         )
         AND s.schedule_id = ?
         ORDER BY l.lap_time_ms ASC"
    );
    $stmt->bind_param('i', $schedule_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $rows = $conn->query(
        "SELECT l.id, l.session_id, l.lap_number, l.lap_time_ms, l.lap_time, s.participant_name, s.schedule_id
         FROM laps l
         JOIN sessions s ON s.session_id = l.session_id
         WHERE l.lap_time_ms = (
            SELECT MIN(l2.lap_time_ms) FROM laps l2 WHERE l2.session_id = l.session_id
         )
         ORDER BY l.lap_time_ms ASC"
    )->fetch_all(MYSQLI_ASSOC);
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard — F1 Lap Simulator</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .leaderboard-container {
            max-width: 900px;
            width: 100%;
        }

        .filter-row {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .filter-row form {
            flex: 1;
            display: flex;
            gap: 8px;
        }

        .filter-row select {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #333;
            background: #111;
            color: #fff;
            border-radius: 6px;
        }

        .leaderboard-table thead th {
            font-family: Inter, sans-serif;
        }

        .leaderboard-table tbody td a {
            color: #fff;
            text-decoration: none;
        }

        .leaderboard-table tbody td a:hover {
            color: #e10600;
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-row form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="results-container leaderboard-container">
        <div class="results-header">
            <h1>Leaderboard</h1>
            <p class="session-label">Best lap across sessions<?= $schedule_id ? ' — filtered by schedule' : '' ?></p>
        </div>

        <div class="filter-row">
            <form method="GET">
                <select name="schedule_id" onchange="this.form.submit()">
                    <option value="">All Schedules</option>
                    <?php foreach ($schedules as $sc): ?>
                        <option value="<?= $sc['schedule_id'] ?>" <?= $schedule_id == $sc['schedule_id'] ? 'selected' : '' ?>><?= htmlspecialchars($sc['schedule_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="btn-back">Filter</button></noscript>
            </form>
            <div class="d-flex gap-2">
                <a class="btn-back" href="/pages/simulation.php">Open Simulator</a>
                <a class="btn-back" href="/">Back</a>
            </div>
        </div>

        <?php if (empty($rows)): ?>
            <p class="no-laps">No lap data available.</p>
        <?php else: ?>
            <table class="lap-table leaderboard-table">
                <thead>
                    <tr>
                        <th>Pos</th>
                        <th>Driver</th>
                        <th>Session</th>
                        <th>Lap</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $pos = 0; foreach ($rows as $r): $pos++; ?>
                        <tr class="<?= $pos === 1 ? 'best-row' : '' ?>">
                            <td><?= $pos ?></td>
                            <td><?= htmlspecialchars($r['participant_name']) ?></td>
                            <td><a href="/pages/results.php?session_id=<?= $r['session_id'] ?>">#<?= $r['session_id'] ?></a></td>
                            <td><?= $r['lap_number'] ?></td>
                            <td><?= htmlspecialchars($r['lap_time']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
