<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$event_id = (int) ($_GET['event_id'] ?? 0);

$conn = getConnection();

// Events for filter dropdown
$events = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_date DESC")->fetch_all(MYSQLI_ASSOC);

// Prepare main query: get each session's best lap, optionally filtered by event
if ($event_id > 0) {
    $stmt = $conn->prepare(
        "SELECT l.id, l.session_id, l.lap_number, l.lap_time_ms, l.lap_time, s.participant_name, s.event_id
         FROM laps l
         JOIN sessions s ON s.session_id = l.session_id
         WHERE l.lap_time_ms = (
            SELECT MIN(l2.lap_time_ms) FROM laps l2 WHERE l2.session_id = l.session_id
         )
         AND s.event_id = ?
         ORDER BY l.lap_time_ms ASC"
    );
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $rows = $conn->query(
        "SELECT l.id, l.session_id, l.lap_number, l.lap_time_ms, l.lap_time, s.participant_name, s.event_id
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
    <link rel="stylesheet" href="/assets/css/results.css">
    <style>
        .leaderboard-container { max-width: 900px; width:100%; }
        .leaderboard-table thead th { font-family: Inter, sans-serif; }
        .filter-row { display:flex; gap:12px; align-items:center; margin-bottom:16px; }
        @media (max-width:600px){ .filter-row{flex-direction:column; align-items:stretch} }
    </style>
</head>
<body>
    <div class="results-container leaderboard-container">
        <div class="results-header">
            <h1>🏆 Leaderboard</h1>
            <p class="session-label">Best lap across sessions<?= $event_id ? ' — filtered by event' : '' ?></p>
        </div>

        <div class="filter-row">
            <form method="GET" style="flex:1; display:flex; gap:8px;">
                <select name="event_id" onchange="this.form.submit()">
                    <option value="">— All Events —</option>
                    <?php foreach ($events as $ev): ?>
                        <option value="<?= $ev['event_id'] ?>" <?= $event_id == $ev['event_id'] ? 'selected' : '' ?>><?= htmlspecialchars($ev['event_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="btn-back">Filter</button></noscript>
            </form>
            <div style="white-space:nowrap">
                <a class="btn-back" href="/simulation.php">Open Simulator</a>
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
                        <tr <?= $pos === 1 ? 'class="best-row"' : '' ?> >
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
<?php include __DIR__ . '/../includes/footer.php'; ?>