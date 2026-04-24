<?php
/* ================================================================
   HOD – Review & Approve / Reject Result Batch
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole('hod');

$pageTitle = 'Review Results';
$msg = ''; $msgType = '';

$batchId = (int)($_GET['batch_id'] ?? $_POST['batch_id'] ?? 0);
$deptId  = (int)($_SESSION['department_id'] ?? 0);

/* ---------- HANDLE APPROVE / REJECT ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $batchId) {
    verifyCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $stmt = $conn->prepare(
            "UPDATE result_batches
             SET status='hod_approved', hod_id=?, hod_approved_at=NOW()
             WHERE id=? AND status='submitted'"
        );
        $stmt->bind_param('ii', $_SESSION['user_id'], $batchId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            logAudit($conn, 'HOD_APPROVE', 'result_batch', $batchId, 'Approved by HOD');
            $msg = 'Results approved! Forwarded to Registry for publication.';
            $msgType = 'success';
        }

    } elseif ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        $stmt = $conn->prepare(
            "UPDATE result_batches
             SET status='rejected', hod_id=?, rejection_reason=?
             WHERE id=? AND status='submitted'"
        );
        $stmt->bind_param('isi', $_SESSION['user_id'], $reason, $batchId);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            logAudit($conn, 'HOD_REJECT', 'result_batch', $batchId, "Reason: $reason");
            $msg = 'Results rejected and returned to the lecturer.';
            $msgType = 'warning';
        }
    }
}

/* ---------- If no batch selected, show list ---------- */
if (!$batchId) {
    $batches = $conn->query(
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

    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="page-header"><div class="container"><h2><i class="bi bi-clipboard-check"></i> Review Results</h2></div></div>
    <div class="container mb-4">
    <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= h($msg) ?></div><?php endif; ?>
    <?php if ($batches->num_rows === 0): ?>
        <div class="alert alert-info">No batches awaiting review.</div>
    <?php else: ?>
        <div class="list-group">
        <?php while ($b = $batches->fetch_assoc()): ?>
            <a href="review.php?batch_id=<?= $b['id'] ?>" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between">
                    <h6 class="mb-1"><strong><?= h($b['course_code']) ?></strong> – <?= h($b['course_title']) ?></h6>
                    <span class="badge bg-primary"><?= $b['result_count'] ?> results</span>
                </div>
                <small class="text-muted">By <?= h($b['lecturer_name']) ?> · <?= h($b['submitted_at']) ?></small>
            </a>
        <?php endwhile; ?>
        </div>
    <?php endif; ?>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit();
}

/* ---------- Single batch review ---------- */
$bq = $conn->prepare(
    "SELECT rb.*, c.code AS course_code, c.title AS course_title, c.credit_units,
            u.full_name AS lecturer_name,
            a.session_name, sem.semester_number
     FROM result_batches rb
     JOIN courses c ON c.id = rb.course_id
     JOIN users u ON u.id = rb.lecturer_id
     JOIN semesters sem ON sem.id = rb.semester_id
     JOIN academic_sessions a ON a.id = sem.session_id
     WHERE rb.id = ? AND c.department_id = ?"
);
$bq->bind_param('ii', $batchId, $deptId);
$bq->execute();
$batch = $bq->get_result()->fetch_assoc();

if (!$batch) { die('Batch not found or not in your department.'); }

/* Fetch results */
$results = $conn->query(
    "SELECT r.*, s.matric_no, u.full_name
     FROM results r
     JOIN students s ON s.id = r.student_id
     JOIN users u ON u.id = s.user_id
     WHERE r.batch_id = $batchId
     ORDER BY s.matric_no"
);

/* Stats */
$statsQ = $conn->query(
    "SELECT COUNT(*) AS total,
            ROUND(AVG(total_score),1) AS avg_score,
            SUM(CASE WHEN grade='F' THEN 1 ELSE 0 END) AS fail_count,
            MAX(total_score) AS max_score,
            MIN(total_score) AS min_score
     FROM results WHERE batch_id = $batchId"
)->fetch_assoc();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
<div class="container">
    <h2><i class="bi bi-clipboard-check"></i> Review: <?= h($batch['course_code']) ?></h2>
    <small><?= h($batch['course_title']) ?> · <?= h($batch['session_name']) ?> Semester <?= $batch['semester_number'] ?></small>
</div>
</div>

<div class="container mb-4">

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= h($msg) ?></div>
<?php endif; ?>

<!-- Summary -->
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card p-3 text-center"><div class="fw-bold text-lsc-green fs-4"><?= $statsQ['total'] ?></div><small>Students</small></div></div>
    <div class="col-md-3"><div class="card p-3 text-center"><div class="fw-bold text-lsc-green fs-4"><?= $statsQ['avg_score'] ?></div><small>Average Score</small></div></div>
    <div class="col-md-3"><div class="card p-3 text-center"><div class="fw-bold text-danger fs-4"><?= $statsQ['fail_count'] ?></div><small>Failures</small></div></div>
    <div class="col-md-3"><div class="card p-3 text-center"><div class="fw-bold fs-4"><?= $statsQ['min_score'] ?>–<?= $statsQ['max_score'] ?></div><small>Score Range</small></div></div>
</div>

<p class="text-muted">Submitted by: <strong><?= h($batch['lecturer_name']) ?></strong> on <?= h($batch['submitted_at']) ?></p>

<div class="card shadow-sm mb-3">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>#</th><th>Matric No</th><th>Student</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>GP</th><th>Remark</th></tr></thead>
<tbody>
<?php $n=0; while ($r = $results->fetch_assoc()): $n++; ?>
<tr class="<?= $r['grade']==='F' ? 'table-danger' : '' ?>">
    <td><?= $n ?></td>
    <td><strong><?= h($r['matric_no']) ?></strong></td>
    <td><?= h($r['full_name']) ?></td>
    <td><?= $r['ca_score'] ?></td>
    <td><?= $r['exam_score'] ?></td>
    <td class="fw-bold"><?= $r['total_score'] ?></td>
    <td><strong><?= $r['grade'] ?></strong></td>
    <td><?= $r['grade_point'] ?></td>
    <td><small><?= h($r['remark']) ?></small></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<?php if ($batch['status'] === 'submitted'): ?>
<!-- Approve / Reject buttons -->
<div class="d-flex gap-3">
    <form method="POST" onsubmit="return confirm('Approve these results?')">
        <?= csrfField() ?>
        <input type="hidden" name="batch_id" value="<?= $batchId ?>">
        <input type="hidden" name="action" value="approve">
        <button class="btn btn-success btn-lg"><i class="bi bi-check-circle"></i> Approve & Forward</button>
    </form>

    <button class="btn btn-outline-danger btn-lg" data-bs-toggle="modal" data-bs-target="#rejectModal">
        <i class="bi bi-x-circle"></i> Reject
    </button>
</div>

<!-- Reject modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST">
<?= csrfField() ?>
<input type="hidden" name="batch_id" value="<?= $batchId ?>">
<input type="hidden" name="action" value="reject">
<div class="modal-header bg-danger text-white">
    <h5 class="modal-title">Reject Results</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <label class="form-label">Reason for Rejection</label>
    <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Explain what needs to be corrected..."></textarea>
</div>
<div class="modal-footer">
    <button type="submit" class="btn btn-danger">Reject & Return to Lecturer</button>
</div>
</form>
</div>
</div>
</div>
<?php else: ?>
<div class="alert alert-<?= $batch['status']==='hod_approved' ? 'success' : 'secondary' ?>">
    Status: <?= statusBadge($batch['status']) ?>
</div>
<?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
