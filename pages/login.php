<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Already logged in, skip
if (!empty($_SESSION['admin_id'])) {
    header('Location: /pages/admin/dashboard.php');
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
        header('Location: /pages/admin/dashboard.php');
        exit();
    } else {
        $error = 'Wrong username or password.';
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="min-vh-100 d-flex align-items-center justify-content-center bg-dark text-white">

<div class="card border-0 shadow-lg p-4" style="width:min(100%, 360px); background:rgba(10,10,10,0.82); border:1px solid #222;">
    <h2 class="h4 fw-bold mb-3"><?php echo APP_NAME; ?></h2>

    <?php if ($error != '') { ?>
        <div class="alert alert-danger py-2 mb-3"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <form method="POST" class="d-grid gap-3">
        <input type="text" name="username" class="form-control bg-dark text-white border-secondary" placeholder="Username" required>
        <input type="password" name="password" class="form-control bg-dark text-white border-secondary" placeholder="Password" required>
        <button type="submit" class="btn btn-danger w-100">Login</button>
    </form>
</div>

</body>
</html>
