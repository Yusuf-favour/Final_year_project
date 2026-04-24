<?php
/* ================================================================
   STUDENT – Academic Standing & Progress
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('student');

$userId = (int)$_SESSION['user_id'];

/* Get student */
$student = $conn->query(
    "SELECT s.*, p.name AS program_name, d.name AS dept_name
     FROM students s
     JOIN programs p ON p.id = s.program_id
     JOIN departments d ON d.id = s.department_id
     WHERE s.user_id = $userId"
)->fetch_assoc();

if (!$student) {
    die('Profile not found.');
}

$studentId = (int)$student['id'];

/* Get all results */
$results = $conn->query(
    "SELECT r.*, c.credit_units, c.level, sem.semester_number, a.session_name
     FROM results r
     JOIN result_batches rb ON rb.id = r.batch_id
     JOIN courses c ON c.id = r.course_id
     JOIN semesters sem ON sem.id = r.semester_id
     JOIN academic_sessions a ON a.id = sem.session_id
     WHERE r.student_id = $studentId AND rb.status = 'published'
     ORDER BY a.session_name DESC, sem.semester_number DESC"
);

/* Calculate overall stats */
$totalQP = 0;
$totalCU = 0;
$courseCount = 0;
$failCount = 0;
$passCount = 0;
$semesterStats = [];

while ($r = $results->fetch_assoc()) {
    $qp = (float)$r['grade_point'] * (int)$r['credit_units'];
    $totalQP += $qp;
    $totalCU += (int)$r['credit_units'];
    $courseCount++;

    if ($r['grade'] === 'F') {
        $failCount++;
    } else {
        $passCount++;
    }

    $key = $r['session_name'] . ' S' . $r['semester_number'];
    if (!isset($semesterStats[$key])) {
        $semesterStats[$key] = [
            'qp' => 0,
            'cu' => 0,
            'courses' => 0,
            'fails' => 0
        ];
    }
    $semesterStats[$key]['qp'] += $qp;
    $semesterStats[$key]['cu'] += (int)$r['credit_units'];
    $semesterStats[$key]['courses']++;
    if ($r['grade'] === 'F') {
        $semesterStats[$key]['fails']++;
    }
}

$cgpa = $totalCU > 0 ? round($totalQP / $totalCU, 2) : 0;
$standing = academicStanding($cgpa);

/* Determine status */
$status = 'Active';
$statusColor = 'success';
$statusIcon = 'bi-check-circle-fill';

if ($cgpa < 1.0) {
    $status = 'On Probation';
    $statusColor = 'danger';
    $statusIcon = 'bi-exclamation-circle-fill';
} elseif ($cgpa < 2.0) {
    $status = 'Warning';
    $statusColor = 'warning';
    $statusIcon = 'bi-exclamation-triangle-fill';
}

$passRate = $courseCount > 0 ? round(($passCount / $courseCount) * 100, 1) : 0;

include __DIR__ . '/../includes/header.php';
?>

<style>
    .standing-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .standing-badge {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        border-radius: 25px;
        font-weight: 600;
        font-size: 1rem;
    }
    .progress-chart {
        height: 300px;
    }
</style>

<div class="page-header">
    <div class="container">
        <h2><i class="bi bi-graph-up"></i> Academic Standing</h2>
        <small>Your Academic Progress & Performance</small>
    </div>
</div>

