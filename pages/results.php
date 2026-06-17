<?php
require_once '../config/db.php';

$session_id = intval($_GET['session_id'] ?? 0);

if (!$session_id) {
    header('Location: index.php');
    exit;
}

$conn = getConnection();

// Get session info
$sessionStmt = $conn->prepare("SELECT * FROM sessions WHERE session_id = ?");
$sessionStmt->bind_param('i', $session_id);
$sessionStmt->execute();
$session = $sessionStmt->get_result()->fetch_assoc();

// Get all laps for this session
$lapsStmt = $conn->prepare("SELECT * FROM laps WHERE session_id = ? ORDER BY lap_number ASC");
$lapsStmt->bind_param('i', $session_id);
$lapsStmt->execute();
$laps = $lapsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Calculate best lap
$bestLap = null;
if (!empty($laps)) {
    $bestLap = array_reduce($laps, function ($carry, $lap) {
        return ($carry === null || $lap['lap_time_ms'] < $carry['lap_time_ms']) ? $lap : $carry;
    });
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Results</title>
    <link rel="stylesheet" href="../assets/css/results.css">
</head>

<body>
    <div class="results-container">

        <div class="results-header">
            <h1>🏁 Session Results</h1>
            <p class="session-label">Session #<?= $session_id ?> &mdash;
                <?= $session ? htmlspecialchars($session['event_name'] ?? 'Unknown Event') : 'Unknown Event' ?>
            </p>
        </div>

        <?php if (empty($laps)): ?>
            <p class="no-laps">No laps recorded for this session.</p>
        <?php else: ?>

            <!-- Best Lap Banner -->
            <div class="best-lap-banner">
                <span class="best-label">⚡ Best Lap</span>
                <span class="best-time"><?= htmlspecialchars($bestLap['lap_time']) ?></span>
                <span class="best-lap-num">Lap <?= $bestLap['lap_number'] ?></span>
            </div>

            <!-- Lap Table -->
            <table class="lap-table">
                <thead>
                    <tr>
                        <th>Lap</th>
                        <th>Time</th>
                        <th>Gap to Best</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($laps as $lap): ?>
                        <?php
                        $isBest = $lap['id'] === $bestLap['id'];
                        $gapMs = $lap['lap_time_ms'] - $bestLap['lap_time_ms'];
                        $gap = $isBest ? '—' : '+' . number_format($gapMs / 1000, 3) . 's';
                        ?>
                        <tr class="<?= $isBest ? 'best-row' : '' ?>">
                            <td>Lap <?= $lap['lap_number'] ?></td>
                            <td><?= htmlspecialchars($lap['lap_time']) ?></td>
                            <td class="gap <?= $isBest ? 'gap-best' : 'gap-slow' ?>"><?= $gap ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Summary -->
            <div class="summary">
                <div class="summary-item">
                    <span class="summary-label">Total Laps</span>
                    <span class="summary-value"><?= count($laps) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Best Lap</span>
                    <span class="summary-value"><?= htmlspecialchars($bestLap['lap_time']) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Time</span>
                    <span class="summary-value">
                        <?php
                        $totalMs = array_sum(array_column($laps, 'lap_time_ms'));
                        $mins = floor($totalMs / 60000);
                        $secs = floor(($totalMs % 60000) / 1000);
                        $ms = $totalMs % 1000;
                        echo sprintf('%02d:%02d.%03d', $mins, $secs, $ms);
                        ?>
                    </span>
                </div>
            </div>

        <?php endif; ?>

        <div class="actions">
            <a href="simulation.php" class="btn-back">← Back to Events</a>
        </div>

    </div>
</body>

</html>