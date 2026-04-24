<?php
/* ================================================================
   ADMIN – Manage Courses
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole('admin');
$pageTitle = 'Courses';
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    $act = $_POST['action'] ?? '';

    if ($act === 'create') {
        $code    = strtoupper(trim($_POST['code']));
        $title   = trim($_POST['title']);
        $cu      = (int)$_POST['credit_units'];
        $deptId  = (int)$_POST['department_id'];
        $progId  = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
        $level   = (int)$_POST['level'];
        $sem     = (int)$_POST['semester'];

        $stmt = $conn->prepare(
            "INSERT INTO courses (code, title, credit_units, department_id, program_id, level, semester)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('ssiisii', $code, $title, $cu, $deptId, $progId, $level, $sem);
        if ($stmt->execute()) {
            logAudit($conn, 'CREATE_COURSE', 'course', $conn->insert_id, $code);
            $msg = "Course '$code' created."; $msgType = 'success';
        } else {
            $msg = 'Error: ' . $conn->error; $msgType = 'danger';
        }

    } elseif ($act === 'assign') {
        $courseId = (int)$_POST['course_id'];
        $lecId   = (int)$_POST['lecturer_id'];
        $sem     = currentSemester($conn);
        if ($sem) {
            $stmt = $conn->prepare(
                "INSERT IGNORE INTO course_assignments (course_id, lecturer_id, semester_id) VALUES (?,?,?)"
            );
            $stmt->bind_param('iii', $courseId, $lecId, $sem['id']);
            $stmt->execute();
            logAudit($conn, 'ASSIGN_COURSE', 'course', $courseId, "Lecturer ID: $lecId");
            $msg = 'Course assigned.'; $msgType = 'success';
        }

    } elseif ($act === 'delete') {
        $id = (int)$_POST['course_id'];
        $conn->query("DELETE FROM courses WHERE id = $id");
        logAudit($conn, 'DELETE_COURSE', 'course', $id, '');
        $msg = 'Course deleted.'; $msgType = 'info';
    }
}

$courses = $conn->query(
    "SELECT c.*, d.code AS dept_code
     FROM courses c
     JOIN departments d ON d.id = c.department_id
     ORDER BY d.code, c.level, c.semester, c.code"
);
$depts = $conn->query("SELECT id, code, name FROM departments ORDER BY name");
$progs = $conn->query("SELECT id, code, name FROM programs ORDER BY name");
$lecturers = $conn->query("SELECT id, full_name FROM users WHERE role IN ('lecturer','hod') AND is_active=1 ORDER BY full_name");

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
<div class="container">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-book"></i> Courses</h2>
        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg"></i> Add Course</button>
    </div>
</div>
</div>

<div class="container mb-4">
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= h($msg) ?></div><?php endif; ?>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>Code</th><th>Title</th><th>CU</th><th>Dept</th><th>Level</th><th>Sem</th><th>Actions</th></tr></thead>
<tbody>
<?php while ($c = $courses->fetch_assoc()): ?>
<tr>
    <td><strong><?= h($c['code']) ?></strong></td>
    <td><?= h($c['title']) ?></td>
    <td><?= $c['credit_units'] ?></td>
    <td><?= h($c['dept_code']) ?></td>
    <td><?= $c['level'] ?></td>
    <td><?= $c['semester'] ?></td>
    <td>
        <!-- Assign button -->
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                data-bs-target="#assignModal" onclick="document.getElementById('assignCourseId').value=<?= $c['id'] ?>">
            <i class="bi bi-person-plus"></i>
        </button>
        <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
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

<!-- Add Course Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST">
<?= csrfField() ?>
<input type="hidden" name="action" value="create">
<div class="modal-header bg-lsc-green text-white">
    <h5 class="modal-title">Add Course</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-6">
            <label class="form-label">Course Code</label>
            <input type="text" name="code" class="form-control" required placeholder="e.g. MLT 201">
        </div>
        <div class="col-6">
            <label class="form-label">Credit Units</label>
            <input type="number" name="credit_units" class="form-control" value="2" min="1" max="6">
        </div>
        <div class="col-12">
            <label class="form-label">Course Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="col-6">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select" required>
                <option value="">—</option>
                <?php while ($d = $depts->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>"><?= h($d['code']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-6">
            <label class="form-label">Program (optional)</label>
            <select name="program_id" class="form-select">
                <option value="">— All —</option>
                <?php while ($p = $progs->fetch_assoc()): ?>
                <option value="<?= $p['id'] ?>"><?= h($p['code']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-6">
            <label class="form-label">Level</label>
            <select name="level" class="form-select">
                <option value="100">100</option><option value="200">200</option>
                <option value="300">300</option><option value="400">400</option>
            </select>
        </div>
        <div class="col-6">
            <label class="form-label">Semester</label>
            <select name="semester" class="form-select">
                <option value="1">1st Semester</option>
                <option value="2">2nd Semester</option>
            </select>
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

<!-- Assign Lecturer Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
<div class="modal-dialog modal-sm">
<div class="modal-content">
<form method="POST">
<?= csrfField() ?>
<input type="hidden" name="action" value="assign">
<input type="hidden" name="course_id" id="assignCourseId">
<div class="modal-header bg-lsc-green text-white">
    <h5 class="modal-title">Assign Lecturer</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <select name="lecturer_id" class="form-select" required>
        <option value="">— Select —</option>
        <?php while ($l = $lecturers->fetch_assoc()): ?>
        <option value="<?= $l['id'] ?>"><?= h($l['full_name']) ?></option>
        <?php endwhile; ?>
    </select>
</div>
<div class="modal-footer">
    <button type="submit" class="btn btn-lsc">Assign</button>
</div>
</form>
</div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
