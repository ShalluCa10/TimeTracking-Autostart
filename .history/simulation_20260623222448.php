<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$conn = getConnection();
$sessionId = (int) ($_GET['session_id'] ?? 0);
$eventId = (int) ($_GET['event_id'] ?? 0);

$events = $conn->query("SELECT event_id, event_name, car, track, racer FROM events WHERE status = 'live' ORDER BY event_date DESC")->fetch_all(MYSQLI_ASSOC);

$conn->close();

$pageTitle = 'Simulator';
include __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="/assets/css/simulation.css">

<div class="sim-wrapper py-4">

    <h1 class="sim-title">F1 LAP SIMULATOR</h1>
    <p class="sim-subtitle" id="simSubtitle">
        Session #<?= $sessionId ?> &nbsp;|&nbsp; Event #<?= $eventId ?>
    </p>

    <!-- ── EVENT SELECTOR ── -->
    <?php if ($sessionId === 0): ?>
        <div id="event-selector" style="margin-bottom: 40px;">

            <?php if (empty($events)): ?>
                <p class="sim-empty">
                    No live events available.
                    <a href="/pages/manage_events.php">Go live on an event first.</a>
                </p>
            <?php else: ?>

                <div class="sim-pre__group">
                    <label for="sel-event">SELECT EVENT</label>
                    <select id="sel-event">
                        <option value="">— Select Event —</option>
                        <?php foreach ($events as $ev): ?>
                            <option value="<?= $ev['event_id'] ?>" data-car="<?= htmlspecialchars($ev['car'] ?? '') ?>"
                                data-track="<?= htmlspecialchars($ev['track'] ?? '') ?>"
                                data-racer="<?= htmlspecialchars($ev['racer'] ?? '') ?>"
                                data-name="<?= htmlspecialchars($ev['event_name']) ?>">
                                <?= htmlspecialchars($ev['event_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Event preview -->
                <div id="event-preview" class="sim-pre__preview" style="display:none;">
                    <div class="sim-pre__detail"><span>Car</span> <strong id="prev-car">—</strong></div>
                    <div class="sim-pre__detail"><span>Track</span> <strong id="prev-track">—</strong></div>
                    <div class="sim-pre__detail"><span>Racer</span> <strong id="prev-racer">—</strong></div>
                </div>

                <p id="selector-status" style="font-size:0.8rem; min-height:1.2em; color:#8888aa; margin-bottom:0;"></p>

            <?php endif; ?>

        </div>
    <?php endif; ?>

    <!-- ── Track ── -->
    <div class="track-line-wrapper">
        <span class="flag start">START</span>
        <span class="flag finish">FINISH</span>
        <div class="track-line" id="trackLine">
            <div class="finish-marker"></div>
            <div id="car-dot"></div>
        </div>
    </div>

    <!-- ── Timer ── -->
    <div class="timer-display" id="timerDisplay">00:00</div>
    <div class="lap-counter" id="lapCounter">LAP 0</div>

    <!-- ── Buttons ── -->
    <div class="sim-controls">
        <button class="btn-sim btn-start" id="startBtn" <?= $sessionId === 0 ? 'disabled' : '' ?>>START</button>
        <button class="btn-sim btn-lap" id="btn-complete-lap" disabled>COMPLETE LAP</button>
        <button class="btn-sim btn-end" id="endBtn" disabled>END SESSION</button>
    </div>

    <!-- ── Lap list ── -->
    <div class="lap-list">
        <h3>LAP TIMES</h3>
        <div id="lapList"></div>
    </div>

    <!-- ── Mimic ── -->
    <div id="mimicry-overlay" style="display:none;">
        <div class="mimicry-box">
            <div class="mimicry-title">⚙ INITIALIZING SESSION</div>

            <div class="mimicry-row" id="mim-racer">
                <span class="mimicry-label">RACER</span>
                <span class="mimicry-arrows" id="mim-racer-arrows"></span>
                <span class="mimicry-confirm" id="mim-racer-confirm"></span>
            </div>

            <div class="mimicry-row" id="mim-track">
                <span class="mimicry-label">TRACK</span>
                <span class="mimicry-arrows" id="mim-track-arrows"></span>
                <span class="mimicry-confirm" id="mim-track-confirm"></span>
            </div>

            <div class="mimicry-row" id="mim-car">
                <span class="mimicry-label">CAR</span>
                <span class="mimicry-arrows" id="mim-car-arrows"></span>
                <span class="mimicry-confirm" id="mim-car-confirm"></span>
            </div>

            <div class="mimicry-ready" id="mim-ready"></div>
        </div>
    </div>

</div>


<script>
    (function () {
        const sel = document.getElementById('sel-event');
        const btn = document.getElementById('startBtn');
        const status = document.getElementById('selector-status');
        const preview = document.getElementById('event-preview');
        const prevCar = document.getElementById('prev-car');
        const prevTrack = document.getElementById('prev-track');
        const prevRacer = document.getElementById('prev-racer');
        const simSubtitle = document.getElementById('simSubtitle');

        if (!sel) return;

        sel.addEventListener('change', async function () {
            const opt = this.options[this.selectedIndex];

            if (!this.value) {
                preview.style.display = 'none';
                btn.disabled = true;
                status.textContent = '';
                return;
            }

            prevCar.textContent = opt.dataset.car || '—';
            prevTrack.textContent = opt.dataset.track || '—';
            prevRacer.textContent = opt.dataset.racer || '—';
            preview.style.display = 'block';

            btn.disabled = true;
            status.textContent = 'Creating session…';

            try {
                const res = await fetch('/api/create_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ event_id: parseInt(this.value) }),
                });
                const data = await res.json();

                if (data.session_id) {
                    history.replaceState(null, '', '?session_id=' + data.session_id);
                    status.textContent = '✅ Session #' + data.session_id + ' ready — press START';
                    if (simSubtitle) {
                        simSubtitle.innerHTML =
                            'Session #' + data.session_id +
                            ' &nbsp;|&nbsp; ' + (opt.dataset.name || opt.textContent.trim());
                    }
                    btn.disabled = false;
                } else {
                    status.textContent = '❌ ' + (data.error ?? 'Could not create session.');
                }
            } catch (e) {
                status.textContent = '❌ Network error.';
            }
        });
    })();
</script>

<script src="/assets/js/simulation.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
