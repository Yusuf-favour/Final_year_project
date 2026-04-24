<?php
/* ================================================================
   HOD – Dashboard
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('hod');

$pageTitle = 'HOD Dashboard';
$sem = currentSemester($conn);
$semId = $sem ? (int)$sem['id'] : 0;
$deptId = (int)($_SESSION['department_id'] ?? 0);

/* Pending batches for my department */
$pending = $conn->query(
    "SELECT rb.*, c.code AS course_code, c.title AS course_title,
            u.full_name AS lecturer_name,
            (SELECT COUNT(*) FROM results WHERE batch_id=rb.id) AS result_count
     FROM result_batches rb
     JOIN courses c ON c.id = rb.course_id
     JOIN users u ON u.id = rb.lecturer_id
     WHERE rb.status = 'submitted'
       AND c.department_id = $deptId
     ORDER BY rb.submitted_at DESC"
);

/* Recently approved */
$recent = $conn->query(
    "SELECT rb.*, c.code AS course_code, c.title AS course_title
     FROM result_batches rb
     JOIN courses c ON c.id = rb.course_id
     WHERE rb.hod_id = {$_SESSION['user_id']}
       AND rb.status IN ('hod_approved','published')
     ORDER BY rb.hod_approved_at DESC LIMIT 10"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
<div class="container">
    <h2><i class="bi bi-speedometer2"></i> HOD / Exam Officer Dashboard</h2>
    <small><?= h($sem['session_name'] ?? '') ?> · Semester <?= h($sem['semester_number'] ?? '') ?></small>
</div>
</div>

<div class="container mb-4">

<div class="row g-3 mb-4">
    <div class="col-sm-6">
        <div class="card card-stat shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value"><?= $pending->num_rows ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
                <div class="stat-icon"><i class="bi bi-clipboard-check"></i></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="card card-stat shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value"><?= $recent->num_rows ?></div>
                    <div class="stat-label">Recently Approved</div>
                </div>
                <div class="stat-icon"><i class="bi bi-check2-all"></i></div>
            </div>
        </div>
    </div>
</div>

<h5 class="text-lsc-green mb-3"><i class="bi bi-hourglass-split"></i> Awaiting Your Approval</h5>

<?php if ($pending->num_rows === 0): ?>
<div class="alert alert-info">No result batches awaiting your review.</div>
<?php else: ?>
<div class="row g-3">
<?php while ($b = $pending->fetch_assoc()): ?>
<div class="col-md-6">
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="text-lsc-green"><?= h($b['course_code']) ?></h5>
            <p class="mb-1"><?= h($b['course_title']) ?></p>
            <p class="text-muted mb-2">
                Submitted by <strong><?= h($b['lecturer_name']) ?></strong>
                <br><small><?= h($b['submitted_at']) ?></small>
            </p>
            <span class="badge bg-info mb-2"><?= $b['result_count'] ?> results</span>
            <div>
                <a href="review.php?batch_id=<?= $b['id'] ?>" class="btn btn-sm btn-lsc">
                    <i class="bi bi-eye"></i> Review & Approve
                </a>
            </div>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>
<?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
