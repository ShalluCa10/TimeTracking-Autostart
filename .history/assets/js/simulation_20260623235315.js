const LAP_DURATION_MS = 5000;

let running = false;
let sessionStart = null;
let lapStart = null;
let animFrame = null;
let currentLap = 0;
let lapTimes = [];

const carDot = document.getElementById('car-dot');
const timerDisplay = document.getElementById('timerDisplay');
const lapDisplay = document.getElementById('lapCounter');
const lapList = document.getElementById('lapList');
const btnStart = document.getElementById('startBtn');
const btnEnd = document.getElementById('endBtn');
const btnCompleteLap = document.getElementById('btn-complete-lap');

function formatTime(ms) {
    const totalSeconds = Math.floor(ms / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
}

function getTrackWidth() {
    const track = document.getElementById('trackLine');
    return track ? track.offsetWidth : 500;
}

function renderLaps() {
    if (!lapList) return;
    lapList.innerHTML = '';

    const bestMs = Math.min(...lapTimes.map(l => l.lap_time_ms));

    lapTimes.forEach(lap => {
        const div = document.createElement('div');
        const best = lap.lap_time_ms === bestMs ? ' ⭐' : '';
        div.textContent = 'Lap ' + lap.lap_number + ':  ' + lap.lap_time + best;
        if (lap.lap_time_ms === bestMs) div.style.color = '#ffd700';
        lapList.appendChild(div);
    });
}

function recordLap() {
    if (!running) return;

    const now = performance.now();
    const lapMs = Math.round(now - lapStart);
    const formatted = formatTime(lapMs);

    currentLap++;
    lapTimes.push({
        lap_number: currentLap,
        lap_time_ms: lapMs,
        lap_time: formatted
    });

    lapStart = performance.now();
    lapDisplay.textContent = 'LAP ' + currentLap;
    renderLaps();

    if (carDot) carDot.style.left = '0px';
}

function animate(timestamp) {
    if (!running) return;

    const sessionElapsed = timestamp - sessionStart;
    timerDisplay.textContent = formatTime(sessionElapsed);

    const lapElapsed = performance.now() - lapStart;
    const progress = Math.min(lapElapsed / LAP_DURATION_MS, 1);
    const trackWidth = getTrackWidth();

    if (carDot) carDot.style.left = (progress * trackWidth) + 'px';

    animFrame = requestAnimationFrame(animate);
}

/* ── Mimicry animation ─────────────────────────── */
function runMimicry(config) {
    return new Promise(resolve => {
        const overlay = document.getElementById('mimicry-overlay');

        // Reset all rows before showing
        ['racer', 'track', 'car'].forEach(key => {
            document.getElementById('mim-' + key).className = 'mimicry-row';
            document.getElementById('mim-' + key + '-arrows').innerHTML = '';
            const c = document.getElementById('mim-' + key + '-confirm');
            c.textContent = '';
            c.className = 'mimicry-confirm';
        });
        const ready = document.getElementById('mim-ready');
        ready.textContent = '';
        ready.className = 'mimicry-ready';

        overlay.style.display = 'flex';

        const steps = [
            { key: 'racer', count: config.racerIdx ?? 3, label: config.racer ?? '—' },
            { key: 'track', count: config.trackIdx ?? 5, label: config.track ?? '—' },
            { key: 'car', count: config.carIdx ?? 2, label: config.car ?? '—' },
        ];

        const ARROW_DELAY = 180;
        const CONFIRM_WAIT = 300;
        const STEP_GAP = 400;

        let totalDelay = 0;

        steps.forEach(step => {
            const row = document.getElementById('mim-' + step.key);
            const arrows = document.getElementById('mim-' + step.key + '-arrows');
            const confirm = document.getElementById('mim-' + step.key + '-confirm');

            setTimeout(() => row.classList.add('active'), totalDelay);

            for (let a = 0; a < step.count; a++) {
                setTimeout(() => {
                    const span = document.createElement('span');
                    span.className = 'mimicry-arrow';
                    span.textContent = '↓';
                    arrows.appendChild(span);
                    requestAnimationFrame(() => span.classList.add('show'));
                }, totalDelay + a * ARROW_DELAY);
            }

            totalDelay += step.count * ARROW_DELAY + CONFIRM_WAIT;

            setTimeout(() => {
                confirm.textContent = '✓ ' + step.label;
                confirm.classList.add('show');
                row.classList.remove('active');
                row.classList.add('done');
            }, totalDelay);

            totalDelay += STEP_GAP;
        });

        setTimeout(() => {
            ready.textContent = '▶ SESSION STARTING...';
            ready.classList.add('show');
        }, totalDelay);

        setTimeout(() => {
            overlay.style.display = 'none';
            resolve();
        }, totalDelay + 900);
    });
}

/* ── Start button ──────────────────────────────── */
btnStart.addEventListener('click', async function () {
    if (running) return;

    // Grab event data from the selector (may be null if session was pre-loaded via URL)
    const sel = document.getElementById('sel-event');
    const opt = sel ? sel.options[sel.selectedIndex] : null;

    await runMimicry({
        racer: opt?.dataset.racer ?? 'Racer',
        track: opt?.dataset.track ?? 'Track',
        car: opt?.dataset.car ?? 'Car',
        racerIdx: 3,  // placeholder — wire to menu_index later
        trackIdx: 5,
        carIdx: 2,
    });

    // ── actual session start (unchanged) ──
    running = true;
    currentLap = 0;
    lapTimes = [];
    sessionStart = performance.now();
    lapStart = sessionStart;

    lapDisplay.textContent = 'LAP 0';
    lapList.innerHTML = '';

    btnStart.disabled = true;
    btnCompleteLap.disabled = false;
    btnEnd.disabled = false;

    animFrame = requestAnimationFrame(animate);
});

btnCompleteLap.addEventListener('click', function () {
    if (!running) return;
    recordLap();
});

btnEnd.addEventListener('click', function () {
    if (!running && lapTimes.length === 0) {
        alert('Start a session first!');
        return;
    }

    running = false;
    cancelAnimationFrame(animFrame);

    btnStart.disabled = false;
    btnCompleteLap.disabled = true;
    btnEnd.disabled = true;

    if (lapTimes.length === 0) {
        alert('No laps recorded.');
        return;
    }

    const sessionId = new URLSearchParams(window.location.search).get('session_id');

    console.log('session_id from URL:', sessionId);
    console.log('laps to send:', lapTimes);

    fetch('../api/save_laps.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            session_id: sessionId,
            laps: lapTimes
        })
    })
        .then(res => res.json())
        .then(data => {
            console.log('Response:', data);
            if (data.success) {
                // compute best lap and save to sessions
                const best = lapTimes.reduce((a, b) => (a.lap_time_ms < b.lap_time_ms ? a : b));
                fetch('/api/create_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save_best_lap',
                        session_id: sessionId,
                        best_lap_time: best.lap_time
                    })
                }).finally(() => {
                    window.location.href = '../pages/results.php?session_id=' + sessionId;
                });
            } else {
                alert('Error saving: ' + data.message);
            }
        })
        .catch(err => {
            console.error('Save error:', err);
            alert('Network error while saving laps.');
        });
});
