<?php
/* ================================================================
   SwiftGrade – Change Password
   ================================================================ */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/audit.php';

requireLogin();

$pageTitle = 'Change Password';
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();

    $current = trim($_POST['current_password'] ?? '');
    $newPass = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($newPass !== $confirm) {
        $msg = 'New passwords do not match.';
        $msgType = 'danger';
    } elseif (strlen($newPass) < 6) {
        $msg = 'Password must be at least 6 characters.';
        $msgType = 'danger';
    } else {
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!password_verify($current, $row['password_hash'])) {
            $msg = 'Current password is incorrect.';
            $msgType = 'danger';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?");
            $upd->bind_param('si', $hash, $_SESSION['user_id']);
            $upd->execute();

            $_SESSION['must_change_password'] = 0;

            logAudit($conn, 'PASSWORD_CHANGE', 'user', $_SESSION['user_id'], 'Password changed');

            $msg = 'Password updated successfully!';
            $msgType = 'success';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h4 class="mb-3"><i class="bi bi-key"></i> Change Password</h4>

                    <?php if ($_SESSION['must_change_password'] ?? 0): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        You must change your password before continuing.
                    </div>
                    <?php endif; ?>

                    <?php if ($msg): ?>
                    <div class="alert alert-<?= $msgType ?>">
                        <?= h($msg) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-lsc w-100">
                            <i class="bi bi-check-lg"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
