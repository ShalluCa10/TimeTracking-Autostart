<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

define('APP_NAME', 'TimeTracking Autostart');

$defaultAutostartRoot = 'C:\\Users\\Humber\\Autostart';
define('AUTOSTART_ROOT', getenv('AUTOSTART_ROOT') ?: $defaultAutostartRoot);
define('AUTOSTART_PYTHON_EXE', getenv('AUTOSTART_PYTHON_EXE') ?: (AUTOSTART_ROOT . '\\.venv\\Scripts\\python.exe'));
define('AUTOSTART_API_SCRIPT', getenv('AUTOSTART_API_SCRIPT') ?: (AUTOSTART_ROOT . '\\api.py'));
define('AUTOSTART_API_LOG', getenv('AUTOSTART_API_LOG') ?: (AUTOSTART_ROOT . '\\python_api.log'));

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host= $_SERVER['HTTP_HOST'];
$path= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
define('BASE_URL', $protocol . '://' . $host . $path);
