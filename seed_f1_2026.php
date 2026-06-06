<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$conn = getConnection();
ensureGameTables($conn);

function getOrCreateId($conn, $table, $keyColumn, $keyValue, $extraColumns = []) {
    $idColumn = (substr($table, -1) === 's') ? substr($table, 0, -1) . '_id' : $table . '_id';
    $stmt = $conn->prepare("SELECT {$idColumn} FROM {$table} WHERE {$keyColumn} = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param('s', $keyValue);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($result)) {
        return (int)reset($result);
    }

    $columns = array_merge([$keyColumn], array_keys($extraColumns));
    $placeholders = array_fill(0, count($columns), '?');
    $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $columns), implode(', ', $placeholders));
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $types = str_repeat('s', count($columns));
    $values = array_merge([$keyValue], array_values($extraColumns));
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return (int)$id;
}

function getOrCreateCompositeId($conn, $table, $criteria, $values) {
    $where = [];
    foreach ($criteria as $column) {
        $where[] = "$column = ?";
    }
    $sql = sprintf('SELECT %s_id FROM %s WHERE %s LIMIT 1', $table, $table, implode(' AND ', $where));
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $types = str_repeat('s', count($values));
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($result)) {
        return (int)reset($result);
    }

    $columns = array_keys($criteria);
    $placeholders = array_fill(0, count($columns), '?');
    $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $columns), implode(', ', $placeholders));
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return (int)$id;
}

$gameId = getOrCreateId($conn, 'games', 'name', 'F1 2026');

$teams = [
    ['name' => 'McLaren', 'code' => 'MCL40'],
    ['name' => 'Mercedes', 'code' => 'W17'],
    ['name' => 'Ferrari', 'code' => 'SF-26'],
    ['name' => 'Red Bull Racing', 'code' => 'RB22'],
    ['name' => 'Williams', 'code' => 'FW48'],
    ['name' => 'Racing Bulls', 'code' => 'VCARB-02'],
    ['name' => 'Aston Martin', 'code' => 'AMR26'],
    ['name' => 'Haas', 'code' => 'VF-26'],
    ['name' => 'Alpine', 'code' => 'A526'],
    ['name' => 'Audi', 'code' => 'AUDI-26'],
    ['name' => 'Cadillac', 'code' => 'CAD-26'],
];

$teamIds = [];
foreach ($teams as $team) {
    $teamIds[$team['code']] = getOrCreateId($conn, 'teams', 'code', $team['code'], ['name' => $team['name']]);
}

$cars = [
    ['team_code' => 'MCL40', 'name' => 'McLaren MCL40', 'code' => 'MCL40'],
    ['team_code' => 'W17', 'name' => 'Mercedes W17', 'code' => 'W17'],
    ['team_code' => 'SF-26', 'name' => 'Ferrari SF-26', 'code' => 'SF-26'],
    ['team_code' => 'RB22', 'name' => 'Red Bull RB22', 'code' => 'RB22'],
    ['team_code' => 'FW48', 'name' => 'Williams FW48', 'code' => 'FW48'],
    ['team_code' => 'VCARB-02', 'name' => 'Racing Bulls VCARB-02', 'code' => 'VCARB-02'],
    ['team_code' => 'AMR26', 'name' => 'Aston Martin AMR26', 'code' => 'AMR26'],
    ['team_code' => 'VF-26', 'name' => 'Haas VF-26', 'code' => 'VF-26'],
    ['team_code' => 'A526', 'name' => 'Alpine A526', 'code' => 'A526'],
    ['team_code' => 'AUDI-26', 'name' => 'Audi AUDI-26', 'code' => 'AUDI-26'],
    ['team_code' => 'CAD-26', 'name' => 'Cadillac CAD-26', 'code' => 'CAD-26'],
];

