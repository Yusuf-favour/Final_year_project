<?php
/* ================================================================
   STUDENT – Course Schedule/Timetable
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('student');

$userId = (int)$_SESSION['user_id'];
$sem = currentSemester($conn);
$semId = $sem ? (int)$sem['id'] : 0;

/* Get student */
$student = $conn->query(
    "SELECT s.*, p.name AS program_name FROM students s
     JOIN programs p ON p.id = s.program_id WHERE s.user_id = $userId"
)->fetch_assoc();

$studentId = (int)$student['id'];

/* Get registered courses for this semester */
$courses = $conn->query(
    "SELECT c.id, c.code, c.title, c.level, COALESCE(c.credit_units, c.unit, 0) AS credit_units,
            d.name AS dept_name, cr.created_at
     FROM course_registrations cr
     JOIN courses c ON c.id = cr.course_id
     LEFT JOIN departments d ON d.id = c.department_id
     WHERE cr.student_id = $studentId AND cr.semester_id = $semId
     ORDER BY c.code ASC"
);

include __DIR__ . '/../includes/header.php';
?>

<style>
    .schedule-card {
        border-left: 4px solid #006B3F;
        transition: all 0.3s ease;
    }
    .schedule-card:hover {
        box-shadow: 0 4px 12px rgba(0,107,63,0.15);
        transform: translateY(-2px);
    }
    .course-code {
        font-weight: 700;
        color: #006B3F;
        font-family: monospace;
    }
    .course-details {
        background: #F9FAFB;
        padding: 1rem;
        border-radius: 6px;
        margin-top: 0.5rem;
    }
</style>

<div class="page-header">
    <div class="container">
        <h2><i class="bi bi-calendar-event"></i> Course Schedule</h2>
        <small>Registered courses for <?= h($sem['session_name'] ?? 'Current Session') ?> · Semester <?= h($sem['semester_number'] ?? '-') ?></small>
    </div>
</div>

<div class="container pb-5">
    <?php if (!$courses || $courses->num_rows === 0): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> You have not registered any courses for this semester.
            <a href="register_courses.php" class="alert-link">Register courses now</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php while ($course = $courses->fetch_assoc()): 
                $resultQ = $conn->query(
                    "SELECT r.grade, r.total_score FROM results r
                     JOIN result_batches rb ON rb.id = r.batch_id
                     WHERE rb.course_id = " . (int)$course['id'] . " AND r.student_id = $studentId 
                     AND rb.status = 'published' LIMIT 1"
                );
                $result = $resultQ ? $resultQ->fetch_assoc() : null;
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card schedule-card h-100">
                    <div class="card-body">
                        <div class="course-code"><?= h($course['code']) ?></div>
                        <h6 class="mt-2 mb-1"><?= h($course['title']) ?></h6>
                        <small class="text-muted"><?= h($course['dept_name'] ?? 'Department') ?></small>
                        
                        <div class="course-details">
                            <div class="row g-2 text-center">
                                <div class="col-6">
                                    <small class="d-block text-muted">Credit Units</small>
                                    <strong><?= (int)$course['credit_units'] ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="d-block text-muted">Level</small>
                                    <strong><?= (int)$course['level'] ?></strong>
                                </div>
                            </div>
                        </div>

                        <?php if ($result): ?>
                            <div class="mt-3 p-2 bg-light rounded">
                                <small class="d-block text-muted mb-1">Grade</small>
                                <div style="font-size: 1.5rem; font-weight: bold; color: #006B3F;">
                                    <?= h($result['grade']) ?> 
                                    <span style="font-size: 0.8rem; color: #6B7280;">
                                        (<?= number_format((float)$result['total_score'], 2) ?>)
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mt-3 p-2 bg-light rounded">
                                <small class="text-muted"><i class="bi bi-clock"></i> Results pending</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Summary Stats -->
        <div class="row g-4 mt-5">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="display-6" style="color: #006B3F;">
                            <?= $courses->num_rows ?>
                        </div>
                        <small class="text-muted">Total Courses</small>
                    </div>
                </div>
            </div>
            <?php 
                $courses->data_seek(0);
                $totalCU = 0;
                while ($c = $courses->fetch_assoc()) {
                    $totalCU += (int)$c['credit_units'];
                }
            ?>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="display-6" style="color: #16A34A;">
                            <?= $totalCU ?>
                        </div>
                        <small class="text-muted">Total Credit Units</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="display-6">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <small class="text-muted">Registration Status</small>
                        <p class="mb-0 mt-2"><span class="badge bg-success">Active</span></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="display-6">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <small class="text-muted">Academic Period</small>
                        <p class="mb-0 mt-2"><?= h($sem['semester_number'] ?? '-') ?>/2</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="mt-5">
        <a href="register_courses.php" class="btn btn-primary">
            <i class="bi bi-journal-plus"></i> Modify Registration
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
