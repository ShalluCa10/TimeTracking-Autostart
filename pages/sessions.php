<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$conn = getConnection();

// ── Active filter ─────────────────────────────────────────
$filterEventId = isset($_GET['event_id']) && (int) $_GET['event_id'] > 0
    ? (int) $_GET['event_id']
    : 0;

// ── Load all events for the filter dropdown ───────────────
$allEvents = $conn->query('
    SELECT event_id, event_name
    FROM   events
    ORDER  BY event_date DESC
')->fetch_all(MYSQLI_ASSOC);

// ── Load sessions (filtered or all) ──────────────────────
if ($filterEventId > 0) {
    $stmt = $conn->prepare('
        SELECT s.session_id,
               s.participant_name,
               s.best_lap_time,
               s.created_at,
               e.event_name,
               e.event_id
        FROM   sessions s
        LEFT JOIN events e ON e.event_id = s.event_id
        WHERE  s.event_id = ?
        ORDER  BY s.created_at DESC
    ');
    $stmt->bind_param('i', $filterEventId);
    $stmt->execute();
    $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $sessions = $conn->query('
        SELECT s.session_id,
               s.participant_name,
               s.best_lap_time,
               s.created_at,
               e.event_name,
               e.event_id
        FROM   sessions s
        LEFT JOIN events e ON e.event_id = s.event_id
        ORDER  BY s.created_at DESC
    ')->fetch_all(MYSQLI_ASSOC);
}

$conn->close();

// ── Active event name for heading ─────────────────────────
$activeEventName = 'All Events';
if ($filterEventId > 0) {
    foreach ($allEvents as $ev) {
        if ((int) $ev['event_id'] === $filterEventId) {
            $activeEventName = htmlspecialchars($ev['event_name']);
            break;
        }
    }
}

$flash     = getFlash();
$pageTitle = 'Sessions';
include __DIR__ . '/../includes/header.php';
?>

<main class="container">

    <div class="page-heading">
        <h1 class="page-title">Sessions — <?= $activeEventName ?></h1>
        <a href="manage_events.php" class="btn btn--outline">Back to Events</a>
    </div>

    <?php if ($flash): ?>
        <div class="flash flash--<?= htmlspecialchars($flash['type']) ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Filter bar -->
    <form method="GET" class="filter-bar">
        <label for="event_id" class="filter-bar__label">Filter by Event</label>
        <select name="event_id" id="event_id" class="filter-bar__select">
            <option value="0">All Events</option>
            <?php foreach ($allEvents as $ev): ?>
                <option value="<?= $ev['event_id'] ?>"
                    <?= (int) $ev['event_id'] === $filterEventId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ev['event_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn--primary">Apply</button>
        <?php if ($filterEventId > 0): ?>
            <a href="sessions.php" class="btn btn--outline">Clear</a>
        <?php endif; ?>
    </form>

    <section class="card">
        <?php if (empty($sessions)): ?>
            <p class="empty-state">No sessions found for this event.</p>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Participant</th>
                        <th>Best Lap</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sessions as $s): ?>
                    <tr>
                        <td>
                            <a href="sessions.php?event_id=<?= $s['event_id'] ?>" class="table-link">
                                <?= htmlspecialchars($s['event_name'] ?? '—') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($s['participant_name'] ?? '—') ?></td>
                        <td><strong><?= $s['best_lap_time'] !== '' ? htmlspecialchars($s['best_lap_time']) : '—' ?></strong></td>
                        <td><?= $s['created_at'] ? date('M j, Y', strtotime($s['created_at'])) : '—' ?></td>
                        <td class="actions">
                            <form method="POST" action="session_delete.php">
                                <input type="hidden" name="session_id" value="<?= $s['session_id'] ?>">
                                <input type="hidden" name="redirect"   value="sessions.php?event_id=<?= $filterEventId ?>">
                                <button type="submit" class="btn btn--danger btn--sm"
                                        onclick="return confirm('Delete this session?')">Delete</button>
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
