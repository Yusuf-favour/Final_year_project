<?php
/* ================================================================
   ADMIN – User Management (CRUD)
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole('admin');

$pageTitle = 'Manage Users';
$msg = '';
$msgType = '';

/* ---------- departments for dropdowns ---------- */

// Load all institutions
$institutions = $conn->query("SELECT id, name FROM institutions WHERE is_active=1 ORDER BY name");

// Load all departments with institution_id
$departments = $conn->query("SELECT id, code, name, institution_id FROM departments ORDER BY name");
// We'll use $departments for the dropdown JS

/* ---------- CREATE / UPDATE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF();
    $action = $_POST['action'] ?? '';


    if ($action === 'create') {
        $username      = trim($_POST['username']);
        $fullName      = trim($_POST['full_name']);
        $email         = trim($_POST['email']);
        $role          = $_POST['role'];
        $deptId        = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $institutionId = !empty($_POST['institution_id']) ? (int)$_POST['institution_id'] : null;
        $password      = trim($_POST['password']);

        if (!in_array($role, ['admin','lecturer','hod','student'])) die('Invalid role');

        $matricNo   = trim($_POST['matric_no'] ?? '');
        $programId  = (int)($_POST['program_id'] ?? 0);
        $level      = (int)($_POST['level'] ?? 100);
        $admYear    = (int)($_POST['admission_year'] ?? date('Y'));

        $errors = [];
        if ($username === '' || $fullName === '' || $password === '') {
            $errors[] = 'Username, full name and password are required.';
        }
        if ($institutionId <= 0) {
            $errors[] = 'Please select an institution.';
        }
        if (in_array($role, ['student','lecturer','hod'], true) && !$deptId) {
            $errors[] = 'Please select a department for this user.';
        }
        if ($role === 'student') {
            if ($matricNo === '') {
                $errors[] = 'Please enter a matric number for this student.';
            }
            if ($programId <= 0) {
                $errors[] = 'Please select a program for this student.';
            }
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (username, password_hash, email, full_name, role, department_id, institution_id, must_change_password)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
            );
            $stmt->bind_param('ssssssi', $username, $hash, $email, $fullName, $role, $deptId, $institutionId);

            if ($stmt->execute()) {
                $newId = $conn->insert_id;

                /* If student, also create student profile */
                if ($role === 'student') {
                    // Get current session id for entry_session_id
                    $sessQ = $conn->query("SELECT id FROM academic_sessions WHERE is_current=1 LIMIT 1");
                    $sess = $sessQ && $sessQ->num_rows ? $sessQ->fetch_assoc() : ['id' => 1];
                    $entrySessionId = (int)$sess['id'];

                    $sstmt = $conn->prepare(
                        "INSERT IGNORE INTO students (user_id, matric_no, department_id, program_id, level, admission_year, entry_session_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    $sstmt->bind_param('isiiiii', $newId, $matricNo, $deptId, $programId, $level, $admYear, $entrySessionId);
                    $sstmt->execute();
                    if ($sstmt->error) {
                        $msg = 'User created, but student profile failed: ' . $sstmt->error;
                        $msgType = 'warning';
                    }
                }

                logAudit($conn, 'CREATE_USER', 'user', $newId, "Created $role: $username");
                if (!$msgType) {
                    $msg = "User '$username' created with default password. They must change on first login.";
                    $msgType = 'success';
                }
            } else {
                $msg = 'Error: ' . $conn->error;
                $msgType = 'danger';
            }
        } else {
            $msg = implode('<br>', $errors);
            $msgType = 'danger';
        }

    } elseif ($action === 'toggle_active') {
        $id = (int)$_POST['user_id'];
        $conn->query("UPDATE users SET is_active = NOT is_active WHERE id = $id AND id != " . (int)$_SESSION['user_id']);
        logAudit($conn, 'TOGGLE_USER_STATUS', 'user', $id, 'Toggled active status');
        $msg = 'User status updated.';
        $msgType = 'info';

    } elseif ($action === 'reset_password') {
        $id = (int)$_POST['user_id'];
        $newPass = 'Reset@' . date('Y');
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?");
        $stmt->bind_param('si', $hash, $id);
        $stmt->execute();
        logAudit($conn, 'RESET_PASSWORD', 'user', $id, 'Password reset by admin');
        $msg = "Password reset to: <code>$newPass</code> — user must change on next login.";
        $msgType = 'warning';

    } elseif ($action === 'delete_user') {
        $id = (int)$_POST['user_id'];
        if ($id === (int)$_SESSION['user_id']) {
            $msg = 'You cannot delete your own account while logged in.';
            $msgType = 'danger';
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                logAudit($conn, 'DELETE_USER', 'user', $id, 'Deleted user account');
                $msg = 'User deleted successfully.';
                $msgType = 'success';
            } else {
                $msg = 'User could not be deleted.';
                $msgType = 'danger';
            }
            $stmt->close();
        }
    }
}

