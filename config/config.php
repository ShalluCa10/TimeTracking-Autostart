<?php
// ============================================================
//  config/config.php  —  App-wide constants
// ============================================================

define('APP_NAME',    'F1 TimeTracking Autostart');
define('APP_VERSION', '1.0');

// ── Auto-detect BASE_URL ──────────────────────────────────
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Get the folder this project lives in (works with any folder name)
$docRoot   = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
$relative  = str_replace($docRoot, '', $scriptDir); // e.g. /TimeTracking-Autostart/pages

// Walk up until we find config.php's actual project root
$configDir = str_replace('\\', '/', dirname(__FILE__));         // .../TimeTracking-Autostart/config
$projectDir = str_replace('\\', '/', dirname($configDir));      // .../TimeTracking-Autostart
$projectRoot = str_replace($docRoot, '', $projectDir);          // /TimeTracking-Autostart

define('BASE_URL', rtrim($scheme . '://' . $host . $projectRoot, '/'));

// API key used by the Python AutoStart script (Phase 2)
define('API_SECRET_KEY', 'api-key');

// Session lifetime in seconds (30 minutes)
define('SESSION_LIFETIME', 1800);

// Lap time regex: mm:ss.mmm
define('LAP_TIME_REGEX', '/^\d{2}:\d{2}\.\d{3}$/');

// Start PHP session once here
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}