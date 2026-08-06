<?php
if (!defined('BASE_URL')) define('BASE_URL', '');
if (!defined('APP_NAME')) define('APP_NAME', 'F1 Lap Simulator');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($pageTitle ?? 'Simulator') . ' - ' . APP_NAME ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400..900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
          crossorigin="anonymous">

    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="/"><?= APP_NAME ?></a>

            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#publicNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="publicNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'simulation.php' ? 'active' : '' ?>" href="/simulation.php">Live Simulator</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'leaderboard.php' ? 'active' : '' ?>" href="/leaderboard.php">Leaderboard</a>
                    </li>
                    <li class="nav-item">
                        <?php if (!empty($_SESSION['admin_id'])): ?>
                            <a class="nav-link" href="/pages/admin/dashboard.php">Go to Admin Dashboard</a>
                        <?php else: ?>
                            <a class="nav-link" href="/index.php">Sign In</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container-fluid py-4 px-4">
