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
        body{
            co
        }
    </style>
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
