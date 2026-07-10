<?php

// ── Flash ─────────────────────────────────────────────────────────────────────

function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash_type']    = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['flash_message'])) return null;

    $flash = [
        'type'    => $_SESSION['flash_type'],
        'message' => $_SESSION['flash_message'],
    ];

    unset($_SESSION['flash_type'], $_SESSION['flash_message']);
    return $flash;
}

// ── Event Status ──────────────────────────────────────────────────────────────

function resolveEventStatus(string $dbStatus, string $eventDate): array {
    if ($dbStatus === 'live')      return ['live',       'Live',      'badge--live'];
    if ($dbStatus === 'completed') return ['completed',  'Completed', 'badge--completed'];

    $today = date('Y-m-d');
    if ($eventDate > $today)       return ['upcoming',  'Upcoming',  'badge--upcoming'];
    if ($eventDate === $today)     return ['live',      'Live',      'badge--live'];
    return                                ['completed', 'Completed', 'badge--completed'];
}

// ── DB Table Bootstrap ────────────────────────────────────────────────────────

function ensureGameTables($conn) {
    $tableExists = function(string $tableName) use ($conn): bool {
        $result = $conn->query("SHOW TABLES LIKE '$tableName'");
        return $result && $result->num_rows > 0;
    };

    $conn->query("CREATE TABLE IF NOT EXISTS games (
        game_id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (game_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS game_versions (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if ($tableExists('game_cars') && !$tableExists('game_teams')) {
        $conn->query('RENAME TABLE game_cars TO game_teams');
    }

    if ($tableExists('game_tracks') && !$tableExists('game_events')) {
        $conn->query('RENAME TABLE game_tracks TO game_events');
    }

    if ($tableExists('game_racers')) {
        $conn->query('DROP TABLE IF EXISTS game_racers');
    }

    $conn->query("CREATE TABLE IF NOT EXISTS game_teams (
        id INT NOT NULL AUTO_INCREMENT,
        version_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_game_teams_version (version_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS game_events (
        id INT NOT NULL AUTO_INCREMENT,
        version_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_game_events_version (version_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    if ($tableExists('game_cars') && !$conn->query('SELECT 1 FROM game_teams LIMIT 1')->num_rows) {
        $conn->query('INSERT INTO game_teams (id, version_id, name, image, sort_order) SELECT id, version_id, name, image, sort_order FROM game_cars');
    }

    if ($tableExists('game_tracks') && !$conn->query('SELECT 1 FROM game_events LIMIT 1')->num_rows) {
        $conn->query('INSERT INTO game_events (id, version_id, name, image, sort_order) SELECT id, version_id, name, image, sort_order FROM game_tracks');
    }

    $conn->query("CREATE TABLE IF NOT EXISTS teams (
        team_id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(150) NOT NULL UNIQUE,
        code VARCHAR(20) NOT NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (team_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS cars (
        car_id INT NOT NULL AUTO_INCREMENT,
        team_id INT NOT NULL,
        game_id INT NOT NULL,
        name VARCHAR(150) NOT NULL,
        code VARCHAR(50) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (car_id),
        FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
        FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS tracks (
        track_id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(150) NOT NULL,
        country VARCHAR(80) NOT NULL,
        track_code VARCHAR(20) NOT NULL,
        laps INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (track_id),
        UNIQUE KEY uq_track_code (track_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS rigs (
        rig_id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(150) NOT NULL UNIQUE,
        description TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (rig_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $conn->query("CREATE TABLE IF NOT EXISTS results (
        result_id INT NOT NULL AUTO_INCREMENT,
        session_id INT NOT NULL,
        position INT NOT NULL DEFAULT 0,
        best_lap_time VARCHAR(20) NOT NULL DEFAULT '',
        total_time VARCHAR(50) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (result_id),
        FOREIGN KEY (session_id) REFERENCES sessions(session_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

// ── Game Option Helpers ───────────────────────────────────────────────────────

function getGameOptions($conn) {
    $options = [];
    $result  = $conn->query('SELECT * FROM games ORDER BY name ASC');
    if ($result) {
        while ($row = $result->fetch_assoc()) $options[] = $row;
    }
    return $options;
}

function getGameItems($conn, $gameId, $tableName, $idName) {
    $items = [];
    $stmt  = $conn->prepare("SELECT {$idName}, name FROM {$tableName} WHERE game_id = ? ORDER BY name ASC");
    if ($stmt) {
        $stmt->bind_param('i', $gameId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $items[] = $row;
        $stmt->close();
    }
    return $items;
}

function getEventGameDefaults($conn, $eventId) {
    $stmt = $conn->prepare('SELECT * FROM event_game_defaults WHERE event_id = ? LIMIT 1');
    if (!$stmt) return null;
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result;
}

function getGameItemById($conn, $tableName, $idName, $id) {
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE {$idName} = ? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function selectedOption($option, $current): string {
    return (string)$option === (string)$current ? ' selected' : '';
}

// ── Static Option Lists ───────────────────────────────────────────────────────

function getCarOptions(): array {
    return [
        'Red Bull RB20', 'Ferrari SF-23', 'Mercedes W14', 'McLaren MCL60',
        'Aston Martin AMR24', 'Alpine A524', 'Williams FW45',
        'AlphaTauri AT04', 'Haas VF-24', 'Alfa Romeo C44',
    ];
}

function getTrackOptions(): array {
    return [
        'Silverstone', 'Monza', 'Spa-Francorchamps', 'Monaco', 'Suzuka',
        'Interlagos', 'Circuit of the Americas', 'Singapore', 'Bahrain', 'Las Vegas',
    ];
}

function getDriverOptions(): array {
    return [
        'Max Verstappen', 'Lewis Hamilton', 'Charles Leclerc', 'Lando Norris',
        'George Russell', 'Carlos Sainz', 'Fernando Alonso',
        'Sergio Pérez', 'Oscar Piastri', 'Pierre Gasly',
    ];
}

function normalizeOptionList(array $options, string $current): array {
    if ($current !== '' && !in_array($current, $options, true)) {
        array_unshift($options, $current);
    }
    return $options;
}
