<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$conn = getConnection();

// ── Bulk delete POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'bulk_delete') {
    $ids = array_filter(array_map('intval', $_POST['session_ids'] ?? []));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types        = str_repeat('i', count($ids));
        $stmt = $conn->prepare("DELETE FROM sessions WHERE session_id IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $stmt->close();
        $deleted = $conn->affected_rows;
        $_SESSION['flash'] = ['type' => 'success', 'message' => "$deleted session(s) deleted."];
    }
    $redirect = $_POST['redirect'] ?? 'sessions.php';
    $conn->close();
    header('Location: ' . $redirect);
    exit();
}

// ── Fetch ─────────────────────────────────────────────────────
$filterEventId = isset($_GET['event_id']) && (int) $_GET['event_id'] > 0
    ? (int) $_GET['event_id']
    : 0;

$allEvents = $conn->query('
    SELECT event_id, event_name
    FROM   events
    ORDER  BY event_date DESC
')->fetch_all(MYSQLI_ASSOC);

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

$activeEventName = 'All Events';
if ($filterEventId > 0) {
    foreach ($allEvents as $ev) {
        if ((int) $ev['event_id'] === $filterEventId) {
            $activeEventName = $ev['event_name'];
            break;
        }
    }
}

$pageTitle = 'Sessions';
include __DIR__ . '/../includes/header.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>

<?php if (isset($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= h($flash['type']) ?> mb-4">
        <?= h($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="page-header">
    <h2>Sessions — <?= h($activeEventName) ?></h2>
    <a href="manage_events.php" class="btn btn-secondary">← Back to Events</a>
</div>

<!-- Filter Bar -->
<form method="GET" class="d-flex align-items-center gap-2 mb-4">
    <label for="event_id" class="form-label mb-0">Filter by Event</label>
    <select name="event_id" id="event_id" class="form-select w-auto">
        <option value="0">All Events</option>
        <?php foreach ($allEvents as $ev): ?>
            <option value="<?= (int) $ev['event_id'] ?>"
                <?= (int) $ev['event_id'] === $filterEventId ? 'selected' : '' ?>>
                <?= h($ev['event_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Apply</button>
    <?php if ($filterEventId > 0): ?>
        <a href="sessions.php" class="btn btn-secondary">Clear</a>
    <?php endif; ?>
</form>

<!-- Sessions Table -->
<div class="card">
    <?php if (empty($sessions)): ?>
        <p class="empty-state">No sessions found for this event.</p>
    <?php else: ?>

        <form method="POST" id="bulkForm">
            <input type="hidden" name="_action"  value="bulk_delete">
            <input type="hidden" name="redirect" value="sessions.php?event_id=<?= $filterEventId ?>">

            <!-- Bulk toolbar -->
            <div class="bulk-toolbar d-flex align-items-center gap-2 px-3 py-2 border-bottom" id="bulkToolbar">
                <span id="bulkCount" class="flex-grow-1 text-muted small text-uppercase">0 selected</span>
                <button type="button" class="btn btn-secondary btn-sm" id="btnSelectAll">Select All</button>
                <button type="submit" class="btn btn-danger btn-sm"
                        onclick="return confirmBulkDelete()">Delete Selected</button>
            </div>

            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="checkAll" class="row-check"></th>
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
                                    <input type="checkbox"
                                           name="session_ids[]"
                                           value="<?= (int) $s['session_id'] ?>"
                                           class="row-check session-check">
                                </td>
                                <td>
                                    <a href="sessions.php?event_id=<?= (int) $s['event_id'] ?>"
                                       class="table-link">
                                        <?= h($s['event_name'] ?? '—') ?>
                                    </a>
                                </td>
                                <td><?= h($s['participant_name'] ?? '—') ?></td>
                                <td><strong><?= $s['best_lap_time'] !== '' ? h($s['best_lap_time']) : '—' ?></strong></td>
                                <td><?= $s['created_at'] ? date('M j, Y', strtotime($s['created_at'])) : '—' ?></td>
                                <td>
                                    <form method="POST" action="session_delete.php"
                                          onsubmit="return confirm('Delete this session?')">
                                        <input type="hidden" name="session_id" value="<?= (int) $s['session_id'] ?>">
                                        <input type="hidden" name="redirect"
                                               value="sessions.php?event_id=<?= $filterEventId ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>

    <?php endif; ?>
</div>

<script>
(function () {
    const checkAll   = document.getElementById('checkAll');
    const toolbar    = document.getElementById('bulkToolbar');
    const countLabel = document.getElementById('bulkCount');
    const btnAll     = document.getElementById('btnSelectAll');
    const checks     = () => [...document.querySelectorAll('.session-check')];

    function updateToolbar() {
        const selected = checks().filter(c => c.checked);
        const count    = selected.length;
        const total    = checks().length;

        countLabel.textContent = count === 0
            ? '0 selected'
            : `${count} of ${total} selected`;

        toolbar.classList.toggle('has-selection', count > 0);
        countLabel.classList.toggle('text-muted', count === 0);
        countLabel.classList.toggle('text-white',  count > 0);

        checkAll.checked       = count === total && total > 0;
        checkAll.indeterminate = count > 0 && count < total;
        btnAll.textContent     = count === total ? 'Deselect All' : 'Select All';

        checks().forEach(c => {
            c.closest('tr').classList.toggle('row-dimmed', count > 0 && !c.checked);
        });
    }

    checkAll.addEventListener('change', () => {
        checks().forEach(c => c.checked = checkAll.checked);
        updateToolbar();
    });

    btnAll.addEventListener('click', () => {
        const allChecked = checks().every(c => c.checked);
        checks().forEach(c => c.checked = !allChecked);
        updateToolbar();
    });

    document.querySelectorAll('.session-check').forEach(c => {
        c.addEventListener('change', updateToolbar);
    });

    updateToolbar();
})();

function confirmBulkDelete() {
    const count = document.querySelectorAll('.session-check:checked').length;
    if (count === 0) { alert('No sessions selected.'); return false; }
    return confirm(`Delete ${count} session(s)? This cannot be undone.`);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
