<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_version') {
        $name = trim($_POST['version_name'] ?? '');
        if ($name) {
            $stmt = $conn->prepare('INSERT IGNORE INTO game_versions (name) VALUES (?)');
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash'] = ['type' => 'success', 'message' => "Version \"$name\" added."];
        }

    } elseif ($action === 'delete_version') {
        $id = (int) ($_POST['version_id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM game_versions WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Version and all its data deleted.'];
        }

    } elseif ($action === 'bulk_add') {
        $versionId = (int) ($_POST['version_id'] ?? 0);
        $type      = $_POST['item_type'] ?? '';
        $raw       = trim($_POST['items'] ?? '');

        $allowed = ['game_tracks', 'game_cars', 'game_racers'];
        $table   = 'game_' . $type;

        if ($versionId > 0 && in_array($table, $allowed) && $raw !== '') {
            $items = preg_split('/[\n,]+/', $raw);
            $stmt  = $conn->prepare("INSERT IGNORE INTO `$table` (version_id, name) VALUES (?, ?)");
            $count = 0;
            foreach ($items as $item) {
                $item = trim($item);
                if ($item !== '') {
                    $stmt->bind_param('is', $versionId, $item);
                    $stmt->execute();
                    $count++;
                }
            }
            $stmt->close();
            $_SESSION['flash'] = ['type' => 'success', 'message' => "$count item(s) added."];
        }

    } elseif ($action === 'delete_item') {
        $id    = (int) ($_POST['item_id']    ?? 0);
        $table = $_POST['item_table'] ?? '';

        $allowed = ['game_tracks', 'game_cars', 'game_racers'];
        if ($id > 0 && in_array($table, $allowed)) {
            $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item deleted.'];
        }
    }

    $conn->close();
    header('Location: manage_games.php');
    exit();
}

$versions = $conn->query('SELECT * FROM game_versions ORDER BY name ASC')
                 ->fetch_all(MYSQLI_ASSOC);

$activeVersionId = (int) ($_GET['version_id'] ?? ($versions[0]['id'] ?? 0));

$tracks = $cars = $racers = [];
if ($activeVersionId > 0) {
    $stmt = $conn->prepare('SELECT * FROM game_tracks WHERE version_id = ? ORDER BY name ASC');
    $stmt->bind_param('i', $activeVersionId);
    $stmt->execute();
    $tracks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT * FROM game_cars WHERE version_id = ? ORDER BY name ASC');
    $stmt->bind_param('i', $activeVersionId);
    $stmt->execute();
    $cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT * FROM game_racers WHERE version_id = ? ORDER BY name ASC');
    $stmt->bind_param('i', $activeVersionId);
    $stmt->execute();
    $racers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();

$pageTitle = 'Manage Game';
include __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> mb-4">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <h2>Manage Game</h2>
    <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<!-- Version Bar -->
<div class="card mb-4">
    <div class="card-header">
        <h3>Game Version</h3>
    </div>
    <div class="card-body p-3" style="background: var(--bg-card);">
        <div class="row g-3 align-items-end">

            <!-- Version selector + delete -->
            <div class="col-12 col-md-6">
                <label class="form-label">Active Version</label>
                <div class="d-flex gap-2">
                    <?php if (empty($versions)): ?>
                        <select class="form-select" disabled>
                            <option>No versions yet</option>
                        </select>
                    <?php else: ?>
                        <select class="form-select" id="versionSelect" onchange="switchVersion(this.value)">
                            <?php foreach ($versions as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= $v['id'] == $activeVersionId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <?php if ($activeVersionId > 0): ?>
                        <form method="POST" onsubmit="return confirm('Delete this version and ALL its tracks, cars and racers?')">
                            <input type="hidden" name="action" value="delete_version">
                            <input type="hidden" name="version_id" value="<?= $activeVersionId ?>">
                            <button type="submit" class="btn btn-danger">🗑 Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add new version -->
            <div class="col-12 col-md-6">
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="action" value="add_version">
                    <div class="flex-grow-1">
                        <label class="form-label">New Version</label>
                        <input type="text" name="version_name" class="form-control" placeholder="e.g. F1 24, F1 23..." required>
                    </div>
                    <div class="d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">+ Add</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php if ($activeVersionId > 0): ?>

<!-- Tracks / Cars / Racers Grid -->
<div class="row g-4">

    <?php
    $panels = [
        ['label' => 'Tracks', 'type' => 'tracks', 'data' => $tracks, 'table' => 'game_tracks',  'placeholder' => "One per line or comma separated\ne.g. Monza\nSilverstone\nSpa"],
        ['label' => 'Cars', 'type' => 'cars', 'data' => $cars, 'table' => 'game_cars',    'placeholder' => "One per line or comma separated\ne.g. Ferrari SF-24\nRed Bull RB20"],
        ['label' => 'Racers', 'type' => 'racers','data' => $racers, 'table' => 'game_racers',  'placeholder' => "One per line or comma separated\ne.g. Leclerc\nVerstappen\nHamilton"],
    ];
    ?>

    <?php foreach ($panels as $panel): ?>
    <div class="col-12 col-md-4">
        <div class="card h-100">

            <div class="card-header">
                <h3><?= $panel['label'] ?></h3>
                <span class="text-muted" style="font-size:0.75rem; font-family:'Barlow Condensed',sans-serif; letter-spacing:0.05em;">
                    <?= count($panel['data']) ?> item<?= count($panel['data']) !== 1 ? 's' : '' ?>
                </span>
            </div>

            <!-- Bulk add form -->
            <div class="p-3" style="border-bottom: 1px solid var(--border); background: var(--bg-card);">
                <form method="POST" class="d-flex flex-column gap-2">
                    <input type="hidden" name="action" value="bulk_add">
                    <input type="hidden" name="version_id" value="<?= $activeVersionId ?>">
                    <input type="hidden" name="item_type" value="<?= $panel['type'] ?>">
                    <textarea name="items" class="form-control" rows="3"
                        placeholder="<?= htmlspecialchars($panel['placeholder']) ?>"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm">+ Add</button>
                </form>
            </div>

            <!-- Item list -->
            <ul class="manage-card__list">
                <?php if (empty($panel['data'])): ?>
                    <li class="manage-card__empty">No <?= $panel['type'] ?> yet.</li>
                <?php else: ?>
                    <?php foreach ($panel['data'] as $item): ?>
                        <li>
                            <span><?= htmlspecialchars($item['name']) ?></span>
                            <form method="POST">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="item_table" value="<?= $panel['table'] ?>">
                                <button type="submit" class="btn-icon" title="Delete">✕</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

        </div>
    </div>
    <?php endforeach; ?>

</div>

<?php else: ?>
    <p class="empty-state">No versions yet. Add one above to get started.</p>
<?php endif; ?>

<script>
function switchVersion(id) {
    window.location.href = 'manage_games.php?version_id=' + id;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
