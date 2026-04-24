<?php
/* ================================================================
   STUDENT – Notifications & Alerts Center
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('student');

$userId = (int)$_SESSION['user_id'];

/* Get student */
$student = $conn->query(
    "SELECT s.* FROM students s WHERE s.user_id = $userId"
)->fetch_assoc();

$studentId = (int)$student['id'];

/* Generate system alerts based on student status */
$alerts = [];

/* Check for failed courses needing retake */
$failedQ = $conn->query(
    "SELECT COUNT(*) as fail_count FROM results r
     JOIN result_batches rb ON rb.id = r.batch_id
     WHERE r.student_id = $studentId AND rb.status = 'published' AND r.grade = 'F'"
);
if ($failedQ) {
    $failedData = $failedQ->fetch_assoc();
    if ((int)$failedData['fail_count'] > 0) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => 'exclamation-circle',
            'title' => 'Failed Courses',
            'message' => 'You have ' . (int)$failedData['fail_count'] . ' failed course(s) that need to be retaken.',
            'action_url' => 'degree_progress.php',
            'action_text' => 'View Details',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
}

/* Check CGPA status */
$cgpaQ = $conn->query(
    "SELECT ROUND(SUM(r.grade_point * c.credit_units) / NULLIF(SUM(c.credit_units),0), 2) AS cgpa
     FROM results r
     JOIN result_batches rb ON rb.id = r.batch_id
     JOIN courses c ON c.id = r.course_id
     WHERE r.student_id = $studentId AND rb.status = 'published'"
)->fetch_assoc();

$cgpa = (float)($cgpaQ['cgpa'] ?? 0);

