<?php
/* ================================================================
   LECTURER – My Courses Management
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('lecturer', 'hod');

$userId = (int)$_SESSION['user_id'];
$sem = currentSemester($conn);
$semId = $sem ? (int)$sem['id'] : 0;

/* Get all assigned courses for current semester */
$courses = $conn->query(
    "SELECT ca.id AS assignment_id, c.id AS course_id, c.code, c.title, 
            COALESCE(c.credit_units, c.unit, 0) AS credit_units, c.level,
            COUNT(DISTINCT cr.id) AS enrolled_students,
            d.name AS dept_name,
            COALESCE(rb.id, 0) AS batch_id, 
            COALESCE(rb.status, 'not_created') AS batch_status,
            COUNT(DISTINCT r.id) AS results_entered
     FROM course_assignments ca
     JOIN courses c ON c.id = ca.course_id
     LEFT JOIN departments d ON d.id = c.department_id
     LEFT JOIN course_registrations cr ON cr.course_id = ca.course_id AND cr.semester_id = ca.semester_id
     LEFT JOIN result_batches rb ON rb.course_id = ca.course_id AND rb.semester_id = ca.semester_id AND rb.lecturer_id = ca.lecturer_id
     LEFT JOIN results r ON r.batch_id = rb.id
     WHERE ca.lecturer_id = $userId AND ca.semester_id = $semId
     GROUP BY ca.id, c.id, c.code, c.title, c.credit_units, c.unit, c.level, d.name, rb.id, rb.status
     ORDER BY c.code ASC"
);

$courseRows = [];
$totalStudents = 0;
$totalResultsEntered = 0;
if ($courses && $courses->num_rows > 0) {
    while ($courseRow = $courses->fetch_assoc()) {
        $courseRows[] = $courseRow;
        $totalStudents += (int)$courseRow['enrolled_students'];
        $totalResultsEntered += (int)$courseRow['results_entered'];
    }
}

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern-dashboard.css">

<style>
    .workspace-card {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
    }
    .workspace-card .header {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .workspace-card .body {
        padding: 0.9rem 1rem;
    }
    .workspace-table th,
    .workspace-table td {
        font-size: 0.9rem;
        padding: 0.65rem 0.5rem;
        vertical-align: middle;
    }
    .mini-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .mini-stats .item {
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        padding: 0.8rem;
    }
    .mini-stats .value {
        font-size: 1.35rem;
        font-weight: 700;
        color: #0F172A;
    }
    .mini-stats .label {
        font-size: 0.78rem;
        text-transform: uppercase;
        color: #6B7280;
    }
    @media (max-width: 992px) {
        .mini-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 576px) {
        .mini-stats { grid-template-columns: 1fr; }
    }
</style>

<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-book-fill"></i> My Courses</h2>
            <small><?= h($sem['session_name'] ?? 'Session') ?> • Semester <?= h($sem['semester_number'] ?? '-') ?></small>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="container pb-4">
    <?php if (empty($courseRows)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-inbox" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
            <p>No courses assigned to you this semester.</p>
            <small class="text-muted">Contact the admin if you believe this is an error.</small>
        </div>
    <?php else: ?>
        <div class="mini-stats">
            <div class="item"><div class="value"><?= count($courseRows) ?></div><div class="label">Courses</div></div>
            <div class="item"><div class="value"><?= $totalStudents ?></div><div class="label">Students</div></div>
            <div class="item"><div class="value"><?= $totalResultsEntered ?></div><div class="label">Marks Entered</div></div>
            <div class="item"><div class="value"><?= array_sum(array_map(function($r){ return ($r['batch_status'] ?? '') === 'submitted' ? 1 : 0; }, $courseRows)) ?></div><div class="label">Submitted Batches</div></div>
        </div>

        <div class="workspace-card">
            <div class="header">
                <h6 class="mb-0"><i class="bi bi-kanban me-2"></i>Course Workspace</h6>
                <small class="text-muted">Use this table to run your full result workflow per course</small>
            </div>
            <div class="body p-0">
                <div class="table-responsive">
                    <table class="table workspace-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Course</th>
                                <th>Dept</th>
                                <th>CU</th>
                                <th>Level</th>
                                <th>Students</th>
                                <th>Marks</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($courseRows as $course): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= h($course['code']) ?></div>
                                    <small class="text-muted"><?= h($course['title']) ?></small>
                                </td>
                                <td><?= h($course['dept_name'] ?? '—') ?></td>
                                <td><?= (int)$course['credit_units'] ?></td>
                                <td><?= (int)$course['level'] ?></td>
                                <td><?= (int)$course['enrolled_students'] ?></td>
                                <td><?= (int)$course['results_entered'] ?></td>
                                <td><?= statusBadge($course['batch_status'] ?? 'not_created') ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="upload.php?course_id=<?= (int)$course['course_id'] ?>" class="btn btn-outline-primary">Marks/Submit</a>
                                        <a href="class_register.php?course_id=<?= (int)$course['course_id'] ?>" class="btn btn-outline-secondary">Attendance</a>
                                        <a href="view_results.php?batch_id=<?= (int)$course['batch_id'] ?>" class="btn btn-outline-dark <?= (int)$course['batch_id'] > 0 ? '' : 'disabled' ?>">Results</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
