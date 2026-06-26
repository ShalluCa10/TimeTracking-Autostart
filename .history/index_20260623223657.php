<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Already logged in, skip
if (!empty($_SESSION['admin_id'])) {
    header('Location: pages/dashboard.php');
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

// var_dump($admin);
// var_dump(password_verify($password, $admin['password_hash']));
// exit();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['username'] = $admin['username'];
        header('Location: pages/dashboard.php');
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
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: #1a1a1a; font-family: Inter, sans-serif; }
        .login-box { max-width: 400px; margin: 100px auto; padding: 30px; background-color: #2c2c2c; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.5); }
        .login-box h2 { text-align: center; margin-bottom: 20px; }
        .login-box input { width: 100%; padding: 10px; margin-bottom: 15px; border: none; border-radius: 4px; }
        .login-box button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .login-box button:hover { background-color: #0056b3; }
        .alert--error { color: #ff4d4d; text-align: center; margin-bottom: 15px; }
</head>
<body class="login-page">

<div class="login-box">
    <h2 style="color: white;"><?php echo APP_NAME; ?></h2>

    <?php if ($error != '') { ?>
        <p class="alert alert--error"><?php echo $error; ?></p>
    <?php } ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="btn btn--primary">Login</button>
    </form>
</div>

</body>
</html>
