<?php
/* ================================================================
   LECTURER – Modernized Dashboard
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('lecturer','hod');

$pageTitle = 'Lecturer Dashboard';
$sem = currentSemester($conn);
$semId = $sem ? (int)$sem['id'] : 0;
$userId = (int)$_SESSION['user_id'];

/* Courses assigned this semester */
$courses = $conn->query(
    "SELECT c.id AS course_id, c.code, c.title, c.unit AS credit_units,
            COUNT(cr.id) AS enrolled_students,
            d.name AS dept_name,
            rb.id AS batch_id, rb.status AS batch_status
     FROM courses c
     LEFT JOIN departments d ON d.id = c.department_id
     LEFT JOIN course_registrations cr ON cr.course_id = c.id
     LEFT JOIN result_batches rb ON rb.course_id = c.id AND rb.semester_id = $semId AND rb.lecturer_id = $userId
     WHERE rb.lecturer_id = $userId OR rb.lecturer_id IS NULL
     GROUP BY c.id
     ORDER BY c.code"
);

/* Get stats */
$draft_count = $conn->query("SELECT COUNT(*) AS c FROM result_batches WHERE lecturer_id = $userId AND status = 'draft'")->fetch_assoc()['c'];
$submitted_count = $conn->query("SELECT COUNT(*) AS c FROM result_batches WHERE lecturer_id = $userId AND status = 'submitted'")->fetch_assoc()['c'];
$approved_count = $conn->query("SELECT COUNT(*) AS c FROM result_batches WHERE lecturer_id = $userId AND status = 'hod_approved'")->fetch_assoc()['c'];

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern-dashboard.css">

<div class="page-header">
    <div class="container">
        <h2><i class="bi bi-book"></i> Lecturer Dashboard</h2>
        <small><?= h($sem['session_name'] ?? '') ?> · Semester <?= h($sem['semester_number'] ?? '') ?></small>
    </div>
</div>

<div class="container">
    <!-- QUICK STATS -->
    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value"><?= $draft_count ?></div>
                        <div class="stat-label">Draft Batches</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-file-earmark"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value"><?= $submitted_count ?></div>
                        <div class="stat-label">Submitted</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value"><?= $approved_count ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value"><?= $courses->num_rows ?></div>
                        <div class="stat-label">Assigned Courses</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-book"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MY COURSES -->
    <h4 class="mb-4"><i class="bi bi-book-fill"></i> My Courses This Semester</h4>

    <?php if ($courses->num_rows === 0): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No courses assigned to you for this semester. Contact the administrator.
        </div>
    <?php else: ?>
        <div class="row g-4 mb-5">
            <?php while ($c = $courses->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1"><?= h($c['code']) ?></h6>
                                <small class="text-muted"><?= h($c['dept_name']) ?></small>
                            </div>
                            <?php if ($c['batch_status']): ?>
                                <span class="badge badge-<?= $c['batch_status'] ?>"><?= ucfirst($c['batch_status']) ?></span>
                            <?php else: ?>
                                <span class="badge badge-light">No batch</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?= h($c['title']) ?></h5>
                        <div class="row g-2 text-center mb-3">
                            <div class="col-6">
                                <small class="text-muted">Credit Units</small>
                                <div style="font-weight: bold; font-size: 1.2rem;"><?= $c['credit_units'] ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Enrolled</small>
                                <div style="font-weight: bold; font-size: 1.2rem;"><?= $c['enrolled_students'] ?></div>
                            </div>
                        </div>
                        <a href="upload.php?course_id=<?= $c['course_id'] ?>" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-upload"></i> Upload / Edit Marks
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <!-- WORKFLOW GUIDE -->
    <div class="card mb-5">
        <div class="card-header">
            <i class="bi bi-shuffle"></i> Mark Submission Workflow
        </div>
        <div class="card-body">
            <div class="row text-center g-4">
                <div class="col-md-3">
                    <div style="font-size: 2rem; color: #6B7280; margin-bottom: 0.5rem;">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h6>1. Enter Marks</h6>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Upload or input student marks for your courses</p>
                </div>
                <div class="col-md-3">
                    <div style="font-size: 2rem; color: var(--dark); margin-bottom: 0.5rem;">
                        <i class="bi bi-arrow-right" style="opacity: 0.3;"></i>
                    </div>
                    <h6>2. Submit to HOD</h6>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Submit for HOD approval</p>
                </div>
                <div class="col-md-3">
                    <div style="font-size: 2rem; color: var(--dark); margin-bottom: 0.5rem;">
                        <i class="bi bi-arrow-right" style="opacity: 0.3;"></i>
                    </div>
                    <h6>3. HOD Reviews</h6>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">HOD approves the marks</p>
                </div>
                <div class="col-md-3">
                    <div style="font-size: 2rem; color: var(--success); margin-bottom: 0.5rem;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h6>4. Publish</h6>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Admin publishes results</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4"></div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