if ($cgpa < 2.0 && $cgpa > 0) {
    $alerts[] = [
        'type' => 'danger',
        'icon' => 'exclamation-triangle',
        'title' => 'Academic Probation',
        'message' => 'Your CGPA is below 2.0. You may be placed on academic probation.',
        'action_url' => 'academic_standing.php',
        'action_text' => 'View Status',
        'created_at' => date('Y-m-d H:i:s')
    ];
} elseif ($cgpa >= 3.5) {
    $alerts[] = [
        'type' => 'success',
        'icon' => 'star',
        'title' => 'Excellent Performance',
        'message' => 'Your CGPA of ' . number_format($cgpa, 2) . ' demonstrates excellent academic performance!',
        'action_url' => 'academic_standing.php',
        'action_text' => 'View Details',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

/* Check for pending course registration */
$sem = currentSemester($conn);
if ($sem) {
    $regCount = $conn->query(
        "SELECT COUNT(*) as count FROM course_registrations cr
         WHERE cr.student_id = $studentId AND cr.semester_id = " . (int)$sem['id']
    )->fetch_assoc();
    
    if ((int)($regCount['count'] ?? 0) === 0) {
        $alerts[] = [
            'type' => 'info',
            'icon' => 'info-circle',
            'title' => 'Course Registration',
            'message' => 'You have not registered any courses for the current semester.',
            'action_url' => 'register_courses.php',
            'action_text' => 'Register Now',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
}

/* Check for pending results */
$pendingQ = $conn->query(
    "SELECT COUNT(*) as count FROM course_registrations cr
     LEFT JOIN results r ON r.student_id = cr.student_id 
        AND r.batch_id IN (
            SELECT id FROM result_batches WHERE course_id = cr.course_id
        )
     WHERE cr.student_id = $studentId AND r.id IS NULL"
);
if ($pendingQ) {
    $pendingData = $pendingQ->fetch_assoc();
    if ((int)$pendingData['count'] > 0) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => 'clock',
            'title' => 'Pending Results',
            'message' => 'You have ' . (int)$pendingData['count'] . ' course(s) awaiting grade publication.',
            'action_url' => 'results.php',
            'action_text' => 'Check Results',
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .alert-card {
        border-left: 4px solid;
        border-radius: 8px;
        transition: all 0.3s ease;
        margin-bottom: 1rem;
    }
    .alert-card:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .alert-success { border-left-color: #16A34A; background: #F0FDF4; }
    .alert-warning { border-left-color: #F59E0B; background: #FFFBEB; }
    .alert-danger { border-left-color: #EF4444; background: #FEF2F2; }
    .alert-info { border-left-color: #3B82F6; background: #F0F9FF; }
    
    .alert-icon {
        font-size: 1.5rem;
        margin-right: 1rem;
    }
    .alert-success .alert-icon { color: #16A34A; }
    .alert-warning .alert-icon { color: #F59E0B; }
    .alert-danger .alert-icon { color: #EF4444; }
    .alert-info .alert-icon { color: #3B82F6; }
    
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
    }
    .empty-state-icon {
        font-size: 4rem;
        color: #D1D5DB;
        margin-bottom: 1rem;
    }
</style>

<div class="page-header">
    <div class="container">
        <h2><i class="bi bi-bell"></i> Notifications & Alerts</h2>
        <small>Important updates and system notifications</small>
    </div>
</div>

<div class="container pb-5">
    <?php if (empty($alerts)): ?>
        <div class="card text-center">
            <div class="card-body">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h5>No Active Alerts</h5>
                    <p class="text-muted mb-0">You're all caught up! Everything looks good with your account.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <?php foreach ($alerts as $alert): ?>
                <div class="alert-card card alert-<?= h($alert['type']) ?>">
                    <div class="card-body d-flex align-items-start">
                        <i class="bi bi-<?= h($alert['icon']) ?> alert-icon flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-bold"><?= h($alert['title']) ?></h6>
                            <p class="mb-2 text-muted"><?= h($alert['message']) ?></p>
                            <a href="<?= h($alert['action_url']) ?>" class="btn btn-sm btn-outline-primary">
                                <?= h($alert['action_text']) ?> <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Sidebar: Quick Stats -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-light fw-bold">
                        <i class="bi bi-lightning"></i> Quick Stats
                    </div>
                    <div class="card-body">
                        <div class="mb-3 pb-3 border-bottom">
                            <small class="d-block text-muted">Alerts</small>
                            <strong class="display-6"><?= count($alerts) ?></strong>
                        </div>

                        <div class="mb-3 pb-3 border-bottom">
                            <small class="d-block text-muted">CGPA</small>
                            <strong class="display-6"><?= number_format($cgpa, 2) ?></strong>
                        </div>

                        <div>
                            <small class="d-block text-muted">Status</small>
                            <?php if ($cgpa < 2.0): ?>
                                <span class="badge bg-danger">At Risk</span>
                            <?php elseif ($cgpa >= 3.5): ?>
                                <span class="badge bg-success">Excellent</span>
                            <?php elseif ($cgpa >= 2.5): ?>
                                <span class="badge bg-info">Good</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Fair</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-light fw-bold">
                        <i class="bi bi-info-circle"></i> System Info
                    </div>
                    <div class="card-body">
                        <p class="mb-2 text-muted small">
                            <i class="bi bi-clock"></i>
                            Alerts generated on: <strong><?= date('M d, Y g:i A') ?></strong>
                        </p>
                        <p class="mb-0 text-muted small">
                            <i class="bi bi-arrow-repeat"></i>
                            Refresh this page to see latest updates
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recommended Actions -->
    <div class="mt-5">
        <h5 class="mb-3"><i class="bi bi-list-check"></i> Recommended Next Steps</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">✓ Check Your Transcript</h6>
                        <p class="text-muted small mb-3">View your complete academic history and GPA</p>
                        <a href="transcript.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark"></i> View Transcript
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">✓ Review Academic Standing</h6>
                        <p class="text-muted small mb-3">Check your academic status and classifications</p>
                        <a href="academic_standing.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-graph-up"></i> Academic Standing
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">✓ Monitor Degree Progress</h6>
                        <p class="text-muted small mb-3">Track your progress towards degree completion</p>
                        <a href="degree_progress.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-mortarboard"></i> Degree Progress
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">✓ Review Courses & Grades</h6>
                        <p class="text-muted small mb-3">See all your enrolled courses and grades</p>
                        <a href="course_schedule.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-calendar-event"></i> Course Schedule
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="mt-4">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
