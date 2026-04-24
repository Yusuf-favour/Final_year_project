<?php
/* ================================================================
   STUDENT – Degree Progress & Requirements
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('student');

$userId = (int)$_SESSION['user_id'];

/* Get student and program info */
$student = $conn->query(
    "SELECT s.*, p.name AS program_name, p.degree_type, p.duration_years, d.name AS dept_name
     FROM students s
     JOIN programs p ON p.id = s.program_id
     JOIN departments d ON d.id = s.department_id
     WHERE s.user_id = $userId"
)->fetch_assoc();

$studentId = (int)$student['id'];

/* Get all completed courses */
$completedResults = $conn->query(
    "SELECT r.grade, c.code, c.title, c.credit_units, c.level
     FROM results r
     JOIN result_batches rb ON rb.id = r.batch_id
     JOIN courses c ON c.id = r.course_id
     WHERE r.student_id = $studentId AND rb.status = 'published' AND r.grade != 'F'
     ORDER BY c.level ASC"
);

$completedCourses = [];
$totalCUCompleted = 0;
$coursesByLevel = [];

while ($course = $completedResults->fetch_assoc()) {
    $completedCourses[] = $course;
    $totalCUCompleted += (int)$course['credit_units'];
    
    $level = (int)$course['level'];
    if (!isset($coursesByLevel[$level])) {
        $coursesByLevel[$level] = ['count' => 0, 'cu' => 0];
    }
    $coursesByLevel[$level]['count']++;
    $coursesByLevel[$level]['cu'] += (int)$course['credit_units'];
}

/* Estimate total CU required for program (assumes 30 CU per level) */
$durationYears = (int)($student['duration_years'] ?? 4);
$estimatedTotalCU = $durationYears * 60; /* Typical: 4 years × 60 CU */

/* Calculate progress */
$progressPercent = $estimatedTotalCU > 0 ? round(($totalCUCompleted / $estimatedTotalCU) * 100, 1) : 0;
$cuRemaining = max(0, $estimatedTotalCU - $totalCUCompleted);

/* Estimated semesters remaining */
$semestersRemaining = $cuRemaining > 0 ? ceil($cuRemaining / 30) : 0;

/* Get failed courses that need retake */
$failedCourses = $conn->query(
    "SELECT c.code, c.title, c.credit_units, r.grade
     FROM results r
     JOIN result_batches rb ON rb.id = r.batch_id
     JOIN courses c ON c.id = r.course_id
     WHERE r.student_id = $studentId AND rb.status = 'published' AND r.grade = 'F'
     ORDER BY c.code ASC"
);

$failedCount = $failedCourses ? $failedCourses->num_rows : 0;

include __DIR__ . '/../includes/header.php';
?>

