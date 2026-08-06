<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/helpers.php';

requireLogin();

$conn = getConnection();

// ── Bulk delete POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'bulk_delete') {
    $ids = array_filter(array_map('intval', $_POST['session_ids'] ?? []));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
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
$filterScheduleId = isset($_GET['schedule_id']) && (int) $_GET['schedule_id'] > 0
    ? (int) $_GET['schedule_id']
    : 0;

$allSchedules = $conn->query('
    SELECT schedule_id, schedule_name
    FROM   schedules
    ORDER  BY schedule_date DESC
')->fetch_all(MYSQLI_ASSOC);

if ($filterScheduleId > 0) {
    $stmt = $conn->prepare('
        SELECT s.session_id,
               s.participant_name,
               s.best_lap_time,
               s.created_at,
               e.schedule_name,
               e.schedule_id
        FROM   sessions s
        LEFT JOIN schedules e ON e.schedule_id = s.schedule_id
        WHERE  s.schedule_id = ?
        ORDER  BY s.created_at DESC
    ');
    $stmt->bind_param('i', $filterScheduleId);
    $stmt->execute();
    $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $sessions = $conn->query('
        SELECT s.session_id,
               s.participant_name,
               s.best_lap_time,
               s.created_at,
               e.schedule_name,
               e.schedule_id
        FROM   sessions s
        LEFT JOIN schedules e ON e.schedule_id = s.schedule_id
        ORDER  BY s.created_at DESC
    ')->fetch_all(MYSQLI_ASSOC);
}

$conn->close();

$activeScheduleName = 'All Schedules';
if ($filterScheduleId > 0) {
    foreach ($allSchedules as $sc) {
        if ((int) $sc['schedule_id'] === $filterScheduleId) {
            $activeScheduleName = $sc['schedule_name'];
            break;
        }
    }
}

$pageTitle = 'Sessions';
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

<div class="page-header">
    <h2>Sessions — <?= h($activeScheduleName) ?></h2>
</div>

<!-- Filter Bar -->
<form method="GET" class="d-flex align-items-center gap-2 mb-4">
    <label for="schedule_id" class="form-label mb-0">Filter by Schedule</label>
    <select name="schedule_id" id="schedule_id" class="form-select w-auto">
        <option value="0">All Schedules</option>
        <?php foreach ($allSchedules as $sc): ?>
            <option value="<?= (int) $sc['schedule_id'] ?>" <?= (int) $sc['schedule_id'] === $filterScheduleId ? 'selected' : '' ?>>
                <?= h($sc['schedule_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Apply</button>
    <?php if ($filterScheduleId > 0): ?>
        <a href="sessions.php" class="btn btn-secondary">Clear</a>
    <?php endif; ?>
</form>

<!-- Sessions Table -->
<div class="card">
    <?php if (empty($sessions)): ?>
        <p class="empty-state">No sessions found for this schedule.</p>
    <?php else: ?>

        <form method="POST" id="bulkForm">
            <input type="hidden" name="_action" value="bulk_delete">
            <input type="hidden" name="redirect" value="sessions.php?schedule_id=<?= $filterScheduleId ?>">

            <!-- Bulk toolbar -->
            <div class="bulk-toolbar d-flex align-items-center gap-2 px-3 py-2 border-bottom" id="bulkToolbar">
                <span id="bulkCount" class="flex-grow-1 text-muted small text-uppercase">0 selected</span>
                <button type="button" class="btn btn-secondary btn-sm" id="btnSelectAll">Select All</button>
                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirmBulkDelete()">Delete
                    Selected</button>
            </div>

            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="checkAll" class="row-check"></th>
                            <th>Schedule</th>
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
                                    <input type="checkbox" name="session_ids[]" value="<?= (int) $s['session_id'] ?>"
                                        class="row-check session-check">
                                </td>
                                <td>
                                    <a href="sessions.php?schedule_id=<?= (int) $s['schedule_id'] ?>" class="table-link">
                                        <?= h($s['schedule_name'] ?? '—') ?>
                                    </a>
                                </td>
                                <td><?= h($s['participant_name'] ?? '—') ?></td>
                                <td><strong><?= $s['best_lap_time'] !== '' ? h($s['best_lap_time']) : '—' ?></strong></td>
                                <td><?= $s['created_at'] ? date('M j, Y', strtotime($s['created_at'])) : '—' ?></td>
                                <td>
                                    <a href="session_form.php?id=<?= (int) $s['session_id'] ?>&schedule_id=<?= (int) $s['schedule_id'] ?>"
                                        class="btn btn-secondary btn-sm">Edit</a>
                                    <button type="submit" form="delete-session-<?= (int) $s['session_id'] ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this session?')">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <?php foreach ($sessions as $s): ?>
            <form id="delete-session-<?= (int) $s['session_id'] ?>" method="POST" action="session_delete.php" style="display:none;">
                <input type="hidden" name="session_id" value="<?= (int) $s['session_id'] ?>">
                <input type="hidden" name="redirect" value="sessions.php?schedule_id=<?= $filterScheduleId ?>">
            </form>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<script>
    (function () {
        const checkAll = document.getElementById('checkAll');
        const toolbar = document.getElementById('bulkToolbar');
        const countLabel = document.getElementById('bulkCount');
        const btnAll = document.getElementById('btnSelectAll');
        const checks = () => [...document.querySelectorAll('.session-check')];

        function updateToolbar() {
            const selected = checks().filter(c => c.checked);
            const count = selected.length;
            const total = checks().length;

            countLabel.textContent = count === 0
                ? '0 selected'
                : `${count} of ${total} selected`;

            toolbar.classList.toggle('has-selection', count > 0);
            countLabel.classList.toggle('text-muted', count === 0);
            countLabel.classList.toggle('text-white', count > 0);

            checkAll.checked = count === total && total > 0;
            checkAll.indeterminate = count > 0 && count < total;
            btnAll.textContent = count === total ? 'Deselect All' : 'Select All';

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

<?php include __DIR__ . '/../../../includes/footer.php'; ?>