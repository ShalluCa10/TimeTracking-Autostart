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
        $type = $_POST['item_type'] ?? '';
        $raw = trim($_POST['items'] ?? '');

        $allowed = ['game_tracks', 'game_cars', 'game_racers'];
        $table = 'game_' . $type;

        if ($versionId > 0 && in_array($table, $allowed) && $raw !== '') {
            $items = preg_split('/[\n,]+/', $raw);

            $maxStmt = $conn->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM `$table` WHERE version_id = ?");
            $maxStmt->bind_param('i', $versionId);
            $maxStmt->execute();
            $maxStmt->bind_result($maxOrder);
            $maxStmt->fetch();
            $maxStmt->close();

            $stmt = $conn->prepare("INSERT IGNORE INTO `$table` (version_id, name, sort_order) VALUES (?, ?, ?)");
            $count = 0;
            foreach ($items as $item) {
                $item = trim($item);
                if ($item !== '') {
                    $maxOrder++;
                    $stmt->bind_param('isi', $versionId, $item, $maxOrder);
                    $stmt->execute();
                    $count++;
                }
            }
            $stmt->close();
            $_SESSION['flash'] = ['type' => 'success', 'message' => "$count item(s) added."];
        }

    } elseif ($action === 'delete_item') {
        $id = (int) ($_POST['item_id'] ?? 0);
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
    $stmt = $conn->prepare('SELECT * FROM game_tracks WHERE version_id = ? ORDER BY sort_order ASC, name ASC');
    $stmt->bind_param('i', $activeVersionId);
    $stmt->execute();
    $tracks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT * FROM game_cars WHERE version_id = ? ORDER BY sort_order ASC, name ASC');
    $stmt->bind_param('i', $activeVersionId);
    $stmt->execute();
    $cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT * FROM game_racers WHERE version_id = ? ORDER BY sort_order ASC, name ASC');
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
    <?php $flash = $_SESSION['flash'];
    unset($_SESSION['flash']); ?>
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
                        <form method="POST"
                            onsubmit="return confirm('Delete this version and ALL its tracks, cars and racers?')">
                            <input type="hidden" name="action" value="delete_version">
                            <input type="hidden" name="version_id" value="<?= $activeVersionId ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="action" value="add_version">
                    <div class="flex-grow-1">
                        <label class="form-label">New Version</label>
                        <input type="text" name="version_name" class="form-control" placeholder="e.g. F1 24, F1 23..."
                            required>
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

    <div class="row g-4">

        <?php
        $panels = [
            ['label' => 'Tracks', 'type' => 'tracks', 'data' => $tracks, 'table' => 'game_tracks', 'placeholder' => "One per line or comma separated\ne.g. Monza\nSilverstone\nSpa"],
            ['label' => 'Cars', 'type' => 'cars', 'data' => $cars, 'table' => 'game_cars', 'placeholder' => "One per line or comma separated\ne.g. Ferrari SF-24\nRed Bull RB20"],
            ['label' => 'Racers', 'type' => 'racers', 'data' => $racers, 'table' => 'game_racers', 'placeholder' => "One per line or comma separated\ne.g. Leclerc\nVerstappen\nHamilton"],
        ];
        ?>

        <?php foreach ($panels as $panel): ?>
            <div class="col-12 col-md-4">
                <div class="card h-100">

                    <div class="card-header">
                        <h3><?= $panel['label'] ?></h3>
                        <span class="text-muted"
                            style="font-size:0.75rem; font-family:'Barlow Condensed',sans-serif; letter-spacing:0.05em;">
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
                    <ul class="manage-card__list sortable-list" data-table="<?= $panel['table'] ?>"
                        id="list-<?= $panel['type'] ?>">
                        <?php if (empty($panel['data'])): ?>
                            <li class="manage-card__empty">No <?= $panel['type'] ?> yet.</li>
                        <?php else: ?>
                            <?php foreach ($panel['data'] as $item): ?>
                                <li class="sortable-item" data-id="<?= $item['id'] ?>">
                                    <span class="drag-handle" title="Drag to reorder">⠿</span>

                                    <!-- ── Photo thumbnail ── -->
                                    <div class="item-photo-wrap">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?= htmlspecialchars($item['image']) ?>" class="item-thumb"
                                                id="thumb-<?= $panel['table'] ?>-<?= $item['id'] ?>"
                                                alt="<?= htmlspecialchars($item['name']) ?>">
                                        <?php else: ?>
                                            <div class="item-thumb item-thumb--empty" id="thumb-<?= $panel['table'] ?>-<?= $item['id'] ?>">
                                                📷
                                            </div>
                                        <?php endif; ?>
                                        <label class="item-photo-btn" title="Upload photo"
                                            for="upload-<?= $panel['table'] ?>-<?= $item['id'] ?>">✎</label>
                                        <input type="file" id="upload-<?= $panel['table'] ?>-<?= $item['id'] ?>"
                                            class="item-upload-input" accept="image/*" data-table="<?= $panel['table'] ?>"
                                            data-id="<?= $item['id'] ?>" style="display:none;">
                                    </div>

                                    <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>

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

<style>
    /* ── Sortable rows ── */
    .sortable-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 12px;
        border-bottom: 1px solid var(--border);
        transition: background 0.15s;
    }

    .sortable-item:last-child {
        border-bottom: none;
    }

    .sortable-item.dragging {
        opacity: 0.4;
    }

    .sortable-item.drag-over {
        background: rgba(255, 255, 255, 0.06);
        border-top: 2px solid #e10600;
    }

    .drag-handle {
        cursor: grab;
        color: #444;
        font-size: 1rem;
        user-select: none;
        flex-shrink: 0;
    }

    .drag-handle:active {
        cursor: grabbing;
    }

    .item-name {
        flex: 1;
        font-size: 0.88rem;
        font-weight: 600;
        letter-spacing: 0.03em;
        color: #eee;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sortable-item form {
        flex-shrink: 0;
    }

    .reorder-saving {
        opacity: 0.5;
        pointer-events: none;
    }

    /* ── Photo cell ── */
    .item-photo-wrap {
        position: relative;
        flex-shrink: 0;
        width: 48px;
        height: 48px;
        border-radius: 8px;
        overflow: hidden;
    }

    .item-thumb {
        width: 48px;
        height: 48px;
        object-fit: cover;
        display: block;
        border-radius: 8px;
        border: 1px solid #2a2a2a;
        transition: filter 0.2s;
    }

    .item-thumb--empty {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #141414;
        border: 1px dashed #333;
        border-radius: 8px;
        font-size: 1.1rem;
        color: #444;
    }

    /* Hover overlay — pencil icon */
    .item-photo-btn {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(225, 6, 0, 0.72);
        border-radius: 8px;
        font-size: 0.85rem;
        color: #fff;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.15s;
    }

    .item-photo-wrap:hover .item-photo-btn {
        opacity: 1;
    }

    .item-photo-wrap:hover .item-thumb {
        filter: brightness(0.5);
    }

    /* Upload pulse */
    .item-thumb.uploading {
        opacity: 0.4;
        animation: pulse 0.8s infinite alternate;
    }

    @keyframes pulse {
        to {
            opacity: 0.9;
        }
    }

    /* ── Panel accent lines ── */
    #list-tracks .sortable-item {
        border-left: 3px solid #0057ff;
    }

    #list-cars .sortable-item {
        border-left: 3px solid #e10600;
    }

    #list-racers .sortable-item {
        border-left: 3px solid #00ff88;
    }
