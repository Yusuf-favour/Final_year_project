<?php
/* ================================================================
   STUDENT – Detailed Results View
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('student');

$pageTitle = 'My Results';
$userId = (int)$_SESSION['user_id'];

/* Fetch student */
$sq = $conn->prepare(
    "SELECT s.*, p.name AS program_name, p.code AS program_code,
            d.name AS dept_name
     FROM students s
     JOIN programs p ON p.id = s.program_id
     JOIN departments d ON d.id = s.department_id
     WHERE s.user_id = ?"
);
$sq->bind_param('i', $userId);
$sq->execute();
$student = $sq->get_result()->fetch_assoc();
if (!$student) {
    session_destroy();
    header('Location: ' . BASE_URL . '/swiftgrade_login.php?error=no_profile');
    exit();
}
$studentId = (int)$student['id'];

/* All published results grouped by semester */
$results = $conn->query(
    "SELECT r.*, c.code AS course_code, c.title AS course_title, c.credit_units,
            sem.semester_number, a.session_name, sem.id AS semester_id
     FROM results r
     JOIN result_batches rb ON rb.id = r.batch_id
     JOIN courses c ON c.id = r.course_id
     JOIN semesters sem ON sem.id = r.semester_id
     JOIN academic_sessions a ON a.id = sem.session_id
     WHERE r.student_id = $studentId
       AND rb.status = 'published'
     ORDER BY a.session_name, sem.semester_number, c.code"
);

/* Group by semester */
$semesters = [];
while ($r = $results->fetch_assoc()) {
    $key = $r['session_name'] . ' Sem ' . $r['semester_number'];
    $semesters[$key][] = $r;
}

/* CGPA */
$cgpaQ = $conn->query(
    "SELECT ROUND(SUM(r.grade_point * c.credit_units) / NULLIF(SUM(c.credit_units),0), 2) AS cgpa
     FROM results r
     JOIN result_batches rb ON rb.id = r.batch_id
     JOIN courses c ON c.id = r.course_id
     WHERE r.student_id = $studentId AND rb.status = 'published'"
)->fetch_assoc();
$cgpa = (float)($cgpaQ['cgpa'] ?? 0);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
<div class="container">
    <h2><i class="bi bi-file-earmark-bar-graph"></i> My Results</h2>
    <small><?= h(currentFullName()) ?> · <?= h($student['matric_no']) ?> · <?= h($student['program_name']) ?></small>
</div>
</div>

<div class="container mb-4">

<?php if (empty($semesters)): ?>
<div class="alert alert-info">No published results available yet.</div>
<?php else: ?>

<?php
$cumulativeQP = 0;
$cumulativeCU = 0;
foreach ($semesters as $label => $rows):
    $semQP = 0;
    $semCU = 0;
    foreach ($rows as $r) {
        $semQP += (float)$r['grade_point'] * (int)$r['credit_units'];
        $semCU += (int)$r['credit_units'];
    }
    $gpa = $semCU > 0 ? round($semQP / $semCU, 2) : 0;
    $cumulativeQP += $semQP;
    $cumulativeCU += $semCU;
    $runningCGPA = $cumulativeCU > 0 ? round($cumulativeQP / $cumulativeCU, 2) : 0;
?>

<div class="card shadow-sm mb-4">
<div class="card-header bg-lsc-green text-white d-flex justify-content-between">
    <strong><?= h($label) ?></strong>
    <span>GPA: <strong><?= number_format($gpa, 2) ?></strong></span>
</div>
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>Code</th><th>Course Title</th><th>CU</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>GP</th><th>Remark</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr class="<?= $r['grade']==='F' ? 'table-danger' : '' ?>">
    <td><strong><?= h($r['course_code']) ?></strong></td>
    <td><?= h($r['course_title']) ?></td>
    <td><?= $r['credit_units'] ?></td>
    <td><?= $r['ca_score'] ?></td>
    <td><?= $r['exam_score'] ?></td>
    <td class="fw-bold"><?= $r['total_score'] ?></td>
    <td><strong><?= $r['grade'] ?></strong></td>
    <td><?= $r['grade_point'] ?></td>
    <td><small><?= h($r['remark']) ?></small></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr class="table-light">
    <td colspan="2" class="text-end"><strong>Semester Total</strong></td>
    <td><strong><?= $semCU ?></strong></td>
    <td colspan="4"></td>
    <td colspan="2">
        GPA: <strong class="text-lsc-green"><?= number_format($gpa, 2) ?></strong> ·
        CGPA: <strong><?= number_format($runningCGPA, 2) ?></strong>
    </td>
</tr>
</tfoot>
</table>
</div>
</div>

<?php endforeach; ?>

<!-- Overall Summary -->
<div class="card shadow-sm p-4">
    <div class="row text-center">
        <div class="col-md-3">
            <h3 class="text-lsc-green"><?= number_format($cgpa, 2) ?></h3>
            <p class="text-muted">Cumulative GPA</p>
        </div>
        <div class="col-md-3">
            <h3 class="text-lsc-green"><?= h(academicStanding($cgpa)) ?></h3>
            <p class="text-muted">Academic Standing</p>
        </div>
        <div class="col-md-3">
            <h3><?= $cumulativeCU ?></h3>
            <p class="text-muted">Total Credit Units</p>
        </div>
        <div class="col-md-3">
            <h3><?= count($semesters) ?></h3>
            <p class="text-muted">Semesters</p>
        </div>
    </div>
</div>

<?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
