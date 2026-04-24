<?php
/* ================================================================
   ADMIN – Manage Departments
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole('admin');
$pageTitle = 'Departments';
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    $act = $_POST['action'] ?? '';

    if ($act === 'create') {
        $code = strtoupper(trim($_POST['code']));
        $name = trim($_POST['name']);
        $stmt = $conn->prepare("INSERT INTO departments (code, name) VALUES (?, ?)");
        $stmt->bind_param('ss', $code, $name);
        if ($stmt->execute()) {
            logAudit($conn, 'CREATE_DEPARTMENT', 'department', $conn->insert_id, $code);
            $msg = "Department '$code' created."; $msgType = 'success';
        } else {
            $msg = 'Error: ' . $conn->error; $msgType = 'danger';
        }
    } elseif ($act === 'delete') {
        $id = (int)$_POST['dept_id'];
        $conn->query("DELETE FROM departments WHERE id = $id");
        logAudit($conn, 'DELETE_DEPARTMENT', 'department', $id, '');
        $msg = 'Department deleted.'; $msgType = 'info';
    }
}

$depts = $conn->query(
    "SELECT d.*, (SELECT COUNT(*) FROM students WHERE department_id=d.id) AS student_count,
            (SELECT COUNT(*) FROM programs WHERE department_id=d.id) AS program_count
     FROM departments d ORDER BY d.name"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
<div class="container">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-building"></i> Departments</h2>
        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add</button>
    </div>
</div>
</div>

<div class="container mb-4">
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= h($msg) ?></div><?php endif; ?>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>Code</th><th>Name</th><th>Programs</th><th>Students</th><th>Actions</th></tr></thead>
<tbody>
<?php while ($d = $depts->fetch_assoc()): ?>
<tr>
    <td><strong><?= h($d['code']) ?></strong></td>
    <td><?= h($d['name']) ?></td>
    <td><span class="badge bg-info"><?= $d['program_count'] ?></span></td>
    <td><span class="badge bg-secondary"><?= $d['student_count'] ?></span></td>
    <td>
        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this department?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST">
<?= csrfField() ?>
<input type="hidden" name="action" value="create">
<div class="modal-header bg-lsc-green text-white">
    <h5 class="modal-title">Add Department</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label">Department Code</label>
        <input type="text" name="code" class="form-control" required maxlength="20" placeholder="e.g. MLT">
    </div>
    <div class="mb-3">
        <label class="form-label">Department Name</label>
        <input type="text" name="name" class="form-control" required placeholder="e.g. Medical Laboratory Technology">
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-lsc">Create</button>
</div>
</form>
</div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