</style>


<script>
    /* ── drag/drop reorder ── */
    (function () {
        const API = window.location.origin
            + window.location.pathname.replace(/\/pages\/[^\/]+$/, '')
            + '/api/reorder_item.php';

        async function saveOrder(list) {
            const table = list.dataset.table;
            const ids = [...list.querySelectorAll('.sortable-item')].map(el => el.dataset.id);
            list.classList.add('reorder-saving');
            const body = new URLSearchParams();
            body.append('table', table);
            ids.forEach((id, i) => body.append(`ids[${i}]`, id));
            try { await fetch(API, { method: 'POST', body }); }
            catch (e) { console.error('Reorder failed:', e); }
            list.classList.remove('reorder-saving');
        }

        let dragged = null;

        document.querySelectorAll('.sortable-list').forEach(list => {
            list.addEventListener('dragstart', e => {
                const item = e.target.closest('.sortable-item');
                if (!item) return;
                dragged = item;
                item.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            list.addEventListener('dragend', e => {
                const item = e.target.closest('.sortable-item');
                if (item) item.classList.remove('dragging');
                list.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
                dragged = null;
            });
            list.addEventListener('dragover', e => {
                e.preventDefault();
                const target = e.target.closest('.sortable-item');
                if (!target || target === dragged) return;
                list.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
                target.classList.add('drag-over');
            });
            list.addEventListener('drop', e => {
                e.preventDefault();
                const target = e.target.closest('.sortable-item');
                if (!target || !dragged || target === dragged) return;
                target.classList.remove('drag-over');
                const items = [...list.querySelectorAll('.sortable-item')];
                const fromIdx = items.indexOf(dragged);
                const toIdx = items.indexOf(target);
                if (fromIdx < toIdx) list.insertBefore(dragged, target.nextElementSibling);
                else list.insertBefore(dragged, target);
                saveOrder(list);
            });
        });

        document.querySelectorAll('.sortable-item').forEach(item => item.setAttribute('draggable', 'true'));
    })();

    /* ── photo upload ── */
    (function () {
        const UPLOAD_API = window.location.origin
            + window.location.pathname.replace(/\/pages\/[^\/]+$/, '')
            + '/api/upload_image.php';

        document.querySelectorAll('.item-upload-input').forEach(input => {
            input.addEventListener('change', async function () {
                if (!this.files[0]) return;

                const table = this.dataset.table;
                const id = this.dataset.id;
                const thumbId = 'thumb-' + table + '-' + id;
                const thumb = document.getElementById(thumbId);

                if (thumb) thumb.classList.add('uploading');

                const fd = new FormData();
                fd.append('table', table);
                fd.append('id', id);
                fd.append('image', this.files[0]);

                try {
                    const res = await fetch(UPLOAD_API, { method: 'POST', body: fd });
                    const data = await res.json();

                    if (data.success) {
                        // Replace placeholder or update existing img
                        const wrap = thumb.parentElement;
                        wrap.innerHTML = `
                            <img src="${data.path}?t=${Date.now()}"
                                 class="item-thumb"
                                 id="${thumbId}"
                                 alt="">
                            <label class="item-photo-btn" title="Upload photo"
                                   for="upload-${table}-${id}">✎</label>
                            <input type="file"
                                   id="upload-${table}-${id}"
                                   class="item-upload-input"
                                   accept="image/*"
                                   data-table="${table}"
                                   data-id="${id}"
                                   style="display:none;">
                        `;
                        // Re-bind the new input
                        wrap.querySelector('.item-upload-input').addEventListener('change', arguments.callee);
                    } else {
                        alert('Upload failed: ' + (data.error ?? 'Unknown error'));
                        if (thumb) thumb.classList.remove('uploading');
                    }
                } catch (e) {
                    alert('Network error during upload.');
                    if (thumb) thumb.classList.remove('uploading');
                }
            });
        });
    })();

    function switchVersion(id) {
        window.location.href = 'manage_games.php?version_id=' + id;
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>