foreach ($cars as $car) {
    $teamId = $teamIds[$car['team_code']];
    $stmt = $conn->prepare('SELECT car_id FROM cars WHERE team_id = ? AND game_id = ? AND code = ? LIMIT 1');
    $stmt->bind_param('iis', $teamId, $gameId, $car['code']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$result) {
        $stmt = $conn->prepare('INSERT INTO cars (team_id, game_id, name, code) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiss', $teamId, $gameId, $car['name'], $car['code']);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare('SELECT * FROM game_cars WHERE game_id = ? AND name = ? LIMIT 1');
    $stmt->bind_param('is', $gameId, $car['name']);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$exists) {
        $stmt = $conn->prepare('INSERT INTO game_cars (game_id, name) VALUES (?, ?)');
        $stmt->bind_param('is', $gameId, $car['name']);
        $stmt->execute();
        $stmt->close();
    }
}

$drivers = [
    ['name' => 'Lando Norris', 'team_code' => 'MCL40', 'country' => 'GBR'],
    ['name' => 'Oscar Piastri', 'team_code' => 'MCL40', 'country' => 'AUS'],
    ['name' => 'George Russell', 'team_code' => 'W17', 'country' => 'GBR'],
    ['name' => 'Lewis Hamilton', 'team_code' => 'W17', 'country' => 'GBR'],
    ['name' => 'Charles Leclerc', 'team_code' => 'SF-26', 'country' => 'MON'],
    ['name' => 'Carlos Sainz', 'team_code' => 'SF-26', 'country' => 'ESP'],
    ['name' => 'Max Verstappen', 'team_code' => 'RB22', 'country' => 'NED'],
    ['name' => 'Sergio Perez', 'team_code' => 'RB22', 'country' => 'MEX'],
    ['name' => 'Alexander Albon', 'team_code' => 'FW48', 'country' => 'THA'],
    ['name' => 'Liam Lawson', 'team_code' => 'FW48', 'country' => 'NZL'],
    ['name' => 'Fernando Alonso', 'team_code' => 'AMR26', 'country' => 'ESP'],
    ['name' => 'Lance Stroll', 'team_code' => 'AMR26', 'country' => 'CAN'],
    ['name' => 'Nico Hulkenberg', 'team_code' => 'VF-26', 'country' => 'GER'],
    ['name' => 'Oliver Bearman', 'team_code' => 'VF-26', 'country' => 'GBR'],
    ['name' => 'Esteban Ocon', 'team_code' => 'A526', 'country' => 'FRA'],
    ['name' => 'Pierre Gasly', 'team_code' => 'A526', 'country' => 'FRA'],
    ['name' => 'Arvid Lindblad', 'team_code' => 'AUDI-26', 'country' => 'SWE'],
    ['name' => 'Valtteri Bottas', 'team_code' => 'AUDI-26', 'country' => 'FIN'],
    ['name' => 'Kimi Antonelli', 'team_code' => 'CAD-26', 'country' => 'ITA'],
    ['name' => 'Gabriel Bortoleto', 'team_code' => 'CAD-26', 'country' => 'BRA'],
    ['name' => 'Franco Colapinto', 'team_code' => 'VCARB-02', 'country' => 'ARG'],
    ['name' => 'Isack Hadjar', 'team_code' => 'VCARB-02', 'country' => 'FRA'],
];

foreach ($drivers as $driver) {
    $teamId = $teamIds[$driver['team_code']];
    $stmt = $conn->prepare('SELECT driver_id FROM drivers WHERE name = ? AND team_id = ? LIMIT 1');
    $stmt->bind_param('si', $driver['name'], $teamId);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$exists) {
        $stmt = $conn->prepare('INSERT INTO drivers (team_id, name, country) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $teamId, $driver['name'], $driver['country']);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare('SELECT * FROM game_drivers WHERE game_id = ? AND name = ? LIMIT 1');
    $stmt->bind_param('is', $gameId, $driver['name']);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$exists) {
        $stmt = $conn->prepare('INSERT INTO game_drivers (game_id, name) VALUES (?, ?)');
        $stmt->bind_param('is', $gameId, $driver['name']);
        $stmt->execute();
        $stmt->close();
    }
}

