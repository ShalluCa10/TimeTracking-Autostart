<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Already logged in, skip
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $conn = getConnection();

    $stmt = $conn->prepare('SELECT admin_id, username, password_hash FROM admins WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin  = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['username'] = $admin['username'];
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Wrong username or password.';
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login — <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body{ background:#0d0d0d; color:#fff; }
        .login-page{ display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .login-box{ width:320px; padding:28px; background:rgba(10,10,10,0.7); border-radius:10px; border:1px solid #222; }
        .login-box h2{ margin-bottom:16px; font-weight:700; }
        .login-box input{ width:100%; margin-bottom:12px; padding:10px; border-radius:6px; border:1px solid #333; background:#0b0b0b; color:#fff }
        .btn--primary{ background:#e10600; border:none; color:#fff; padding:10px 14px; border-radius:6px; width:100%; }
    </style>
</head>
<body class="login-page">

<div class="login-box">
    <h2><?php echo APP_NAME; ?></h2>

    <?php if ($error != '') { ?>
        <p class="alert alert--error"><?php echo $error; ?></p>
    <?php } ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="btn--primary">Login</button>
    </form>
</div>

</body>
</html>
