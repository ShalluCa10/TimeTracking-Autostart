<?php

// ── Flash ─────────────────────────────────────────────────────────────────────

function setFlash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    if (empty($_SESSION['flash_message']))
        return null;

    $flash = [
        'type' => $_SESSION['flash_type'],
        'message' => $_SESSION['flash_message'],
    ];

    unset($_SESSION['flash_type'], $_SESSION['flash_message']);
    return $flash;
}

// ── Event Status ──────────────────────────────────────────────────────────────

function resolveEventStatus(string $dbStatus, string $eventDate): array
{
    if ($dbStatus === 'live')
        return ['live', 'Live', 'badge--live'];
    if ($dbStatus === 'completed')
        return ['completed', 'Completed', 'badge--completed'];

    // "auto" only turns Live/Completed when an admin explicitly sets it; a same-day
    // schedule stays Upcoming until then, it only auto-completes once its date has passed.
    $today = date('Y-m-d');
    if ($eventDate < $today)
        return ['completed', 'Completed', 'badge--completed'];
    return ['upcoming', 'Upcoming', 'badge--upcoming'];
}

// ── DB Table Bootstrap ────────────────────────────────────────────────────────

function ensureGameTables($conn)
{
    $tableExists = function (string $tableName) use ($conn): bool {
        $result = $conn->query("SHOW TABLES LIKE '$tableName'");
        return $result && $result->num_rows > 0;
    };

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
}

// ── Session Timer Column Bootstrap ───────────────────────────────────────────

function ensureScheduleTimerColumn($conn)
{
    $columnExists = function (string $table, string $column) use ($conn): bool {
        $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '" . $conn->real_escape_string($column) . "'");
        return $result && $result->num_rows > 0;
    };

    if (!$columnExists('schedules', 'timer_minutes')) {
        $conn->query('ALTER TABLE `schedules` ADD COLUMN `timer_minutes` INT DEFAULT NULL AFTER `status`');
    }
    if (!$columnExists('sessions', 'timer_minutes')) {
        $conn->query('ALTER TABLE `sessions` ADD COLUMN `timer_minutes` INT DEFAULT NULL AFTER `best_lap_time`');
    }
}
