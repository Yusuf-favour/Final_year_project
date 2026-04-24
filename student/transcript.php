<?php
/* ================================================================
   STUDENT – Complete Transcript & Academic History
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('student');

$userId = (int)$_SESSION['user_id'];

/* Get student info */
$student = $conn->query(
    "SELECT s.*, u.email, p.name AS program_name, p.degree_type,
            d.name AS dept_name
     FROM students s
     JOIN users u ON u.id = s.user_id
     JOIN programs p ON p.id = s.program_id
     JOIN departments d ON d.id = s.department_id
     WHERE s.user_id = $userId"
)->fetch_assoc();

if (!$student) {
    die('Student profile not found.');
}

$studentId = (int)$student['id'];

/* Get all published results */
$results = $conn->query(
    "SELECT r.*, c.code, c.title, c.credit_units, c.level,
            sem.semester_number, a.session_name
     FROM results r
     JOIN result_batches rb ON rb.id = r.batch_id
     JOIN courses c ON c.id = r.course_id
     JOIN semesters sem ON sem.id = r.semester_id
     JOIN academic_sessions a ON a.id = sem.session_id
     WHERE r.student_id = $studentId AND rb.status = 'published'
     ORDER BY a.session_name DESC, sem.semester_number DESC, c.code"
);

/* Calculate stats */
$totalQP = 0;
$totalCU = 0;
$failCount = 0;
$courseCount = 0;
$semesters = [];

while ($r = $results->fetch_assoc()) {
    $qp = (float)$r['grade_point'] * (int)$r['credit_units'];
    $totalQP += $qp;
    $totalCU += (int)$r['credit_units'];
    $courseCount++;

    if ($r['grade'] === 'F') {
        $failCount++;
    }

    $key = $r['session_name'] . ' Sem ' . $r['semester_number'];
    $semesters[$key][] = $r;
}

$cgpa = $totalCU > 0 ? round($totalQP / $totalCU, 2) : 0;
$standing = academicStanding($cgpa);

include __DIR__ . '/../includes/header.php';
?>

