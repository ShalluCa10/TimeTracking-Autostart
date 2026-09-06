<?php
// Add formula and assist columns to schedules table
// Usage: php sql/migrations/2026_09_05_add_formula_assist.php

require_once __DIR__ . '/../../config/db.php';

$conn = getConnection();

function columnExists(mysqli $conn, string $table, string $column): bool
{
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '" . $conn->real_escape_string($column) . "'");
    return $result && $result->num_rows > 0;
}

// Add formula column if it doesn't exist
if (!columnExists($conn, 'schedules', 'formula')) {
    $conn->query("ALTER TABLE `schedules` ADD COLUMN `formula` VARCHAR(20) DEFAULT NULL AFTER `version_id`");
    echo "Added formula column to schedules\n";
}

// Add assist column if it doesn't exist
if (!columnExists($conn, 'schedules', 'assist')) {
    $conn->query("ALTER TABLE `schedules` ADD COLUMN `assist` VARCHAR(50) DEFAULT NULL AFTER `formula`");
    echo "Added assist column to schedules\n";
}

// Add formula and assist to sessions table if they don't exist
if (!columnExists($conn, 'sessions', 'formula')) {
    $conn->query("ALTER TABLE `sessions` ADD COLUMN `formula` VARCHAR(20) DEFAULT NULL AFTER `f1_version`");
    echo "Added formula column to sessions\n";
}

if (!columnExists($conn, 'sessions', 'assist')) {
    $conn->query("ALTER TABLE `sessions` ADD COLUMN `assist` VARCHAR(50) DEFAULT NULL AFTER `formula`");
    echo "Added assist column to sessions\n";
}

$conn->close();
echo "Migration complete!\n";
