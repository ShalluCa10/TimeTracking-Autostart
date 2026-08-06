<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$conn = getConnection();
ensureGameTables($conn);

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

        $tableMap = ['tracks' => 'game_events', 'cars' => 'game_teams'];
        $allowed = ['game_events', 'game_teams'];
        $table = $tableMap[$type] ?? '';

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

        $allowed = ['game_events', 'game_teams'];
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

$tracks = $cars = [];
if ($activeVersionId > 0) {
    $stmt = $conn->prepare('SELECT * FROM game_events WHERE version_id = ? ORDER BY sort_order ASC, name ASC');
    $stmt->bind_param('i', $activeVersionId);
    $stmt->execute();
    $tracks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare('SELECT * FROM game_teams WHERE version_id = ? ORDER BY sort_order ASC, name ASC');
    $stmt->bind_param('i', $activeVersionId);
    $stmt->execute();
    $cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();

$pageTitle = 'Manage Game';
include __DIR__ . '/../../../includes/header.php';
?>

<?php if (isset($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash'];
    unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> mb-4">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h2 class="h4 fw-bold text-uppercase mb-0">Manage Game</h2>
    <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
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
                        <form method="POST" onsubmit="return confirm('Delete this version and ALL its events and teams?')">
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
            ['label' => 'Teams', 'type' => 'cars', 'data' => $cars, 'table' => 'game_teams', 'placeholder' => "One per line or comma separated\ne.g. Ferrari\nMercedes\nRed Bull"],
            ['label' => 'Events', 'type' => 'tracks', 'data' => $tracks, 'table' => 'game_events', 'placeholder' => "One per line or comma separated\ne.g. Monaco GP\nSilverstone\nSpa"],
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
                    <div class="p-3 border-bottom">
                        <form method="POST" class="d-flex flex-column gap-2">
                            <input type="hidden" name="action" value="bulk_add">
                            <input type="hidden" name="version_id" value="<?= $activeVersionId ?>">
                            <input type="hidden" name="item_type" value="<?= $panel['type'] ?>">
                            <textarea name="items" class="form-control" rows="3"
                                placeholder="<?= htmlspecialchars($panel['placeholder']) ?>"></textarea>
                            <button type="submit" class="btn btn-primary btn-sm">+ Add</button>
                        </form>
                    </div>

                    <!-- Item grid -->
                    <ul class="sortable-list item-grid" data-table="<?= $panel['table'] ?>" id="list-<?= $panel['type'] ?>">
                        <?php if (empty($panel['data'])): ?>
                            <li class="manage-card__empty">No <?= $panel['label'] ?> yet.</li>
                        <?php else: ?>
                            <?php foreach ($panel['data'] as $item): ?>
                                <?php
                                // Build 2-letter initials fallback (e.g. "Ferrari" -> "FE")
                                $clean = preg_replace('/[^A-Za-z0-9]/', '', $item['name']);
                                $initials = $clean !== '' ? strtoupper(mb_substr($clean, 0, 2)) : '?';
                                ?>
                                <li class="sortable-item" data-id="<?= $item['id'] ?>">

                                    <!-- Overlay toolbar: drag handle + delete -->
                                    <div class="item-card__toolbar">
                                        <span class="drag-handle" title="Drag to reorder">⠿</span>
                                        <form method="POST" class="item-delete-form">
                                            <input type="hidden" name="action" value="delete_item">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="item_table" value="<?= $panel['table'] ?>">
                                            <button type="submit" class="btn-icon" title="Delete">✕</button>
                                        </form>
                                    </div>

                                    <!-- Photo -->
                                    <div class="item-photo-wrap">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?= htmlspecialchars($item['image']) ?>" class="item-thumb"
                                                id="thumb-<?= $panel['table'] ?>-<?= $item['id'] ?>"
                                                alt="<?= htmlspecialchars($item['name']) ?>">
                                        <?php else: ?>
                                            <div class="item-thumb item-thumb--empty" id="thumb-<?= $panel['table'] ?>-<?= $item['id'] ?>">
                                                <?= $initials ?>
                                            </div>
                                        <?php endif; ?>
                                        <label class="item-photo-btn" title="Upload photo"
                                            for="upload-<?= $panel['table'] ?>-<?= $item['id'] ?>">✎</label>
                                        <input type="file" id="upload-<?= $panel['table'] ?>-<?= $item['id'] ?>"
                                            class="item-upload-input" accept="image/*" data-table="<?= $panel['table'] ?>"
                                            data-id="<?= $item['id'] ?>" style="display:none;">
                                    </div>

                                    <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>


                </div>
            </div>
        <?php endforeach; ?>

    </div>

<?php else: ?>
    <p class="text-muted py-4 mb-0">No versions yet. Add one above to get started.</p>
<?php endif; ?>

<style>
    /* ── Grid container ── */
    .item-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 16px;
        list-style: none;
        padding: 16px;
        margin: 0;
    }

    /* ── Card ── */
    .sortable-item {
        position: relative;
        display: flex;
        flex-direction: column;
        background: linear-gradient(180deg, #111827 0%, #0f172a 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        box-shadow: 0 12px 28px rgba(2, 8, 23, 0.28);
        min-height: 100%;
    }

    .sortable-item:hover {
        transform: translateY(-4px) scale(1.01);
        border-color: rgba(225, 6, 0, 0.55);
        box-shadow: 0 18px 36px rgba(2, 8, 23, 0.42);
    }

    .sortable-item.dragging {
        opacity: 0.45;
        transform: scale(0.98);
    }

    .sortable-item.drag-over {
        border-color: #e10600;
        box-shadow: 0 0 0 2px rgba(225, 6, 0, 0.35);
    }

    /* Accent per panel type */
    #list-tracks .sortable-item {
        border-left: 3px solid #0057ff;
    }

    #list-cars .sortable-item {
        border-left: 3px solid #e10600;
    }

    /* ── Toolbar overlay (drag handle + delete) ── */
    .item-card__toolbar {
        position: absolute;
        top: 8px;
        left: 8px;
        right: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 3;
        opacity: 0;
        transition: opacity 0.15s ease;
        pointer-events: none;
    }

    .sortable-item:hover .item-card__toolbar {
        opacity: 1;
    }

    .drag-handle {
        cursor: grab;
        color: #eee;
        font-size: 0.85rem;
        background: rgba(0, 0, 0, 0.7);
        border-radius: 999px;
        padding: 4px 8px;
        pointer-events: auto;
        user-select: none;
    }

    .drag-handle:active {
        cursor: grabbing;
    }

    .item-delete-form {
        pointer-events: auto;
    }

    .btn-icon {
        background: rgba(0, 0, 0, 0.7);
        border: none;
        color: #f87171;
        width: 26px;
        height: 26px;
        border-radius: 999px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
    }

    .btn-icon:hover {
        background: #e10600;
        color: #fff;
    }

    /* ── Photo cell ── */
    .item-photo-wrap {
        position: relative;
        width: 100%;
        aspect-ratio: 1 / 1;
        overflow: hidden;
        background: #020617;
    }

    /* Cars are square, Schedules are widescreen (track layouts) */
    #list-tracks .item-photo-wrap {
        aspect-ratio: 16 / 9;
    }

    .item-thumb {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.25s ease, filter 0.25s ease;
    }

    .item-thumb--empty {
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #111827 0%, #0f172a 100%);
        font-family: 'Orbitron', sans-serif;
        font-weight: 800;
        font-size: 1.25rem;
        letter-spacing: 0.05em;
        color: #e10600;
    }

    #list-tracks .item-thumb--empty {
        color: #0057ff;
    }

    /* Hover overlay — pencil icon */
    .item-photo-btn {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(225, 6, 0, 0.72);
        font-size: 0.95rem;
        color: #fff;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.15s ease;
    }

    .item-photo-wrap:hover .item-photo-btn {
        opacity: 1;
    }

    .item-photo-wrap:hover .item-thumb {
        transform: scale(1.06);
        filter: brightness(0.72) saturate(1.08);
    }

    .item-thumb.uploading {
        opacity: 0.4;
        animation: pulse 0.8s infinite alternate;
    }

    @keyframes pulse {
        to {
            opacity: 0.9;
        }
    }

    /* ── Name label under photo ── */
    .item-name {
        display: block;
        padding: 10px 10px 12px;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #f8fafc;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(2, 8, 23, 0.45);
    }

    /* ── Empty state ── */
    .manage-card__empty {
        grid-column: 1 / -1;
        text-align: center;
        color: #64748b;
        font-size: 0.8rem;
        padding: 24px;
    }

    .reorder-saving {
        opacity: 0.5;
        pointer-events: none;
    }

    @media (max-width: 576px) {
        .item-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            padding: 12px;
        }
    }
</style>



<script>
    /* ── drag/drop reorder ── */
    (function () {
        const API = window.location.origin + '/api/reorder_item.php';

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
        const UPLOAD_API = window.location.origin + '/api/upload_image.php';

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

<?php include __DIR__ . '/../../../includes/footer.php'; ?>