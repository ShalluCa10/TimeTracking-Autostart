<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
$conn = getConnection();
ensureGameTables($conn);

action:
$action = $_GET['action'] ?? 'games';

if ($action === 'games') {
    $games = getGameOptions($conn);
    echo json_encode(['success' => true, 'games' => $games]);
    exit();
}

if ($action === 'game' && !empty($_GET['game_id'])) {
    $gameId = (int) $_GET['game_id'];
    $game = getGameItemById($conn, 'games', 'game_id', $gameId);
    if (!$game) {
        echo json_encode(['success' => false, 'message' => 'Game not found']);
        exit();
    }
    $cars = getGameItems($conn, $gameId, 'game_cars', 'car_id');
    $tracks = getGameItems($conn, $gameId, 'game_tracks', 'track_id');
    $drivers = getGameItems($conn, $gameId, 'game_drivers', 'driver_id');
    echo json_encode(['success' => true, 'game' => $game, 'cars' => $cars, 'tracks' => $tracks, 'drivers' => $drivers]);
    exit();
}

if ($action === 'event_defaults' && !empty($_GET['event_id'])) {
    $eventId = (int) $_GET['event_id'];
    $defaults = getEventGameDefaults($conn, $eventId);
    if (!$defaults) {
        echo json_encode(['success' => false, 'message' => 'No defaults for this event']);
        exit();
    }
    $game = getGameItemById($conn, 'games', 'game_id', $defaults['game_id']);
    $car = getGameItemById($conn, 'game_cars', 'car_id', $defaults['car_id']);
    $track = getGameItemById($conn, 'game_tracks', 'track_id', $defaults['track_id']);
    $driver = getGameItemById($conn, 'game_drivers', 'driver_id', $defaults['driver_id']);
    echo json_encode(['success' => true, 'defaults' => $defaults, 'game' => $game, 'car' => $car, 'track' => $track, 'driver' => $driver]);
    exit();
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit();