<style>
    .progress-card {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .level-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: #E8F5EE;
        color: #006B3F;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .requirement-item {
        padding: 1rem;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .requirement-item:last-child {
        border-bottom: none;
    }
    .requirement-status {
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.85rem;
    }
    .status-met { background: #DBEAFE; color: #0C4A6E; }
    .status-pending { background: #FEF3C7; color: #78350F; }
    .status-not-met { background: #FECACA; color: #7F1D1D; }
</style>

<div class="page-header">
    <div class="container">
        <h2><i class="bi bi-mortarboard-fill"></i> Degree Progress</h2>
        <small><?= h($student['program_name']) ?> (<?= h($student['degree_type'] ?? 'Degree') ?>) · <?= $durationYears ?> Years</small>
    </div>
</div>

<div class="container pb-5">
    <!-- Overall Progress -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="progress-card card">
                <div class="card-body">
                    <h6 class="mb-3 fw-bold">Progress to Degree Completion</h6>
                    
                    <div class="progress mb-3" style="height: 40px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= $progressPercent ?>%; background: linear-gradient(90deg, #006B3F, #16A34A);"
                             aria-valuenow="<?= $progressPercent ?>" aria-valuemin="0" aria-valuemax="100">
                            <span class="fw-bold text-white" style="font-size: 0.9rem;">
                                <?= $progressPercent ?>%
                            </span>
                        </div>
                    </div>

                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <small class="d-block text-muted">Credit Units Earned</small>
                            <div class="h5 mb-0"><?= $totalCUCompleted ?>/<?= $estimatedTotalCU ?></div>
                        </div>
                        <div class="col-6">
                            <small class="d-block text-muted">Remaining</small>
                            <div class="h5 mb-0" style="color: #EF4444;"><?= $cuRemaining ?> CU</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Info -->
        <div class="col-lg-4">
            <div class="progress-card card">
                <div class="card-body">
                    <h6 class="mb-3 fw-bold">Timeline</h6>
                    
                    <div class="mb-3 pb-3 border-bottom">
                        <small class="d-block text-muted">Program Duration</small>
                        <strong><?= $durationYears ?> years</strong>
                    </div>

                    <div class="mb-3 pb-3 border-bottom">
                        <small class="d-block text-muted">Est. Semesters Remaining</small>
                        <strong><?= $semestersRemaining ?> semesters</strong>
                    </div>

                    <div>
                        <small class="d-block text-muted">Completion Status</small>
                        <span class="badge bg-<?php 
                            if ($progressPercent >= 75) echo 'success';
                            elseif ($progressPercent >= 50) echo 'info';
                            else echo 'warning';
                        ?>" style="font-size: 0.9rem;">
                            <?php 
                            if ($progressPercent >= 100) echo '✓ Complete';
                            elseif ($progressPercent >= 75) echo 'On Track';
                            else echo 'In Progress';
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- By Level Breakdown -->
    <h5 class="mb-3"><i class="bi bi-bar-chart"></i> Courses by Level</h5>
    <div class="row g-3 mb-5">
        <?php for ($level = 100; $level <= 400; $level += 100): 
            $levelData = $coursesByLevel[$level] ?? null;
            $levelStatus = $levelData ? 'Completed' : 'Not Started';
            $levelColor = $levelData ? 'success' : 'secondary';
        ?>
        <div class="col-md-6 col-lg-3">
            <div class="progress-card card h-100">
                <div class="card-body">
                    <div class="level-badge mb-2"><?= $level ?> Level</div>
                    <div class="mt-3">
                        <small class="d-block text-muted mb-1">Courses Completed</small>
                        <div class="h5 mb-3"><?= $levelData['count'] ?? 0 ?></div>
                    </div>
                    <small class="d-block text-muted mb-1">Credit Units</small>
                    <div class="h5 mb-0"><?= $levelData['cu'] ?? 0 ?></div>
                </div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- Completed Courses -->
    <h5 class="mb-3"><i class="bi bi-check-circle"></i> Completed Courses</h5>
    <div class="progress-card card mb-5">
        <?php if (!empty($completedCourses)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Course Code</th>
                            <th>Course Title</th>
                            <th>Level</th>
                            <th>Credit Units</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedCourses as $course): ?>
                        <tr>
                            <td><code class="fw-bold text-primary"><?= h($course['code']) ?></code></td>
                            <td><?= h($course['title']) ?></td>
                            <td><span class="level-badge"><?= (int)$course['level'] ?></span></td>
                            <td><?= (int)$course['credit_units'] ?></td>
                            <td>
                                <span class="badge bg-success" style="font-size: 0.85rem;">
                                    <?= h($course['grade']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card-body">
                <p class="text-muted mb-0">No completed courses yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Failed Courses (if any) -->
    <?php if ($failedCount > 0): ?>
    <h5 class="mb-3"><i class="bi bi-exclamation-circle"></i> Courses Requiring Retake</h5>
    <div class="alert alert-warning mb-5">
        <strong>⚠ Attention:</strong> You have <?= $failedCount ?> course(s) that need to be retaken. These will be automatically registered in the next available semester.
    </div>
    
    <div class="progress-card card mb-5">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Credit Units</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $failedCourses->data_seek(0);
                        while ($failed = $failedCourses->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><code class="fw-bold"><?= h($failed['code']) ?></code></td>
                        <td><?= h($failed['title']) ?></td>
                        <td><?= (int)$failed['credit_units'] ?></td>
                        <td>
                            <span class="badge bg-danger">
                                Needs Retake (<?= h($failed['grade']) ?>)
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Requirements Checklist -->
    <h5 class="mb-3"><i class="bi bi-list-check"></i> Degree Requirements</h5>
    <div class="progress-card card mb-5">
        <div class="requirement-item">
            <span>Minimum Total Credit Units (<?= $estimatedTotalCU ?> CU)</span>
            <span class="requirement-status <?php echo $totalCUCompleted >= $estimatedTotalCU ? 'status-met' : 'status-pending'; ?>">
                <?= $totalCUCompleted ?>/<?= $estimatedTotalCU ?>
            </span>
        </div>
        <div class="requirement-item">
            <span>CGPA Requirement (2.0 minimum)</span>
            <?php 
                $cgpa = $conn->query(
                    "SELECT ROUND(SUM(r.grade_point * c.credit_units) / NULLIF(SUM(c.credit_units),0), 2) AS cgpa
                     FROM results r
                     JOIN result_batches rb ON rb.id = r.batch_id
                     JOIN courses c ON c.id = r.course_id
                     WHERE r.student_id = $studentId AND rb.status = 'published'"
                )->fetch_assoc();
                $cgpaValue = (float)($cgpa['cgpa'] ?? 0);
            ?>
            <span class="requirement-status <?php echo $cgpaValue >= 2.0 ? 'status-met' : 'status-pending'; ?>">
                <?= number_format($cgpaValue, 2) ?>
            </span>
        </div>
        <div class="requirement-item">
            <span>All Courses Passed (No F grades)</span>
            <span class="requirement-status <?php echo $failedCount == 0 ? 'status-met' : 'status-not-met'; ?>">
                <?php echo $failedCount == 0 ? '✓ Met' : $failedCount . ' Pending'; ?>
            </span>
        </div>
    </div>

    <!-- Action Buttons -->
    <div>
        <a href="transcript.php" class="btn btn-primary">
            <i class="bi bi-file-earmark"></i> View Complete Transcript
        </a>
        <a href="register_courses.php" class="btn btn-outline-primary">
            <i class="bi bi-journal-plus"></i> Register Courses
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
