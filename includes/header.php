<?php
if (!defined('BASE_URL'))  define('BASE_URL', '');
if (!defined('APP_NAME'))  define('APP_NAME', 'F1 Lap Simulator');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($pageTitle ?? 'Dashboard') . ' - ' . APP_NAME ?></title>


<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400..900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
          crossorigin="anonymous">

    <!-- overrides Bootstrap -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
   <nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="/pages/dashboard.php"><?= APP_NAME ?></a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <!-- Center links -->
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php'     ? 'active' : '' ?>" href="/pages/dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'manage_events.php' ? 'active' : '' ?>" href="/pages/manage_events.php">Manage Events</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'simulation.php'    ? 'active' : '' ?>" href="/pages/simulation.php">Simulator</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'manage_games.php'  ? 'active' : '' ?>" href="/pages/manage_games.php">Manage Game</a>
                </li>
            </ul>

            <!-- Right: Hi + Logout -->
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <span class="nav-link disabled">Hi, <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/pages/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>


    <main class="container-fluid py-4 px-4">