<style>
    .transcript-header {
        background: linear-gradient(135deg, #006B3F 0%, #004D2C 100%);
        color: white;
        padding: 3rem 0;
        margin-bottom: 2rem;
    }
    .stat-box {
        text-align: center;
        padding: 1.5rem;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #006B3F;
    }
    .stat-label {
        font-size: 0.85rem;
        color: #6B7280;
        text-transform: uppercase;
        font-weight: 600;
    }
    .semester-table {
        margin-bottom: 2rem;
    }
    .grade-a { background: #DCFCE7; color: #166534; }
    .grade-b { background: #DBEAFE; color: #0C4A6E; }
    .grade-c { background: #FEF3C7; color: #78350F; }
    .grade-f { background: #FEE2E2; color: #7F1D1D; }
    @media print {
        .no-print { display: none; }
        body { background: white; }
    }
</style>

<div class="transcript-header">
    <div class="container text-center">
        <h2 class="mb-2"><i class="bi bi-mortarboard"></i> Academic Transcript</h2>
        <p class="mb-0">Complete Academic Record</p>
    </div>
</div>

<div class="container pb-5">
    <!-- Student Information -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div>
                        <strong class="text-muted">Full Name</strong>
                        <p class="h6 mb-3"><?= h($student['full_name'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <strong class="text-muted">Program</strong>
                        <p class="h6 mb-3"><?= h($student['program_name']) ?> (<?= h($student['degree_type']) ?>)</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div>
                        <strong class="text-muted">Matric Number</strong>
                        <p class="h6 mb-3"><?= h($student['matric_no']) ?></p>
                    </div>
                    <div>
                        <strong class="text-muted">Department</strong>
                        <p class="h6 mb-3"><?= h($student['dept_name']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Statistics -->
    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-value"><?= number_format($cgpa, 2) ?></div>
                <div class="stat-label">CGPA</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-value" style="color: <?= $cgpa >= 2.0 ? '#16A34A' : '#EF4444' ?>">
                    <?= h($standing) ?>
                </div>
                <div class="stat-label">Standing</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-value"><?= $courseCount ?></div>
                <div class="stat-label">Courses Taken</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-value"><?= $totalCU ?></div>
                <div class="stat-label">Total CU Earned</div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Print Transcript
        </button>
        <a href="results.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Results
        </a>
    </div>

    <!-- Detailed Results by Semester -->
    <?php if (empty($semesters)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            No results published yet.
        </div>
    <?php else: ?>
        <?php foreach ($semesters as $semLabel => $courses): 
            $semQP = 0;
            $semCU = 0;
            foreach ($courses as $c) {
                $semQP += (float)$c['grade_point'] * (int)$c['credit_units'];
                $semCU += (int)$c['credit_units'];
            }
            $semGPA = $semCU > 0 ? round($semQP / $semCU, 2) : 0;
        ?>
        <div class="card shadow-sm border-0 semester-table">
            <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
                <span><?= h($semLabel) ?></span>
                <span style="color: #006B3F;">GPA: <?= number_format($semGPA, 2) ?></span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Course Title</th>
                            <th style="width: 80px;" class="text-center">CU</th>
                            <th style="width: 80px;" class="text-center">CA</th>
                            <th style="width: 80px;" class="text-center">Exam</th>
                            <th style="width: 80px;" class="text-center">Total</th>
                            <th style="width: 80px;" class="text-center">Grade</th>
                            <th style="width: 80px;" class="text-center">GP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): 
                            $gradeClass = 'grade-a';
                            if ($c['grade'] === 'B') $gradeClass = 'grade-b';
                            elseif ($c['grade'] === 'C') $gradeClass = 'grade-c';
                            elseif ($c['grade'] === 'F') $gradeClass = 'grade-f';
                        ?>
                        <tr>
                            <td><strong><?= h($c['code']) ?></strong></td>
                            <td><?= h($c['title']) ?></td>
                            <td class="text-center"><?= (int)$c['credit_units'] ?></td>
                            <td class="text-center"><?= number_format($c['ca_score'], 1) ?></td>
                            <td class="text-center"><?= number_format($c['exam_score'], 1) ?></td>
                            <td class="text-center fw-bold"><?= number_format($c['total_score'], 1) ?></td>
                            <td class="text-center">
                                <span class="badge <?= $gradeClass ?>" style="font-size: 0.9rem;">
                                    <?= h($c['grade']) ?>
                                </span>
                            </td>
                            <td class="text-center"><?= number_format($c['grade_point'], 1) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="2">Semester Total</td>
                            <td class="text-center"><?= $semCU ?></td>
                            <td colspan="3"></td>
                            <td colspan="2" class="text-center">
                                GPA: <span style="color: #006B3F;"><?= number_format($semGPA, 2) ?></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Final Summary -->
        <div class="card shadow-sm border-0 mt-4" style="background: linear-gradient(135deg, #E8F5EE 0%, #F0FDF4 100%);">
            <div class="card-body text-center p-4">
                <h5 class="mb-3">Academic Summary</h5>
                <div class="row g-4 text-center">
                    <div class="col-md-4">
                        <div class="h3 mb-1" style="color: #006B3F;"><?= number_format($cgpa, 2) ?></div>
                        <small class="text-muted">Cumulative GPA</small>
                    </div>
                    <div class="col-md-4">
                        <div class="h3 mb-1" style="color: <?= $standing === 'Excellent' || $standing === 'Very Good' ? '#16A34A' : '#EF4444' ?>">
                            <?= h($standing) ?>
                        </div>
                        <small class="text-muted">Academic Standing</small>
                    </div>
                    <div class="col-md-4">
                        <div class="h3 mb-1" style="color: #006B3F;"><?= $courseCount ?></div>
                        <small class="text-muted">Total Courses</small>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-5 pt-4 border-top text-center text-muted no-print">
        <small>
            <i class="bi bi-info-circle"></i>
            This is an official academic transcript generated from the Student Management System.
            Generated on <?= date('F d, Y \a\t g:i A') ?>
        </small>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
