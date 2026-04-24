<?php
/* ================================================================
   LECTURER – Upload / Edit Marks + Submit Batch
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole('lecturer','hod');

$pageTitle = 'Upload Marks';
$msg = ''; $msgType = '';

$courseId = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$sem     = currentSemester($conn);
$semId   = $sem ? (int)$sem['id'] : 0;

if (!$courseId || !$semId) {
    include __DIR__ . '/../includes/header.php';
    echo '<div class="container mt-5"><div class="alert alert-danger">Invalid course or semester.<br><a href="index.php" class="btn btn-primary mt-2">Back to Dashboard</a></div></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

/* Verify this lecturer is assigned */
$chk = $conn->prepare(
    "SELECT ca.id FROM course_assignments ca
     WHERE ca.course_id = ? AND ca.lecturer_id = ? AND ca.semester_id = ?"
);
$chk->bind_param('iii', $courseId, $_SESSION['user_id'], $semId);
$chk->execute();
if ($chk->get_result()->num_rows === 0) {
    include __DIR__ . '/../includes/header.php';
    echo '<div class="container mt-5"><div class="alert alert-danger">You are not assigned to this course.<br><a href="index.php" class="btn btn-primary mt-2">Back to Dashboard</a></div></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

/* Fetch course info */
$crs = $conn->prepare("SELECT c.*, d.name AS dept_name FROM courses c JOIN departments d ON d.id=c.department_id WHERE c.id=?");
$crs->bind_param('i', $courseId);
$crs->execute();
$course = $crs->get_result()->fetch_assoc();

/* Get or create result batch for the current lecturer */
$bq = $conn->prepare("SELECT * FROM result_batches WHERE course_id=? AND semester_id=? AND lecturer_id=?");
$bq->bind_param('iii', $courseId, $semId, $_SESSION['user_id']);
$bq->execute();
$batch = $bq->get_result()->fetch_assoc();

if (!$batch) {
    $ins = $conn->prepare(
        "INSERT INTO result_batches (course_id, semester_id, lecturer_id, status) VALUES (?,?,?,'draft')"
    );
    $ins->bind_param('iii', $courseId, $semId, $_SESSION['user_id']);
    $ins->execute();
    $batchId = $conn->insert_id;
    logAudit($conn, 'CREATE_BATCH', 'result_batch', $batchId, $course['code']);
    $batch = ['id' => $batchId, 'status' => 'draft'];
} else {
    $batchId = (int)$batch['id'];
}

$canEdit = in_array($batch['status'], ['draft', 'rejected']);

/* ---------- HANDLE POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_marks' && $canEdit) {
        $studentIds = $_POST['student_id'] ?? [];
        $caScores   = $_POST['ca_score'] ?? [];
        $examScores = $_POST['exam_score'] ?? [];

        foreach ($studentIds as $i => $sid) {
            $sid  = (int)$sid;
            $ca   = max(0, min(40, (float)($caScores[$i] ?? 0)));
            $exam = max(0, min(60, (float)($examScores[$i] ?? 0)));
            $total = round($ca + $exam, 2);
            $gradeData = computeGrade($total, $conn);

            $r = $conn->prepare(
                "INSERT INTO results (batch_id, student_id, course_id, semester_id, ca_score, exam_score, total_score, grade, grade_point, remark, entered_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE ca_score=VALUES(ca_score), exam_score=VALUES(exam_score),
                    total_score=VALUES(total_score), grade=VALUES(grade), grade_point=VALUES(grade_point),
                    remark=VALUES(remark), entered_by=VALUES(entered_by), updated_at=NOW()"
            );
            $r->bind_param('iiiiddssdsi',
                $batchId, $sid, $courseId, $semId,
                $ca, $exam, $total,
                $gradeData['grade'], $gradeData['grade_point'], $gradeData['remark'],
                $_SESSION['user_id']
            );
            $r->execute();
        }

        logAudit($conn, 'SAVE_MARKS', 'result_batch', $batchId, count($studentIds) . ' students');
        $msg = 'Marks saved successfully.';
        $msgType = 'success';

    } elseif ($action === 'submit' && $canEdit) {
        /* Check at least one result exists */
        $rc = $conn->query("SELECT COUNT(*) AS c FROM results WHERE batch_id = $batchId")->fetch_assoc()['c'];
        if ($rc > 0) {
            $conn->query("UPDATE result_batches SET status='submitted', submitted_at=NOW(), rejection_reason=NULL WHERE id=$batchId");
            logAudit($conn, 'SUBMIT_BATCH', 'result_batch', $batchId, 'Submitted for HOD review');
            $msg = 'Results submitted for HOD approval!';
            $msgType = 'success';
            $batch['status'] = 'submitted';
            $canEdit = false;
        } else {
            $msg = 'Enter marks before submitting.';
            $msgType = 'warning';
        }
    }
}

