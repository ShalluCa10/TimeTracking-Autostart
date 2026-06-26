<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome — <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body{ 
           
        min-height:100vh; background-image: url('/assets/img/raceTrack1.svg'); background-size:cover; background-position:center; color: #fff;}
        .overlay{ background: linear-gradient(180deg, rgba(0,0,0,0.6), rgba(0,0,0,0.75)); min-height:100vh; }
        .hero { padding:6rem 1rem; color:#fff; }
        .card-cta { background: rgba(10,10,10,0.6); color:#fff; border:1px solid rgba(255,255,255,0.06); }
        .card-cta h3{ margin-bottom:8px; color:#fff; }
        .cta-btn{ background:#e10600; color:#fff; border:none; }
        @media (max-width:576px){ .hero{ padding:3rem 1rem } }
        p{
             color:#fff;
        }
    </style>
</head>
<body>
<div class="overlay d-flex align-items-center">
    <div class="container hero text-center">
        <div class="row justify-content-center mb-4">
            <div class="col-12 col-md-8">
                <h1 class="display-5 fw-bold">F1 Lap Simulator</h1>
                <p class="lead text-muted">Fast laps, live events, and friendly competition — public leaderboard and simulator.</p>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-12 col-sm-8 col-md-4">
                <div class="card card-cta p-4 text-start h-100">
                    <h3>Leaderboard</h3>
                    <p class="text-muted">See the best laps across events and sessions — public view.</p>
                    <a href="/leaderboard.php" class="btn cta-btn">View Leaderboard</a>
                </div>
            </div>

            <div class="col-12 col-sm-8 col-md-4">
                <div class="card card-cta p-4 text-start h-100">
                    <h3>Simulator</h3>
                    <p class="text-muted">Start a new session and record lap times in the simulator.</p>
                    <a href="/simulation.php" class="btn cta-btn">Open Simulator</a>
                </div>
            </div>

            <div class="col-12 col-sm-8 col-md-4">
                <div class="card card-cta p-4 text-start h-100">
                    <h3>Admin</h3>
                    <p class="text-muted">Admin login for managing events, games and sessions.</p>
                    <a href="/pages/login.php" class="btn cta-btn">Admin Login</a>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center text-muted">&copy; <?= date('Y') ?> F1 Lap Simulator</div>
        </div>
    </div>
</div>

</body>
</html>
