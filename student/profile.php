<?php
/* ================================================================
   STUDENT – Profile & Settings
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('student');

$userId = (int)$_SESSION['user_id'];
$msg = '';
$msgType = '';

/* Get student and user info */
$data = $conn->query(
    "SELECT s.*, u.email, u.full_name, u.username, p.name AS program_name,
            d.name AS dept_name
     FROM students s
     JOIN users u ON u.id = s.user_id
     JOIN programs p ON p.id = s.program_id
     JOIN departments d ON d.id = s.department_id
     WHERE s.user_id = $userId"
)->fetch_assoc();

if (!$data) {
    die('Profile not found.');
}

/* Handle profile update */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Invalid email address.';
        $msgType = 'danger';
    } else {
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param('si', $email, $userId);
        
        if ($stmt->execute()) {
            $msg = '✓ Profile updated successfully.';
            $msgType = 'success';
            $data['email'] = $email;
        } else {
            $msg = 'Error updating profile.';
            $msgType = 'danger';
        }
        $stmt->close();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .profile-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .profile-header {
        background: linear-gradient(135deg, #006B3F 0%, #004D2C 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }
    .profile-avatar {
        width: 100px;
        height: 100px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        margin: 0 auto 1rem;
    }
    .info-group {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #E5E7EB;
    }
    .info-group:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .info-label {
        color: #6B7280;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }
    .info-value {
        font-size: 1.05rem;
        color: #1F2937;
        font-weight: 500;
    }
</style>

<div class="page-header">
    <div class="container">
        <h2><i class="bi bi-person-circle"></i> My Profile</h2>
        <small>View and manage your account information</small>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <!-- Left Column - Profile Info -->
        <div class="col-lg-4">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h4 class="mb-1"><?= h($data['full_name']) ?></h4>
                    <small><?= h($data['program_name']) ?></small>
                </div>
                <div class="card-body">
                    <div class="info-group">
                        <div class="info-label">Matric Number</div>
                        <div class="info-value" style="font-family: monospace;">
                            <?= h($data['matric_no']) ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Username</div>
                        <div class="info-value"><?= h($data['username']) ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Department</div>
                        <div class="info-value"><?= h($data['dept_name']) ?></div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Current Level</div>
                        <div class="info-value"><?= (int)$data['level'] ?>/400</div>
                    </div>
                    <div class="info-group">
                        <div class="info-label">Admission Year</div>
                        <div class="info-value"><?= $data['admission_year'] ?? 'N/A' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Settings -->
        <div class="col-lg-8">
            <!-- Messages -->
            <?php if ($msg): ?>
                <div class="alert alert-<?= $msgType ?> alert-dismissible fade show mb-4">
                    <?= h($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Edit Email -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-envelope"></i> Email Address
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= h($data['email'] ?? '') ?>" required>
                            <small class="text-muted">
                                We'll use this for important notifications and password recovery.
                            </small>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Email
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-key"></i> Security
                </div>
                <div class="card-body">
                    <a href="<?= BASE_URL ?>/swiftgrade_change_password.php" class="btn btn-warning">
                        <i class="bi bi-shield-lock"></i> Change Password
                    </a>
                    <small class="d-block mt-3 text-muted">
                        Change your password regularly to keep your account secure.
                    </small>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-lightning"></i> Quick Actions
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="register_courses.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-journal-plus"></i> Register Courses
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="results.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-file-earmark-bar-graph"></i> View Results
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="transcript.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-book"></i> Transcript
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="academic_standing.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-graph-up"></i> Academic Standing
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-info-circle"></i> About Your Account
                </div>
                <div class="card-body">
                    <ul style="font-size: 0.95rem;">
                        <li>Your profile information is maintained by the institution</li>
                        <li>Contact the registrar's office to update matric number or program</li>
                        <li>Ensure your email is correct for receiving important notifications</li>
                        <li>Your password is encrypted and never shared</li>
                        <li>All activities are logged for security purposes</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