/* ---------- FETCH REGISTERED STUDENTS + EXISTING MARKS ---------- */
$students = $conn->query(
    "SELECT s.id AS student_id, s.matric_no, u.full_name,
            r.ca_score, r.exam_score, r.total_score, r.grade, r.grade_point, r.remark
     FROM course_registrations cr
     JOIN students s ON s.id = cr.student_id
     JOIN users u ON u.id = s.user_id
     LEFT JOIN results r ON r.student_id = s.id AND r.course_id = $courseId AND r.semester_id = $semId
     WHERE cr.course_id = $courseId AND cr.semester_id = $semId
     ORDER BY s.matric_no"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
<div class="container">
    <h2><i class="bi bi-upload"></i> <?= h($course['code']) ?> – Upload Marks</h2>
    <small><?= h($course['title']) ?> · <?= h($sem['session_name']) ?> Semester <?= $sem['semester_number'] ?></small>
</div>
</div>

<div class="container mb-4">

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show"><?= h($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if (!empty($batch['rejection_reason'])): ?>
<div class="alert alert-danger">
    <strong><i class="bi bi-x-circle"></i> Rejected by HOD:</strong> <?= h($batch['rejection_reason']) ?>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <span class="me-2">Status: <?= statusBadge($batch['status']) ?></span>
        <span class="badge bg-secondary"><?= $students->num_rows ?> students registered</span>
    </div>
    <?php if ($canEdit && $students->num_rows > 0): ?>
    <form method="POST" onsubmit="return confirm('Submit these results to HOD for approval?')">
        <?= csrfField() ?>
        <input type="hidden" name="course_id" value="<?= $courseId ?>">
        <input type="hidden" name="action" value="submit">
        <button class="btn btn-gold"><i class="bi bi-send"></i> Submit to HOD</button>
    </form>
    <?php endif; ?>
</div>

<?php if ($students->num_rows === 0): ?>
<div class="alert alert-info">No students have registered for this course yet.</div>
<?php else: ?>

<form method="POST">
<?= csrfField() ?>
<input type="hidden" name="course_id" value="<?= $courseId ?>">
<input type="hidden" name="action" value="save_marks">

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead>
<tr>
    <th>#</th><th>Matric No</th><th>Student Name</th>
    <th style="width:100px">CA (0-40)</th>
    <th style="width:100px">Exam (0-60)</th>
    <th>Total</th><th>Grade</th><th>GP</th><th>Remark</th>
</tr>
</thead>
<tbody>
<?php $n=0; while ($s = $students->fetch_assoc()): $n++; ?>
<tr>
    <td><?= $n ?></td>
    <td><strong><?= h($s['matric_no']) ?></strong></td>
    <td><?= h($s['full_name']) ?></td>
    <td>
        <input type="hidden" name="student_id[]" value="<?= $s['student_id'] ?>">
        <input type="number" name="ca_score[]" class="form-control form-control-sm ca-input"
               value="<?= $s['ca_score'] ?? '' ?>" min="0" max="40" step="0.5"
               <?= $canEdit ? '' : 'readonly' ?>>
    </td>
    <td>
        <input type="number" name="exam_score[]" class="form-control form-control-sm exam-input"
               value="<?= $s['exam_score'] ?? '' ?>" min="0" max="60" step="0.5"
               <?= $canEdit ? '' : 'readonly' ?>>
    </td>
    <td class="total-cell fw-bold"><?= $s['total_score'] ?? '—' ?></td>
    <td><?= $s['grade'] ?? '—' ?></td>
    <td><?= $s['grade_point'] ?? '—' ?></td>
    <td><small><?= h($s['remark'] ?? '') ?></small></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<?php if ($canEdit): ?>
<div class="mt-3 text-end">
    <button type="submit" class="btn btn-lsc btn-lg">
        <i class="bi bi-save"></i> Save All Marks
    </button>
</div>
<?php endif; ?>
</form>
<?php endif; ?>
</div>

<?php
$extraJS = <<<'JS'
<script>
document.querySelectorAll('.ca-input, .exam-input').forEach(input => {
    input.addEventListener('input', function() {
        const row = this.closest('tr');
        const ca = parseFloat(row.querySelector('.ca-input').value) || 0;
        const exam = parseFloat(row.querySelector('.exam-input').value) || 0;
        row.querySelector('.total-cell').textContent = (ca + exam).toFixed(1);
    });
});
</script>
JS;
include __DIR__ . '/../includes/footer.php';
?>
