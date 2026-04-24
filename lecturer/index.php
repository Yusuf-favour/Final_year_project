<?php
/* ================================================================
   LECTURER – Dashboard
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
    "SELECT ca.id AS assignment_id, c.id AS course_id, c.code, c.title,
            COALESCE(c.credit_units, c.unit, 0) AS credit_units,
            COUNT(DISTINCT cr.id) AS enrolled_students,
          COUNT(DISTINCT r.id) AS results_entered,
            d.name AS dept_name,
            rb.id AS batch_id, rb.status AS batch_status
     FROM course_assignments ca
     JOIN courses c ON c.id = ca.course_id
     LEFT JOIN departments d ON d.id = c.department_id
     LEFT JOIN course_registrations cr ON cr.course_id = ca.course_id AND cr.semester_id = ca.semester_id
     LEFT JOIN result_batches rb ON rb.course_id = ca.course_id AND rb.semester_id = ca.semester_id AND rb.lecturer_id = ca.lecturer_id
    LEFT JOIN results r ON r.batch_id = rb.id
     WHERE ca.lecturer_id = $userId AND ca.semester_id = $semId
    GROUP BY ca.id, c.id, c.code, c.title, c.credit_units, c.unit, d.name, rb.id, rb.status
     ORDER BY c.code"
);

if (!$courses) {
    $courses = new class {
        public $num_rows = 0;
        public function fetch_assoc(){ return null; }
    };
}

$draftCount = 0;
$submittedCount = 0;
$publishedCount = 0;
$rejectedCount = 0;
$hodApprovedCount = 0;

$batchStats = $conn->query(
    "SELECT status, COUNT(*) AS total
     FROM result_batches
     WHERE lecturer_id = $userId AND semester_id = $semId
     GROUP BY status"
);

if ($batchStats) {
    while ($row = $batchStats->fetch_assoc()) {
        if ($row['status'] === 'draft') {
            $draftCount = (int)$row['total'];
        } elseif ($row['status'] === 'submitted') {
            $submittedCount = (int)$row['total'];
        } elseif ($row['status'] === 'hod_approved') {
            $hodApprovedCount = (int)$row['total'];
        } elseif ($row['status'] === 'published') {
            $publishedCount = (int)$row['total'];
        } elseif ($row['status'] === 'rejected') {
            $rejectedCount = (int)$row['total'];
        }
    }
}

$courseRows = [];
$totalStudents = 0;
$totalResultsEntered = 0;
if ($courses && $courses->num_rows > 0) {
    while ($row = $courses->fetch_assoc()) {
        $courseRows[] = $row;
        $totalStudents += (int)$row['enrolled_students'];
        $totalResultsEntered += (int)$row['results_entered'];
    }
}

$activeBatchCount = $draftCount + $submittedCount + $hodApprovedCount;

$attendanceSessions = 0;
$attendanceTableExists = false;
$attendanceCheck = $conn->query("SHOW TABLES LIKE 'attendance'");
if ($attendanceCheck && $attendanceCheck->num_rows > 0) {
    $attendanceTableExists = true;
    $attendanceQ = $conn->query(
        "SELECT COUNT(DISTINCT attendance_date) AS total
         FROM attendance
         WHERE lecturer_id = $userId AND semester_id = $semId"
    );
    if ($attendanceQ && ($aRow = $attendanceQ->fetch_assoc())) {
        $attendanceSessions = (int)$aRow['total'];
    }
}

$recentBatches = $conn->query(
    "SELECT rb.id, rb.status, rb.updated_at, rb.submitted_at, rb.published_at,
            c.code, c.title
     FROM result_batches rb
     JOIN courses c ON c.id = rb.course_id
     WHERE rb.lecturer_id = $userId AND rb.semester_id = $semId
     ORDER BY rb.updated_at DESC
     LIMIT 8"
);

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern-dashboard.css">

<style>
    .lecturer-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr);
        gap: 1rem;
    }
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .kpi-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        padding: 1rem;
    }
    .kpi-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0F172A;
        line-height: 1;
    }
    .kpi-label {
        margin-top: 0.35rem;
        font-size: 0.8rem;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .dashboard-block {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
    }
    .dashboard-block .block-header {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .dashboard-block .block-body {
        padding: 0.9rem 1rem;
    }
    .compact-table th,
    .compact-table td {
        font-size: 0.9rem;
        padding: 0.65rem 0.5rem;
        vertical-align: middle;
    }
    .quick-links a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-decoration: none;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        padding: 0.6rem 0.75rem;
        color: #1F2937;
        margin-bottom: 0.55rem;
    }
    .quick-links a:hover {
        border-color: #006B3F;
        color: #006B3F;
        background: #F8FBF9;
    }
    .activity-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .activity-list li {
        padding: 0.6rem 0;
        border-bottom: 1px dashed #E5E7EB;
        font-size: 0.9rem;
    }
    .activity-list li:last-child {
        border-bottom: 0;
    }
    @media (max-width: 1200px) {
        .kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 992px) {
        .lecturer-grid { grid-template-columns: 1fr; }
        .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 576px) {
        .kpi-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="page-header">
    <div class="container d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <h2><i class="bi bi-speedometer2"></i> Lecturer Dashboard</h2>
            <small><?= h($sem['session_name'] ?? 'Current Session') ?> · Semester <?= h($sem['semester_number'] ?? '-') ?></small>
        </div>
        <div class="d-flex gap-2">
            <a href="my_courses.php" class="btn btn-light border">
                <i class="bi bi-journal-text me-1"></i> Course Workspace
            </a>
            <a href="upload.php?course_id=<?= isset($courseRows[0]) ? (int)$courseRows[0]['course_id'] : 0 ?>" class="btn btn-primary <?= empty($courseRows) ? 'disabled' : '' ?>">
                <i class="bi bi-pencil-square me-1"></i> Start Mark Entry
            </a>
        </div>
    </div>
</div>

<div class="container pb-4">
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-value"><?= count($courseRows) ?></div>
            <div class="kpi-label">Assigned Courses</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value"><?= $totalStudents ?></div>
            <div class="kpi-label">Registered Students</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value"><?= $activeBatchCount ?></div>
            <div class="kpi-label">Active Batches</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value"><?= $submittedCount ?></div>
            <div class="kpi-label">Awaiting HOD Review</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value"><?= $publishedCount ?></div>
            <div class="kpi-label">Published Batches</div>
        </div>
    </div>

    <?php if ($rejectedCount > 0): ?>
        <div class="alert alert-warning py-2 mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            You have <strong><?= $rejectedCount ?></strong> rejected batch(es). Review and resubmit from your course workspace.
        </div>
    <?php endif; ?>

    <div class="lecturer-grid">
        <div>
            <div class="dashboard-block mb-3">
                <div class="block-header">
                    <h6 class="mb-0"><i class="bi bi-table me-2"></i>Course Operations Board</h6>
                    <small class="text-muted"><?= h($sem['session_name'] ?? 'Session') ?> / Semester <?= h($sem['semester_number'] ?? '-') ?></small>
                </div>
                <div class="block-body p-0">
                    <?php if (empty($courseRows)): ?>
                        <div class="p-3 alert alert-info mb-0 rounded-0 border-0">No courses assigned for this semester. Contact administrator.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table compact-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Course</th>
                                        <th>Students</th>
                                        <th>Marks Entered</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($courseRows as $c): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= h($c['code']) ?></div>
                                            <small class="text-muted"><?= h($c['title']) ?></small>
                                        </td>
                                        <td><?= (int)$c['enrolled_students'] ?></td>
                                        <td><?= (int)$c['results_entered'] ?></td>
                                        <td><?= $c['batch_status'] ? statusBadge($c['batch_status']) : '<span class="badge bg-light text-dark">not_created</span>' ?></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="upload.php?course_id=<?= (int)$c['course_id'] ?>" class="btn btn-outline-primary">Marks/Submit</a>
                                                <a href="class_register.php?course_id=<?= (int)$c['course_id'] ?>" class="btn btn-outline-secondary">Attendance</a>
                                                <a href="view_results.php?batch_id=<?= (int)$c['batch_id'] ?>" class="btn btn-outline-dark <?= (int)$c['batch_id'] > 0 ? '' : 'disabled' ?>">Results</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-block">
                <div class="block-header">
                    <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Batch Activity</h6>
                </div>
                <div class="block-body">
                    <?php if (!$recentBatches || $recentBatches->num_rows === 0): ?>
                        <div class="text-muted small">No batch activity yet for this semester.</div>
                    <?php else: ?>
                        <ul class="activity-list">
                            <?php while ($rb = $recentBatches->fetch_assoc()): ?>
                                <li>
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-semibold"><?= h($rb['code']) ?> · <?= h($rb['title']) ?></div>
                                            <div class="small text-muted">Updated <?= formatDateTime($rb['updated_at'], 'M d, Y h:i A') ?></div>
                                        </div>
                                        <div><?= statusBadge($rb['status']) ?></div>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <div class="dashboard-block mb-3">
                <div class="block-header">
                    <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
                </div>
                <div class="block-body quick-links">
                    <a href="my_courses.php"><span><i class="bi bi-journal-text me-2"></i>Open Course Workspace</span><i class="bi bi-arrow-right"></i></a>
                    <a href="<?= !empty($courseRows) ? 'upload.php?course_id='.(int)$courseRows[0]['course_id'] : '#' ?>" class="<?= empty($courseRows) ? 'disabled' : '' ?>"><span><i class="bi bi-pencil-square me-2"></i>Enter & Submit Marks</span><i class="bi bi-arrow-right"></i></a>
                    <a href="<?= !empty($courseRows) ? 'class_register.php?course_id='.(int)$courseRows[0]['course_id'] : '#' ?>" class="<?= empty($courseRows) ? 'disabled' : '' ?>"><span><i class="bi bi-clipboard-check me-2"></i>Take Attendance</span><i class="bi bi-arrow-right"></i></a>
                    <a href="<?= !empty($courseRows) ? 'course_analytics.php?course_id='.(int)$courseRows[0]['course_id'] : '#' ?>" class="<?= empty($courseRows) ? 'disabled' : '' ?>"><span><i class="bi bi-bar-chart-line me-2"></i>View Analytics</span><i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="dashboard-block mb-3">
                <div class="block-header">
                    <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Workflow Snapshot</h6>
                </div>
                <div class="block-body">
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Draft</span><strong><?= $draftCount ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Submitted</span><strong><?= $submittedCount ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">HOD Approved</span><strong><?= $hodApprovedCount ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Published</span><strong><?= $publishedCount ?></strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Rejected</span><strong><?= $rejectedCount ?></strong></div>
                </div>
            </div>

            <div class="dashboard-block">
                <div class="block-header">
                    <h6 class="mb-0"><i class="bi bi-person-check me-2"></i>Teaching Summary</h6>
                </div>
                <div class="block-body">
                    <div class="small text-muted mb-1">Total students managed</div>
                    <div class="h4 mb-2"><?= $totalStudents ?></div>
                    <div class="small text-muted mb-1">Total marks entries</div>
                    <div class="h5 mb-2"><?= $totalResultsEntered ?></div>
                    <div class="small text-muted mb-1">Attendance sessions</div>
                    <div class="h5 mb-0"><?= $attendanceTableExists ? $attendanceSessions : 0 ?></div>
                    <?php if (!$attendanceTableExists): ?>
                        <div class="small text-muted mt-2">Attendance table will initialize automatically on first attendance use.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
