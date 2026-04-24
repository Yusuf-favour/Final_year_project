<?php
/* ================================================================
   ADMIN – Academic Sessions
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole('admin');
$pageTitle = 'Academic Sessions';
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    $act = $_POST['action'] ?? '';

    if ($act === 'create_session') {
        $name = trim($_POST['session_name']);
        $stmt = $conn->prepare("INSERT INTO academic_sessions (session_name) VALUES (?)");
        $stmt->bind_param('s', $name);
        if ($stmt->execute()) {
            $sid = $conn->insert_id;
            $conn->query("INSERT INTO semesters (session_id, semester_number) VALUES ($sid, 1), ($sid, 2)");
            logAudit($conn, 'CREATE_SESSION', 'session', $sid, $name);
            $msg = "Session '$name' created with 2 semesters."; $msgType = 'success';
        } else {
            $msg = 'Error: ' . $conn->error; $msgType = 'danger';
        }

    } elseif ($act === 'set_current') {
        $semId = (int)$_POST['semester_id'];
        $conn->query("UPDATE academic_sessions SET is_current = 0");
        $conn->query("UPDATE semesters SET is_current = 0");
        $conn->query("UPDATE semesters SET is_current = 1 WHERE id = $semId");
        $conn->query(
            "UPDATE academic_sessions SET is_current = 1
             WHERE id = (SELECT session_id FROM semesters WHERE id = $semId)"
        );
        logAudit($conn, 'SET_CURRENT_SEMESTER', 'semester', $semId, '');
        $msg = 'Current semester updated.'; $msgType = 'success';
    }
}

$sessions = $conn->query(
    "SELECT a.*, s.id AS sem_id, s.semester_number, s.is_current AS sem_current
     FROM academic_sessions a
     JOIN semesters s ON s.session_id = a.id
     ORDER BY a.session_name DESC, s.semester_number"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
<div class="container">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-calendar3"></i> Academic Sessions</h2>
        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> New Session</button>
    </div>
</div>
</div>

<div class="container mb-4">
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= h($msg) ?></div><?php endif; ?>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>Session</th><th>Semester</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php while ($s = $sessions->fetch_assoc()): ?>
<tr class="<?= $s['sem_current'] ? 'table-success' : '' ?>">
    <td><strong><?= h($s['session_name']) ?></strong></td>
    <td>Semester <?= $s['semester_number'] ?></td>
    <td>
        <?php if ($s['sem_current']): ?>
            <span class="badge bg-success">Current</span>
        <?php else: ?>
            <span class="badge bg-secondary">—</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if (!$s['sem_current']): ?>
        <form method="POST" class="d-inline" onsubmit="return confirm('Set this as current semester?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="set_current">
            <input type="hidden" name="semester_id" value="<?= $s['sem_id'] ?>">
            <button class="btn btn-sm btn-outline-success">Set Current</button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Add Session Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
<div class="modal-dialog modal-sm">
<div class="modal-content">
<form method="POST">
<?= csrfField() ?>
<input type="hidden" name="action" value="create_session">
<div class="modal-header bg-lsc-green text-white">
    <h5 class="modal-title">New Session</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <label class="form-label">Session Name</label>
    <input type="text" name="session_name" class="form-control" required placeholder="e.g. 2026/2027">
</div>
<div class="modal-footer">
    <button type="submit" class="btn btn-lsc">Create</button>
</div>
</form>
</div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