/* ---------- FETCH USERS ---------- */
$filter = $_GET['role'] ?? '';
$where = '';
if (in_array($filter, ['admin','lecturer','hod','student'])) {
    $where = "WHERE u.role = '" . $conn->real_escape_string($filter) . "'";
}

$users = $conn->query(
    "SELECT u.*, d.name AS dept_name
     FROM users u
     LEFT JOIN departments d ON d.id = u.department_id
     $where
     ORDER BY u.created_at DESC"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
<div class="container">
    <div class="d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-people"></i> Manage Users</h2>
        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-plus-lg"></i> Add User
        </button>
    </div>
</div>
</div>

<div class="container mb-4">

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
    <?= $msg ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filter tabs -->
<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link <?= !$filter?'active':'' ?>" href="users.php">All</a></li>
    <li class="nav-item"><a class="nav-link <?= $filter==='admin'?'active':'' ?>" href="users.php?role=admin">Admins</a></li>
    <li class="nav-item"><a class="nav-link <?= $filter==='lecturer'?'active':'' ?>" href="users.php?role=lecturer">Lecturers</a></li>
    <li class="nav-item"><a class="nav-link <?= $filter==='hod'?'active':'' ?>" href="users.php?role=hod">HODs</a></li>
    <li class="nav-item"><a class="nav-link <?= $filter==='student'?'active':'' ?>" href="users.php?role=student">Students</a></li>
</ul>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead>
<tr>
    <th>#</th><th>Username</th><th>Full Name</th><th>Email</th>
    <th>Role</th><th>Department</th><th>Active</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php while ($u = $users->fetch_assoc()): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><strong><?= h($u['username']) ?></strong></td>
    <td><?= h($u['full_name']) ?></td>
    <td><?= h($u['email']) ?></td>
    <td><span class="badge bg-<?= $u['role']==='admin'?'danger':($u['role']==='hod'?'info':'secondary') ?>"><?= h(ucfirst($u['role'])) ?></span></td>
    <td><?= h($u['dept_name'] ?? '—') ?></td>
    <td>
        <?php if ($u['is_active']): ?>
            <span class="badge bg-success">Active</span>
        <?php else: ?>
            <span class="badge bg-secondary">Inactive</span>
        <?php endif; ?>
    </td>
    <td>
        <form method="POST" class="d-inline">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="toggle_active">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <button class="btn btn-sm btn-outline-warning" title="Toggle active">
                <i class="bi bi-toggle-on"></i>
            </button>
        </form>
        <form method="POST" class="d-inline" onsubmit="return confirm('Reset password?')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <button class="btn btn-sm btn-outline-secondary" title="Reset password">
                <i class="bi bi-key"></i>
            </button>
        </form>
        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user? This cannot be undone.')">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <button class="btn btn-sm btn-outline-danger" title="Delete user">
                <i class="bi bi-trash"></i>
            </button>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<form method="POST">
<?= csrfField() ?>
<input type="hidden" name="action" value="create">
<div class="modal-header bg-lsc-green text-white">
    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add New User</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required minlength="6" value="NextGen@2026">
            <small class="text-muted">Default: NextGen@2026</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Role <span class="text-danger">*</span></label>
            <select name="role" id="roleSelect" class="form-select" required onchange="toggleStudentFields()">
                <option value="student">Student</option>
                <option value="lecturer">Lecturer</option>
                <option value="hod">HOD / Exam Officer</option>
                <option value="admin">Administrator</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Institution <span class="text-danger">*</span></label>
            <select name="institution_id" id="institutionSelect" class="form-select" required onchange="filterDepartments()">
                <option value="">— Select —</option>
                <?php if ($institutions) while ($inst = $institutions->fetch_assoc()): ?>
                <option value="<?= $inst['id'] ?>"><?= h($inst['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Department</label>
            <select name="department_id" id="departmentSelect" class="form-select">
                <option value="">— Select —</option>
                <!-- Options will be populated by JS -->
            </select>
        </div>

        <!-- Student-specific fields -->
        <div class="col-12 student-fields">
            <hr><h6 class="text-muted">Student Details</h6>
        </div>
        <div class="col-md-4 student-fields">
            <label class="form-label">Matric No</label>
            <input type="text" name="matric_no" class="form-control">
        </div>
        <div class="col-md-4 student-fields">
            <label class="form-label">Program</label>
            <select name="program_id" id="programSelect" class="form-select">
                <option value="">— Select —</option>
                <?php
$progs = $conn->query("SELECT id, code, name FROM programs ORDER BY name");
if (!$progs) {
    echo '<option>No programs</option>';
}
                while ($p = $progs->fetch_assoc()):
                ?>
                <option value="<?= $p['id'] ?>"><?= h($p['code'].' – '.$p['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2 student-fields">
            <label class="form-label">Level</label>
            <select name="level" class="form-select">
                <option value="100">100</option>
                <option value="200">200</option>
                <option value="300">300</option>
                <option value="400">400</option>
            </select>
        </div>
        <div class="col-md-2 student-fields">
            <label class="form-label">Admission Year</label>
            <input type="number" name="admission_year" class="form-control" value="<?= date('Y') ?>">
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-lsc"><i class="bi bi-check-lg"></i> Create User</button>
</div>
</form>
</div>
</div>
</div>

<script>
// Departments data for filtering
const departments = <?php
    $deptArr = [];
    if ($departments) {
        while ($d = $departments->fetch_assoc()) {
            $deptArr[] = [
                'id' => $d['id'],
                'code' => $d['code'],
                'name' => $d['name'],
                'institution_id' => $d['institution_id']
            ];
        }
    }
    echo json_encode($deptArr);
?>;

function filterDepartments() {
    const instId = document.getElementById('institutionSelect').value;
    const deptSelect = document.getElementById('departmentSelect');
    deptSelect.innerHTML = '<option value="">— Select —</option>';
    departments.forEach(d => {
        if (!instId || d.institution_id == instId) {
            deptSelect.innerHTML += `<option value="${d.id}">${d.code} – ${d.name}</option>`;
        }
    });
}

function toggleStudentFields() {
    const role = document.getElementById('roleSelect').value;
    const showStudent = role === 'student';
    const deptSelect = document.getElementById('departmentSelect');
    const progSelect = document.getElementById('programSelect');

    document.querySelectorAll('.student-fields').forEach(el => {
        el.style.display = showStudent ? '' : 'none';
    });

    deptSelect.required = role === 'student' || role === 'hod' || role === 'lecturer';
    if (progSelect) {
        progSelect.required = showStudent;
    }
}

// Initial setup
document.addEventListener('DOMContentLoaded', function() {
    filterDepartments();
    toggleStudentFields();
    document.getElementById('institutionSelect').addEventListener('change', filterDepartments);
    document.getElementById('roleSelect').addEventListener('change', toggleStudentFields);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