<div class="container pb-5">
    <!-- Current Status -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="standing-card card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h5 class="mb-2">Current Academic Status</h5>
                            <div class="standing-badge bg-<?= $statusColor ?>-light text-<?= $statusColor ?>">
                                <i class="bi <?= $statusIcon ?>"></i> <?= h($status) ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="display-5" style="color: #006B3F;"><?= number_format($cgpa, 2) ?></div>
                            <small class="text-muted">Cumulative GPA</small>
                        </div>
                    </div>
                    
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?= min(100, $cgpa * 20) ?>%; background: #006B3F;"
                             aria-valuenow="<?= $cgpa ?>" aria-valuemin="0" aria-valuemax="5">
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-3">
                        <div class="col-6">
                            <small class="text-muted">Scale</small>
                            <p class="mb-0">0.0 ────── 5.0</p>
                        </div>
                        <div class="col-6 text-end">
                            <small class="text-muted">Classification</small>
                            <p class="mb-0 fw-bold" style="color: #006B3F;">
                                <?php 
                                if ($cgpa >= 4.50) echo '1st Class Honours';
                                elseif ($cgpa >= 3.50) echo '2nd Class Upper';
                                elseif ($cgpa >= 2.40) echo '2nd Class Lower';
                                elseif ($cgpa >= 1.50) echo '3rd Class';
                                elseif ($cgpa >= 1.00) echo 'Pass';
                                else echo 'Below Pass';
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="standing-card card">
                <div class="card-body p-4">
                    <h6 class="mb-3 fw-bold"><i class="bi bi-lightning"></i> Key Metrics</h6>
                    
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Courses Passed</span>
                            <strong style="color: #16A34A;"><?= $passCount ?>/<?= $courseCount ?></strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?= $courseCount > 0 ? ($passCount / $courseCount * 100) : 0 ?>%"></div>
                        </div>
                    </div>

                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Pass Rate</span>
                            <strong><?= $passRate ?>%</strong>
                        </div>
                    </div>

                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Failed Courses</span>
                            <strong style="color: #EF4444;"><?= $failCount ?></strong>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total CU Earned</span>
                            <strong><?= $totalCU ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Semester Breakdown -->
    <h5 class="mb-3"><i class="bi bi-calendar3"></i> Semester Performance</h5>
    <div class="row g-3">
        <?php foreach ($semesterStats as $semLabel => $stats): 
            $semGPA = $stats['cu'] > 0 ? round($stats['qp'] / $stats['cu'], 2) : 0;
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="standing-card card h-100">
                <div class="card-header bg-light fw-bold">
                    <?= h($semLabel) ?>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="h4 mb-1" style="color: #006B3F;">
                            <?= number_format($semGPA, 2) ?>
                        </div>
                        <small class="text-muted">GPA</small>
                    </div>

                    <div class="mb-2 pb-2 border-bottom">
                        <small class="text-muted">Courses:</small>
                        <strong><?= $stats['courses'] ?></strong>
                    </div>

                    <div class="mb-2 pb-2 border-bottom">
                        <small class="text-muted">Credit Units:</small>
                        <strong><?= $stats['cu'] ?></strong>
                    </div>

                    <div>
                        <small class="text-muted">Failed:</small>
                        <?php if ($stats['fails'] > 0): ?>
                            <span class="badge bg-danger"><?= $stats['fails'] ?></span>
                        <?php else: ?>
                            <span class="badge bg-success">0</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Recommendations -->
    <div class="alert alert-info mt-5">
        <h6 class="mb-2"><i class="bi bi-lightbulb"></i> Recommendations</h6>
        <ul class="mb-0">
            <?php if ($cgpa < 2.0): ?>
                <li>Your CGPA is below 2.0. Focus on improving your grades in upcoming courses.</li>
                <li>Consider seeking academic support or tutoring services.</li>
            <?php endif; ?>
            
            <?php if ($failCount > 0): ?>
                <li>You have <?= $failCount ?> failed course(s). These courses should be retaken next semester.</li>
                <li>Retaken courses will be automatically added to your schedule when available.</li>
            <?php endif; ?>
            
            <?php if ($passRate >= 90): ?>
                <li>Excellent performance! Keep up the great work.</li>
            <?php elseif ($passRate >= 80): ?>
                <li>Good performance. Continue maintaining your current study habits.</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Quick Links -->
    <div class="row g-3 mt-4">
        <div class="col-md-4">
            <a href="results.php" class="btn btn-outline-primary w-100">
                <i class="bi bi-file-earmark-bar-graph"></i> View Results
            </a>
        </div>
        <div class="col-md-4">
            <a href="transcript.php" class="btn btn-outline-primary w-100">
                <i class="bi bi-book"></i> Download Transcript
            </a>
        </div>
        <div class="col-md-4">
            <a href="index.php" class="btn btn-outline-primary w-100">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
