<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' — ' . APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/../assets/css/style.css">
</head>
<body>

<div class="topbar">
    <span class="topbar__brand"><?php echo APP_NAME; ?></span>
    <div class="topbar__nav">
        <a href="<?php echo BASE_URL; ?>/../pages/manage_games.php" class="topbar__link">Manage Game</a>
        <span class="topbar__user">
            Hi, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
        </span>
        <a href="<?php echo BASE_URL; ?>/../pages/logout.php">Logout</a>
    </div>
</div>

<main class="container">
