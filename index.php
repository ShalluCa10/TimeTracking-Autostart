<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome — <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="min-vh-100 text-white">
<div class="min-vh-100 d-flex align-items-center" style="background-image: url('/assets/img/raceTrack1.svg'); background-size: cover; background-position: center;">
    <div class="w-100 py-5" style="background: linear-gradient(180deg, rgba(0,0,0,0.6), rgba(0,0,0,0.75));">
        <div class="container text-center">
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-md-8">
                    <h1 class="display-5 fw-bold"><?php echo APP_NAME; ?></h1>
                    <p class="lead text-light opacity-75">Fast laps, live events, and friendly competition — public leaderboard and simulator.</p>
                </div>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-12 col-sm-8 col-md-4">
                    <div class="card h-100 border-0 bg-dark bg-opacity-75 text-start p-4 d-flex flex-column">
                        <h3 class="mb-2">Leaderboard</h3>
                        <p class="text-light opacity-75">See the best laps across events and sessions — public view.</p>
                        <a href="/leaderboard.php" class="btn btn-danger mt-auto">View Leaderboard</a>
                    </div>
                </div>

                <div class="col-12 col-sm-8 col-md-4">
                    <div class="card h-100 border-0 bg-dark bg-opacity-75 text-start p-4 d-flex flex-column">
                        <h3 class="mb-2">Simulator</h3>
                        <p class="text-light opacity-75">Start a new session and record lap times in the simulator.</p>
                        <a href="/pages/simulation.php" class="btn btn-danger mt-auto">Open Simulator</a>
                    </div>
                </div>

                <div class="col-12 col-sm-8 col-md-4">
                    <div class="card h-100 border-0 bg-dark bg-opacity-75 text-start p-4 d-flex flex-column">
                        <h3 class="mb-2">Admin</h3>
                        <p class="text-light opacity-75">Admin login for managing events, games and sessions.</p>
                        <a href="/pages/login.php" class="btn btn-danger mt-auto">Admin Login</a>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-12 text-center text-light opacity-50 small">&copy; <?= date('Y') ?> F1 Lap Simulator</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
