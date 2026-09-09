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

// Split podium (top 3) from the rest, and compute gap to the leader
$podium = array_slice($rows, 0, 3);
$rest = array_slice($rows, 3);
$leaderMs = $rows[0]['lap_time_ms'] ?? null;
$medals = ['🥇', '🥈', '🥉'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - F1 Simulator</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .leaderboard-container {
            max-width: 960px;
            width: 100%;
        }

        .lb-header h1 {
            color: #fff;
        }

        .filter-row {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 2rem;
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

        /* Podium */
        .podium {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            align-items: end;
            gap: 12px;
            margin-bottom: 2.5rem;
        }

        .podium-slot {
            order: 2;
            background: linear-gradient(160deg, #1a1a1a, #141414);
            border: 1px solid #2a2a2a;
            border-top: 3px solid #666;
            border-radius: 10px 10px 4px 4px;
            padding: 1.4rem 0.8rem 1.2rem;
            text-align: center;
            position: relative;
            transition: transform 0.2s;
        }

        .podium-slot:hover {
            transform: translateY(-4px);
        }

        .podium-slot.rank-1 {
            order: 2;
            border-top-color: #ffd447;
            padding-top: 2rem;
            box-shadow: 0 8px 28px rgba(255, 212, 71, 0.12);
        }

        .podium-slot.rank-2 {
            order: 1;
            border-top-color: #c9ccd1;
        }

        .podium-slot.rank-3 {
            order: 3;
            border-top-color: #d08a4f;
        }

        .podium-medal {
            font-size: 2rem;
            line-height: 1;
            margin-bottom: 0.4rem;
        }

        .podium-name {
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .podium-time {
            font-family: monospace;
            font-size: 1.4rem;
            font-weight: 800;
            color: #e10600;
            margin: 0.3rem 0;
        }

        .podium-slot.rank-1 .podium-time {
            color: #ffd447;
            font-size: 1.6rem;
        }

        .podium-session {
            font-size: 0.7rem;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .podium-session a {
            color: #777;
            text-decoration: none;
        }

        .podium-session a:hover {
            color: #e10600;
        }

        @media (max-width: 640px) {
            .podium {
                grid-template-columns: 1fr;
            }

            .podium-slot,
            .podium-slot.rank-1,
            .podium-slot.rank-2,
            .podium-slot.rank-3 {
                order: 0;
                padding-top: 1.4rem;
            }
        }

        /* Table */
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

        .lb-pos {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            color: #888;
        }

        .lb-gap {
            font-family: monospace;
            color: #888;
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
        <div class="results-header lb-header">
            <h1>Leaderboard</h1>
            <p class="session-label">Best lap across sessions<?= $schedule_id ? ' - filtered by schedule' : '' ?></p>
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
                <a class="btn-back" href="/simulation.php">Open Controller</a>
                <a class="btn-back" href="/">Back</a>
            </div>
        </div>

        <?php if (empty($rows)): ?>
            <p class="no-laps">No lap data available.</p>
        <?php else: ?>

            <?php if (!empty($podium)): ?>
                <div class="podium">
                    <?php foreach ($podium as $i => $r): ?>
                        <div class="podium-slot rank-<?= $i + 1 ?>">
                            <div class="podium-medal"><?= $medals[$i] ?></div>
                            <div class="podium-name"><?= htmlspecialchars($r['participant_name']) ?></div>
                            <div class="podium-time"><?= htmlspecialchars($r['lap_time']) ?></div>
                            <div class="podium-session">Lap <?= $r['lap_number'] ?> &middot;
                                <a href="/pages/results.php?session_id=<?= $r['session_id'] ?>">#<?= $r['session_id'] ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($rest)): ?>
                <table class="lap-table leaderboard-table">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Driver</th>
                            <th>Session</th>
                            <th>Lap</th>
                            <th>Time</th>
                            <th>Gap</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $pos = 3; foreach ($rest as $r): $pos++; ?>
                            <tr>
                                <td class="lb-pos">#<?= $pos ?></td>
                                <td><?= htmlspecialchars($r['participant_name']) ?></td>
                                <td><a href="/pages/results.php?session_id=<?= $r['session_id'] ?>">#<?= $r['session_id'] ?></a></td>
                                <td><?= $r['lap_number'] ?></td>
                                <td><?= htmlspecialchars($r['lap_time']) ?></td>
                                <td class="lb-gap">+<?= number_format(($r['lap_time_ms'] - $leaderMs) / 1000, 3) ?>s</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script>
        // Auto-refresh leaderboard every 5 seconds so live session finishes appear automatically
        setInterval(function() {
            // Only auto-refresh if user hasn't opened dropdown/interacting
            if (!document.hidden) {
                window.location.reload();
            }
        }, 5000);
    </script>
</body>
</html>
