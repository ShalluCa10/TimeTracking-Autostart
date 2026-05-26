<?php
// ============================================================
//  includes/header.php  —  HTML <head> + top nav
// ============================================================
// Call:  include __DIR__ . '/../includes/header.php';
// Assumes $pageTitle is set before including.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar__brand">🏎️ <?= APP_NAME ?></div>
    <?php if (!empty($_SESSION['admin_id'])): ?>
    <nav class="topbar__nav">
        <span class="topbar__user">👤 <?= e($_SESSION['username']) ?></span>
        <a href="<?= BASE_URL ?>/pages/logout.php" class="btn btn--sm btn--outline">Logout</a>
    </nav>
    <?php endif; ?>
</header>
<main class="container">
<?php renderFlash(); ?>
