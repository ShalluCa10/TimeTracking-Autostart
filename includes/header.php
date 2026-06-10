<?php
if (!defined('BASE_URL')) define('BASE_URL', '');
if (!defined('APP_NAME')) define('APP_NAME', 'F1 Lap Simulator');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($pageTitle ?? 'Dashboard') . ' — ' . APP_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<div class="topbar">
    <span class="topbar__brand"><?= APP_NAME ?></span>

    <div class="topbar__nav">
        <a href="/pages/dashboard.php"      class="topbar__link">Dashboard</a>
        <a href="/pages/manage_events.php"  class="topbar__link">Manage Events</a>
        <a href="/pages/simulation.php"     class="topbar__link">Simulator</a>
        <a href="/pages/manage_games.php"   class="topbar__link">Manage Game</a>
    </div>

    <div class="topbar__right">
        <span class="topbar__user">Hi, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
        <a href="/pages/logout.php" class="topbar__link">Logout</a>
    </div>
</div>
