<?php
/* ================================================================
   ADMIN – Publish Results  (HOD-approved → Published)
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole('admin');
$pageTitle = 'Publish Results';
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    $batchId = (int)$_POST['batch_id'];

    $stmt = $conn->prepare(
        "UPDATE result_batches
         SET status = 'published', published_by = ?, published_at = NOW()
         WHERE id = ? AND status = 'hod_approved'"
    );
    $stmt->bind_param('ii', $_SESSION['user_id'], $batchId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        logAudit($conn, 'PUBLISH_RESULTS', 'result_batch', $batchId, 'Results published');
        $moved = carryOverFailedCoursesForBatch($conn, $batchId);
        $msg = 'Results published successfully! Students can now view them.';
        if ($moved > 0) {
            $msg .= ' ' . $moved . ' failed course registration(s) were moved to the next semester automatically.';
        }
        $msgType = 'success';
    } else {
        $msg = 'No batch updated — it may not be in HOD-approved status.';
        $msgType = 'warning';
    }
}

/* Fetch approved batches ready for publishing */
$batches = $conn->query(
    "SELECT rb.*, c.code AS course_code, c.title AS course_title,
            u.full_name AS lecturer_name,
            h.full_name AS hod_name,
            a.session_name,
            s.semester_number,
            (SELECT COUNT(*) FROM results WHERE batch_id = rb.id) AS result_count
     FROM result_batches rb
     JOIN courses c ON c.id = rb.course_id
     JOIN users u ON u.id = rb.lecturer_id
     LEFT JOIN users h ON h.id = rb.hod_id
     JOIN semesters s ON s.id = rb.semester_id
     JOIN academic_sessions a ON a.id = s.session_id
     WHERE rb.status = 'hod_approved'
     ORDER BY rb.hod_approved_at DESC"
);

/* Also show recently published */
$published = $conn->query(
    "SELECT rb.*, c.code AS course_code, c.title AS course_title,
            p.full_name AS publisher_name, rb.published_at
     FROM result_batches rb
     JOIN courses c ON c.id = rb.course_id
     LEFT JOIN users p ON p.id = rb.published_by
     WHERE rb.status = 'published'
     ORDER BY rb.published_at DESC LIMIT 20"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
<div class="container">
    <h2><i class="bi bi-check2-circle"></i> Publish Results</h2>
    <small>Final step: make HOD-approved results visible to students</small>
</div>
</div>

<div class="container mb-4">
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= h($msg) ?></div><?php endif; ?>

<h5 class="text-lsc-green mb-3"><i class="bi bi-hourglass-split"></i> Awaiting Publication</h5>

<?php if ($batches->num_rows === 0): ?>
<div class="alert alert-info">No batches awaiting publication.</div>
<?php else: ?>
<div class="row g-3 mb-4">
<?php while ($b = $batches->fetch_assoc()): ?>
<div class="col-md-6">
    <div class="card shadow-sm">
        <div class="card-body">
            <h6><strong><?= h($b['course_code']) ?></strong> – <?= h($b['course_title']) ?></h6>
            <p class="text-muted mb-1">
                <?= h($b['session_name']) ?> · Semester <?= $b['semester_number'] ?>
            </p>
            <p class="mb-1">
                <i class="bi bi-person"></i> Lecturer: <?= h($b['lecturer_name']) ?><br>
                <i class="bi bi-shield-check"></i> Approved by: <?= h($b['hod_name'] ?? '—') ?>
                <small class="text-muted">(<?= h($b['hod_approved_at']) ?>)</small>
            </p>
            <p class="mb-2">
                <span class="badge bg-info"><?= $b['result_count'] ?> results</span>
                <?= statusBadge($b['status']) ?>
            </p>
            <form method="POST" onsubmit="return confirm('Publish these results? Students will be able to view them.')">
                <?= csrfField() ?>
                <input type="hidden" name="batch_id" value="<?= $b['id'] ?>">
                <button class="btn btn-lsc btn-sm"><i class="bi bi-megaphone"></i> Publish Now</button>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>
<?php endif; ?>

<hr>
<h5 class="text-muted mb-3"><i class="bi bi-check-circle"></i> Recently Published</h5>
<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0">
<thead><tr><th>Course</th><th>Published By</th><th>Published At</th><th>Status</th></tr></thead>
<tbody>
<?php while ($p = $published->fetch_assoc()): ?>
<tr>
    <td><strong><?= h($p['course_code']) ?></strong> – <?= h($p['course_title']) ?></td>
    <td><?= h($p['publisher_name'] ?? '—') ?></td>
    <td><?= h($p['published_at']) ?></td>
    <td><?= statusBadge($p['status']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
