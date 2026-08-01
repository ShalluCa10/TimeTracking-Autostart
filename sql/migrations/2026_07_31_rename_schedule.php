<?php
// One-time migration: rename events -> schedules (schedule_id/schedule_name/schedule_date/team/event)
// and drop unused legacy tables. Safe to re-run (idempotent).
// Usage: php sql/migrations/2026_07_31_rename_schedule.php

require_once __DIR__ . '/../../config/db.php';

$conn = getConnection();

function tableExists(mysqli $conn, string $table): bool
{
    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    return $result && $result->num_rows > 0;
}

function columnExists(mysqli $conn, string $table, string $column): bool
{
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '" . $conn->real_escape_string($column) . "'");
    return $result && $result->num_rows > 0;
}

// 1. Rename `events` -> `schedules` (only if schedules doesn't already exist)
if (tableExists($conn, 'events') && !tableExists($conn, 'schedules')) {
    $conn->query('RENAME TABLE `events` TO `schedules`');
    $conn->query("ALTER TABLE `schedules`
        CHANGE `event_id` `schedule_id` INT(11) NOT NULL AUTO_INCREMENT,
        CHANGE `event_name` `schedule_name` VARCHAR(150) NOT NULL,
        CHANGE `event_date` `schedule_date` DATE NOT NULL,
        CHANGE `car` `team` VARCHAR(100) DEFAULT NULL,
        CHANGE `track` `event` VARCHAR(100) DEFAULT NULL");
    echo "Renamed events -> schedules\n";
}

// 2. Update sessions table to reference schedule_id / team / event
if (tableExists($conn, 'sessions')) {
    if (columnExists($conn, 'sessions', 'event_id') && !columnExists($conn, 'sessions', 'schedule_id')) {
        $conn->query('ALTER TABLE `sessions` CHANGE `event_id` `schedule_id` INT(11) NOT NULL');
        echo "sessions.event_id -> schedule_id\n";
    }
    if (columnExists($conn, 'sessions', 'car') && !columnExists($conn, 'sessions', 'team')) {
        $conn->query("ALTER TABLE `sessions` CHANGE `car` `team` VARCHAR(100) NOT NULL DEFAULT ''");
        echo "sessions.car -> team\n";
    }
    if (columnExists($conn, 'sessions', 'track') && !columnExists($conn, 'sessions', 'event')) {
        $conn->query("ALTER TABLE `sessions` CHANGE `track` `event` VARCHAR(100) NOT NULL DEFAULT ''");
        echo "sessions.track -> event\n";
    }
}

// 3. Drop excess/unused legacy tables (old duplicate `events` + never-used normalized tables)
foreach (['events', 'cars', 'teams', 'tracks', 'games', 'rigs', 'results'] as $legacy) {
    if (tableExists($conn, $legacy)) {
        $conn->query("DROP TABLE IF EXISTS `$legacy`");
        echo "Dropped legacy table: $legacy\n";
    }
}

$conn->close();
echo "Migration complete.\n";
