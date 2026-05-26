<?php
// ============================================================
//  index.php  —  Login page
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Already logged in → go to dashboard
if (!empty($_SESSION['admin_id'])) {
    redirect('pages/dashboard.php');
}

$error = '';

// Handle URL messages (session expired, logged out)
$msg = $_GET['msg'] ?? '';
if ($msg === 'session_expired') {
    $error = 'Your session has ended. Please log in again.';
} elseif ($msg === 'logged_out') {
    $error = ''; // silent logout
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } elseif (attemptLogin($username, $password)) {
        setFlash('success', 'Welcome back, ' . $username . '!');
        redirect('pages/dashboard.php');
    } else {
        $error = 'Invalid username or password. Please try again.';
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-page">
<div class="login-card">
    <div class="login-card__logo">🏎️</div>
    <h1 class="login-card__title">F1 AutoStart</h1>
    <p class="login-card__sub">Staff Panel</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-group">
            <label for="username">Username <span class="required">*</span></label>
            <input type="text" id="username" name="username"
                   value="<?= e($_POST['username'] ?? '') ?>"
                   autocomplete="username" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password <span class="required">*</span></label>
            <input type="password" id="password" name="password"
                   autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn btn--primary btn--full">Log In</button>
    </form>
</div>
</body>
</html>
