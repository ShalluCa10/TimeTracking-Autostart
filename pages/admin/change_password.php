<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($currentPassword === '')
        $errors[] = 'Current password is required.';
    if (strlen($newPassword) < 8)
        $errors[] = 'New password must be at least 8 characters.';
    if ($newPassword !== $confirmPassword)
        $errors[] = 'New password and confirmation do not match.';

    if (empty($errors)) {
        $conn = getConnection();

        $stmt = $conn->prepare('SELECT password_hash FROM admins WHERE admin_id = ? LIMIT 1');
        $stmt->bind_param('i', $_SESSION['admin_id']);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $conn->prepare('UPDATE admins SET password_hash = ? WHERE admin_id = ?');
            $stmt->bind_param('si', $newHash, $_SESSION['admin_id']);
            $stmt->execute();
            $stmt->close();

            setFlash('success', 'Password updated successfully.');
            $conn->close();
            header('Location: /pages/admin/change_password.php');
            exit();
        }

        $conn->close();
    }
}

$pageTitle = 'Change Password';
include __DIR__ . '/../../includes/header.php';
?>

<?php $flash = getFlash(); ?>
<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> mb-4">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 480px;">
    <div class="card-header">
        <h3>Change Password</h3>
    </div>
    <div class="card-body">
        <form method="POST" class="d-grid gap-3">
            <div>
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div>
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" minlength="8" required>
            </div>
            <div>
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" minlength="8" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
