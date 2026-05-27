<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

define('APP_NAME', 'TimeTracking Autostart');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host= $_SERVER['HTTP_HOST'];
$path= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
define('BASE_URL', $protocol . '://' . $host . $path);
