<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();
$conn = getConnection();
ensureGameTables($conn);

$errors = [];
$success = '';
$selectedGameId = isset($_GET['selected_game_id']) ? (int)$_GET['selected_game_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_game') {
        $name = trim($_POST['game_name'] ?? '');
        if ($name === '') {
            $errors[] = 'Game version name is required.';
        } else {
            $stmt = $conn->prepare('INSERT INTO games (name) VALUES (?)');
            $stmt->bind_param('s', $name);
            if ($stmt->execute()) {
                $success = 'Game version added.';
                $selectedGameId = $stmt->insert_id;
            } else {
                $errors[] = 'Failed to add game version. It may already exist.';
            }
            $stmt->close();
        }
    }

    if ($action === 'add_car') {
        $gameId = (int)($_POST['game_id'] ?? 0);
        $name = trim($_POST['car_name'] ?? '');
        if ($gameId === 0 || $name === '') {
            $errors[] = 'Select a game and enter a car name.';
        } else {
            $stmt = $conn->prepare('INSERT INTO game_cars (game_id, name) VALUES (?, ?)');
            $stmt->bind_param('is', $gameId, $name);
            if ($stmt->execute()) {
                $success = 'Car added to game.';
                $selectedGameId = $gameId;
            } else {
                $errors[] = 'Failed to add car.';
            }
            $stmt->close();
        }
    }

    if ($action === 'add_track') {
        $gameId = (int)($_POST['game_id'] ?? 0);
        $name = trim($_POST['track_name'] ?? '');
        if ($gameId === 0 || $name === '') {
            $errors[] = 'Select a game and enter a track name.';
        } else {
            $stmt = $conn->prepare('INSERT INTO game_tracks (game_id, name) VALUES (?, ?)');
            $stmt->bind_param('is', $gameId, $name);
            if ($stmt->execute()) {
                $success = 'Track added to game.';
                $selectedGameId = $gameId;
            } else {
                $errors[] = 'Failed to add track.';
            }
            $stmt->close();
        }
    }

    if ($action === 'add_driver') {
        $gameId = (int)($_POST['game_id'] ?? 0);
        $name = trim($_POST['driver_name'] ?? '');
        if ($gameId === 0 || $name === '') {
            $errors[] = 'Select a game and enter a driver name.';
        } else {
            $stmt = $conn->prepare('INSERT INTO game_drivers (game_id, name) VALUES (?, ?)');
            $stmt->bind_param('is', $gameId, $name);
            if ($stmt->execute()) {
                $success = 'Driver added to game.';
                $selectedGameId = $gameId;
            } else {
                $errors[] = 'Failed to add driver.';
            }
            $stmt->close();
        }
    }

    if ($selectedGameId === 0 && isset($_POST['game_id'])) {
        $selectedGameId = (int)$_POST['game_id'];
    }

    if ($selectedGameId !== 0) {
        header('Location: manage_games.php?selected_game_id=' . $selectedGameId);
        exit();
    }
}

$games = getGameOptions($conn);
$gameOptions = [];
foreach ($games as $game) {
    $gameOptions[$game['game_id']] = $game['name'];
}

$selectedGameId = $selectedGameId ?: (count($games) ? (int)$games[0]['game_id'] : 0);

$cars = $selectedGameId ? getGameItems($conn, $selectedGameId, 'game_cars', 'car_id') : [];
$tracks = $selectedGameId ? getGameItems($conn, $selectedGameId, 'game_tracks', 'track_id') : [];
$drivers = $selectedGameId ? getGameItems($conn, $selectedGameId, 'game_drivers', 'driver_id') : [];

$pageTitle = 'Manage Game';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <a href="dashboard.php" class="back-link">← Back</a>
    <h2>Manage Game</h2>
</div>

<?php if ($success): ?>
    <div class="alert alert--success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (count($errors)): ?>
    <div class="alert alert--error">
        <?php foreach ($errors as $error): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="grid-row">
    <div class="card">
        <h3>Add Game Version</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_game">
            <div class="form-group">
                <label for="game_name">Game Version</label>
                <input id="game_name" name="game_name" type="text" placeholder="e.g. F1 2024" required>
            </div>
            <button class="btn btn--primary" type="submit">Add Game</button>
        </form>
    </div>

    <div class="card">
        <h3>Add Options for Game</h3>
        <form method="GET" style="margin-bottom: 1rem;">
            <div class="form-group">
                <label for="selected_game_id">Select Game</label>
                <select id="selected_game_id" name="selected_game_id" onchange="this.form.submit()">
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game['game_id']; ?>"<?php echo ($game['game_id'] === $selectedGameId) ? ' selected' : ''; ?>><?php echo htmlspecialchars($game['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="add_car">
            <input type="hidden" name="game_id" value="<?php echo htmlspecialchars($selectedGameId); ?>">
            <div class="form-group">
                <label for="car_name">Car</label>
                <input id="car_name" name="car_name" type="text" placeholder="e.g. Red Bull RB20" required>
            </div>
            <button class="btn btn--primary" type="submit">Add Car</button>
        </form>

        <form method="POST" style="margin-top: 1rem;">
            <input type="hidden" name="action" value="add_track">
            <input type="hidden" name="game_id" value="<?php echo htmlspecialchars($selectedGameId); ?>">
            <div class="form-group">
                <label for="track_name">Track</label>
                <input id="track_name" name="track_name" type="text" placeholder="e.g. Silverstone" required>
            </div>
            <button class="btn btn--primary" type="submit">Add Track</button>
        </form>

        <form method="POST" style="margin-top: 1rem;">
            <input type="hidden" name="action" value="add_driver">
            <input type="hidden" name="game_id" value="<?php echo htmlspecialchars($selectedGameId); ?>">
            <div class="form-group">
                <label for="driver_name">Driver</label>
                <input id="driver_name" name="driver_name" type="text" placeholder="e.g. Max Verstappen" required>
            </div>
            <button class="btn btn--primary" type="submit">Add Driver</button>
        </form>
    </div>
</div>

<div class="page-header" style="margin-top: 2rem;">
    <h3>Options for <?php echo htmlspecialchars($gameOptions[$selectedGameId] ?? 'Select a game'); ?></h3>
</div>

<div class="grid-row">
    <div class="card">
        <h4>Cars</h4>
        <?php if (count($cars)): ?>
            <ul>
                <?php foreach ($cars as $car): ?>
                    <li><?php echo htmlspecialchars($car['name']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No cars added yet.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h4>Tracks</h4>
        <?php if (count($tracks)): ?>
            <ul>
                <?php foreach ($tracks as $track): ?>
                    <li><?php echo htmlspecialchars($track['name']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No tracks added yet.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h4>Drivers</h4>
        <?php if (count($drivers)): ?>
            <ul>
                <?php foreach ($drivers as $driver): ?>
                    <li><?php echo htmlspecialchars($driver['name']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No drivers added yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>