<?php
/* ================================================================
   ADMIN – Manage Programs
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole('admin');
$pageTitle = 'Programs';
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    $act = $_POST['action'] ?? '';

    if ($act === 'create') {
        $code   = strtoupper(trim($_POST['code']));
        $name   = trim($_POST['name']);
        $deptId = (int)$_POST['department_id'];
        $dur    = (int)$_POST['duration_years'];
        $deg    = trim($_POST['degree_type']);

        $stmt = $conn->prepare("INSERT INTO programs (department_id, code, name, duration_years, degree_type) VALUES (?,?,?,?,?)");
        $stmt->bind_param('issis', $deptId, $code, $name, $dur, $deg);
        if ($stmt->execute()) {
            logAudit($conn, 'CREATE_PROGRAM', 'program', $conn->insert_id, $code);
            $msg = "Program '$code' created."; $msgType = 'success';
        } else {
            $msg = 'Error: ' . $conn->error; $msgType = 'danger';
        }
    } elseif ($act === 'delete') {
        $id = (int)$_POST['prog_id'];
        $conn->query("DELETE FROM programs WHERE id = $id");
        logAudit($conn, 'DELETE_PROGRAM', 'program', $id, '');
        $msg = 'Program deleted.'; $msgType = 'info';
    }
}

$programs = $conn->query(
    "SELECT p.*, d.name AS dept_name
     FROM programs p
     JOIN departments d ON d.id = p.department_id
     ORDER BY d.name, p.code"
);
$depts = $conn->query("SELECT id, code, name FROM departments ORDER BY name");

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
<div class="container">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-diagram-3"></i> Programs</h2>
        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add</button>
    </div>
</div>
</div>

<div class="container mb-4">
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= h($msg) ?></div><?php endif; ?>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>Code</th><th>Program Name</th><th>Department</th><th>Type</th><th>Duration</th><th>Actions</th></tr></thead>
<tbody>
<?php while ($p = $programs->fetch_assoc()): ?>
<tr>
    <td><strong><?= h($p['code']) ?></strong></td>
    <td><?= h($p['name']) ?></td>
    <td><?= h($p['dept_name']) ?></td>
    <td><span class="badge bg-info"><?= h($p['degree_type']) ?></span></td>
    <td><?= $p['duration_years'] ?> years</td>
    <td>
        <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="prog_id" value="<?= $p['id'] ?>">
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
    <h5 class="modal-title">Add Program</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select" required>
            <option value="">— Select —</option>
            <?php while ($d = $depts->fetch_assoc()): ?>
            <option value="<?= $d['id'] ?>"><?= h($d['code'].' – '.$d['name']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Program Code</label>
        <input type="text" name="code" class="form-control" required placeholder="e.g. ND-MLT">
    </div>
    <div class="mb-3">
        <label class="form-label">Program Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="row g-3">
        <div class="col-6">
            <label class="form-label">Degree Type</label>
            <select name="degree_type" class="form-select">
                <option value="ND">ND</option>
                <option value="HND">HND</option>
                <option value="Certificate">Certificate</option>
            </select>
        </div>
        <div class="col-6">
            <label class="form-label">Duration (years)</label>
            <input type="number" name="duration_years" class="form-control" value="2" min="1" max="6">
        </div>
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