$tracks = [
    ['name' => 'Albert Park', 'country' => 'AUS', 'track_code' => 'AUS', 'laps' => 58],
    ['name' => 'Shanghai', 'country' => 'CHN', 'track_code' => 'CHN', 'laps' => 56],
    ['name' => 'Suzuka', 'country' => 'JPN', 'track_code' => 'JPN', 'laps' => 53],
    ['name' => 'Miami', 'country' => 'USA', 'track_code' => 'MIA', 'laps' => 57],
    ['name' => 'Imola', 'country' => 'ITA', 'track_code' => 'IMO', 'laps' => 63],
    ['name' => 'Monaco', 'country' => 'MON', 'track_code' => 'MON', 'laps' => 78],
    ['name' => 'Barcelona', 'country' => 'ESP', 'track_code' => 'ESP', 'laps' => 66],
    ['name' => 'Montreal', 'country' => 'CAN', 'track_code' => 'CAN', 'laps' => 70],
    ['name' => 'Spielberg', 'country' => 'AUT', 'track_code' => 'AUT', 'laps' => 71],
    ['name' => 'Silverstone', 'country' => 'GBR', 'track_code' => 'GBR', 'laps' => 52],
    ['name' => 'Spa-Francorchamps', 'country' => 'BEL', 'track_code' => 'BEL', 'laps' => 44],
    ['name' => 'Hungaroring', 'country' => 'HUN', 'track_code' => 'HUN', 'laps' => 70],
    ['name' => 'Zandvoort', 'country' => 'NED', 'track_code' => 'NED', 'laps' => 72],
    ['name' => 'Monza', 'country' => 'ITA', 'track_code' => 'ITA', 'laps' => 53],
    ['name' => 'Baku', 'country' => 'AZE', 'track_code' => 'AZE', 'laps' => 51],
    ['name' => 'Singapore', 'country' => 'SIN', 'track_code' => 'SIN', 'laps' => 61],
    ['name' => 'Austin', 'country' => 'USA', 'track_code' => 'USA', 'laps' => 56],
    ['name' => 'Mexico City', 'country' => 'MEX', 'track_code' => 'MEX', 'laps' => 71],
    ['name' => 'São Paulo', 'country' => 'BRA', 'track_code' => 'BRA', 'laps' => 71],
    ['name' => 'Las Vegas', 'country' => 'USA', 'track_code' => 'VEG', 'laps' => 50],
    ['name' => 'Lusail', 'country' => 'QAT', 'track_code' => 'QAT', 'laps' => 57],
    ['name' => 'Yas Marina', 'country' => 'UAE', 'track_code' => 'UAE', 'laps' => 58],
    ['name' => 'Jeddah', 'country' => 'SAU', 'track_code' => 'SAU', 'laps' => 50],
    ['name' => 'Portimão', 'country' => 'PRT', 'track_code' => 'PRT', 'laps' => 66],
];

foreach ($tracks as $track) {
    $stmt = $conn->prepare('SELECT track_id FROM tracks WHERE track_code = ? LIMIT 1');
    $stmt->bind_param('s', $track['track_code']);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$exists) {
        $stmt = $conn->prepare('INSERT INTO tracks (name, country, track_code, laps) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('sssi', $track['name'], $track['country'], $track['track_code'], $track['laps']);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare('SELECT * FROM game_tracks WHERE game_id = ? AND name = ? LIMIT 1');
    $stmt->bind_param('is', $gameId, $track['name']);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$exists) {
        $stmt = $conn->prepare('INSERT INTO game_tracks (game_id, name) VALUES (?, ?)');
        $stmt->bind_param('is', $gameId, $track['name']);
        $stmt->execute();
        $stmt->close();
    }
}

$rigs = [
    ['name' => 'Rig 1', 'description' => 'Primary esports rig with triple screen and high-performance pedals.'],
    ['name' => 'Rig 2', 'description' => 'Secondary rig configured for practice and qualifying sessions.'],
    ['name' => 'Rig 3', 'description' => 'Portable rig for remote event use and test sessions.'],
];

foreach ($rigs as $rig) {
    $stmt = $conn->prepare('SELECT rig_id FROM rigs WHERE name = ? LIMIT 1');
    $stmt->bind_param('s', $rig['name']);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$exists) {
        $stmt = $conn->prepare('INSERT INTO rigs (name, description) VALUES (?, ?)');
        $stmt->bind_param('ss', $rig['name'], $rig['description']);
        $stmt->execute();
        $stmt->close();
    }
}

echo "Seed complete. F1 2026 game version, 11 teams, 22 drivers, 24 tracks, and 3 rigs were created or already exist.\n";
$conn->close();
