<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$conn    = getConnection();
ensureGameTables($conn);
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit  = $eventId > 0;
$errors  = [];
$values  = ['event_name' => '', 'event_date' => '', 'location' => '', 'notes' => ''];
$selectedGameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;

$games = getGameOptions($conn);
$gameOptions = [];
foreach ($games as $game) {
    $gameOptions[$game['game_id']] = $game['name'];
}

// Load existing event if editing
if ($isEdit) {
    $stmt = $conn->prepare('SELECT * FROM events WHERE event_id = ? LIMIT 1');
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();

    if (!$event) {
        header('Location: dashboard.php');
        exit();
    }

    $values = $event;
    $stmt->close();

    $settings = getEventGameDefaults($conn, $eventId);
    if ($settings) {
        $selectedGameId = $settings['game_id'];
        $values['default_car_id'] = $settings['car_id'];
        $values['default_track_id'] = $settings['track_id'];
        $values['default_driver_id'] = $settings['driver_id'];
    } else {
        $values['default_car_id'] = 0;
        $values['default_track_id'] = 0;
        $values['default_driver_id'] = 0;
    }
}

// default to first game if not selected yet
if ($selectedGameId === 0 && count($games)) {
    $selectedGameId = (int)$games[0]['game_id'];
}

$cars = $selectedGameId ? getGameItems($conn, $selectedGameId, 'game_cars', 'car_id') : [];
$tracks = $selectedGameId ? getGameItems($conn, $selectedGameId, 'game_tracks', 'track_id') : [];
$drivers = $selectedGameId ? getGameItems($conn, $selectedGameId, 'game_drivers', 'driver_id') : [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $values['event_name'] = trim($_POST['event_name'] ?? '');
    $values['event_date'] = trim($_POST['event_date'] ?? '');
    $values['location'] = trim($_POST['location']   ?? '');
    $values['notes']  = trim($_POST['notes']      ?? '');
    $selectedGameId = (int)($_POST['game_id'] ?? $selectedGameId);
    $values['default_car_id'] = (int)($_POST['default_car_id'] ?? 0);
    $values['default_track_id'] = (int)($_POST['default_track_id'] ?? 0);
    $values['default_driver_id'] = (int)($_POST['default_driver_id'] ?? 0);

    if ($values['event_name'] == '') $errors[] = 'Event name is required.';
    if ($values['event_date'] == '') $errors[] = 'Date is required.';
    if ($selectedGameId === 0) $errors[] = 'Please select a game version.';
    if ($values['default_car_id'] === 0) $errors[] = 'Please select a default car for this game.';
    if ($values['default_track_id'] === 0) $errors[] = 'Please select a default track for this game.';
    if ($values['default_driver_id'] === 0) $errors[] = 'Please select a default driver for this game.';

    if (count($errors) == 0) {
        if ($isEdit) {
            $stmt = $conn->prepare(
                'UPDATE events SET event_name=?, event_date=?, location=?, notes=? WHERE event_id=?'
            );
            $stmt->bind_param(
                'ssssi',
                $values['event_name'],
                $values['event_date'],
                $values['location'],
                $values['notes'],
                $eventId
            );
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO events (event_name, event_date, location, notes) VALUES (?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'ssss',
                $values['event_name'],
                $values['event_date'],
                $values['location'],
                $values['notes']
            );
        }

        $stmt->execute();
        $savedEventId = $isEdit ? $eventId : $stmt->insert_id;
        $stmt->close();

        $up = $conn->prepare(
            'INSERT INTO event_game_defaults (event_id, game_id, car_id, track_id, driver_id) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE game_id=VALUES(game_id), car_id=VALUES(car_id), track_id=VALUES(track_id), driver_id=VALUES(driver_id)'
        );
        $up->bind_param('iiiii', $savedEventId, $selectedGameId, $values['default_car_id'], $values['default_track_id'], $values['default_driver_id']);
        $up->execute();
        $up->close();

        $conn->close();

        setFlash('success', $isEdit ? 'Event updated.' : 'Event created.');
        header('Location: dashboard.php');
        exit();
    }
}

$conn->close();

$pageTitle = $isEdit ? 'Edit Event' : 'New Event';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <a href="dashboard.php" class="back-link">← Back</a>
    <h2><?php echo $pageTitle; ?></h2>
</div>

<?php if (count($errors) > 0) { ?>
    <div class="alert alert--error">
        <?php foreach ($errors as $err) { ?>
            <p><?php echo $err; ?></p>
        <?php } ?>
    </div>
<?php } ?>

<form method="POST" class="form">
    <div class="form-group">
        <label>Event Name</label>
        <input type="text" name="event_name"
               value="<?php echo htmlspecialchars($values['event_name']); ?>" required>
    </div>
    <div class="form-group">
        <label>Date</label>
        <input type="date" name="event_date"
               value="<?php echo htmlspecialchars($values['event_date']); ?>" required>
    </div>
    <div class="form-group">
        <label>Location</label>
        <input type="text" name="location"
               value="<?php echo htmlspecialchars($values['location']); ?>">
    </div>
    <div class="form-group">
        <label>Game Version</label>
        <select name="game_id" id="game_id" required onchange="window.location.href='event_form.php?id=<?php echo $eventId; ?>&game_id=' + this.value;">
            <option value="">Select game</option>
            <?php foreach ($games as $game): ?>
                <option value="<?php echo $game['game_id']; ?>"<?php echo selectedOption($game['game_id'], $selectedGameId); ?>><?php echo htmlspecialchars($game['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Default Car</label>
        <select name="default_car_id" required>
            <option value="">Select car</option>
            <?php foreach ($cars as $car): ?>
                <option value="<?php echo $car['car_id']; ?>"<?php echo selectedOption($car['car_id'], $values['default_car_id'] ?? 0); ?>><?php echo htmlspecialchars($car['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Default Track</label>
        <select name="default_track_id" required>
            <option value="">Select track</option>
            <?php foreach ($tracks as $track): ?>
                <option value="<?php echo $track['track_id']; ?>"<?php echo selectedOption($track['track_id'], $values['default_track_id'] ?? 0); ?>><?php echo htmlspecialchars($track['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Default Driver</label>
        <select name="default_driver_id" required>
            <option value="">Select driver</option>
            <?php foreach ($drivers as $driver): ?>
                <option value="<?php echo $driver['driver_id']; ?>"<?php echo selectedOption($driver['driver_id'], $values['default_driver_id'] ?? 0); ?>><?php echo htmlspecialchars($driver['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" rows="4"><?php echo htmlspecialchars($values['notes']); ?></textarea>
    </div>
    <button type="submit" class="btn btn--primary">
        <?php echo $isEdit ? 'Save Changes' : 'Create Event'; ?>
    </button>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
