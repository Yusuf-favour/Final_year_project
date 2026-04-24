<?php
/* ================================================================
   ADMIN – User Management (CRUD)
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$pageTitle = 'User Management';
$message = '';
$error = '';

/* ===== HANDLE FORM SUBMISSIONS ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* CREATE USER */
    if ($action === 'create') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $role = $_POST['role'];
        $password = trim($_POST['password']);
        $department_id = $_POST['department_id'] ?? null;

        if (empty($username) || empty($full_name) || empty($password)) {
            $error = '⚠️ All fields are required!';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO users (username, email, full_name, role, password_hash, department_id, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, 1)"
            );
            $stmt->bind_param('sssssi', $username, $email, $full_name, $role, $password_hash, $department_id);
            
            if ($stmt->execute()) {
                $message = '✅ User created successfully!';
            } else {
                $error = '❌ Error: ' . $stmt->error;
            }
        }
    }

    /* UPDATE USER */
    elseif ($action === 'update') {
        $user_id = (int)$_POST['user_id'];
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $is_active = (int)($_POST['is_active'] ?? 0);

        $stmt = $conn->prepare(
            "UPDATE users SET email = ?, full_name = ?, is_active = ? WHERE id = ?"
        );
        $stmt->bind_param('ssii', $email, $full_name, $is_active, $user_id);
        
        if ($stmt->execute()) {
            $message = '✅ User updated successfully!';
        } else {
            $error = '❌ Error: ' . $stmt->error;
        }
    }

    /* RESET PASSWORD */
    elseif ($action === 'reset_password') {
        $user_id = (int)$_POST['user_id'];
        $password = trim($_POST['password']);

        if (empty($password)) {
            $error = '⚠️ Password is required!';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param('si', $password_hash, $user_id);
            
            if ($stmt->execute()) {
                $message = '✅ Password reset successfully!';
            } else {
                $error = '❌ Error: ' . $stmt->error;
            }
        }
    }

    /* DELETE USER */
    elseif ($action === 'delete') {
        $user_id = (int)$_POST['user_id'];
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->bind_param('i', $user_id);
        
        if ($stmt->execute()) {
            $message = '✅ User deleted successfully!';
        } else {
            $error = '❌ Cannot delete admin accounts!';
        }
    }
}

/* Get all users */
$users = $conn->query(
    "SELECT u.*, d.name as dept_name 
     FROM users u 
     LEFT JOIN departments d ON d.id = u.department_id
     ORDER BY u.role DESC, u.username"
);

/* Get departments for dropdown */
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name");

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern-dashboard.css">

<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-people-fill"></i> User Management</h2>
                <small>Create, edit, and manage user accounts</small>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus"></i> Add New User
            </button>
        </div>
    </div>
</div>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- USERS TABLE -->
    <div class="card mb-5">
        <div class="card-header">
            <i class="bi bi-list"></i> All Users
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <code><?= h($user['username']) ?></code>
                            </td>
                            <td>
                                <strong><?= h($user['full_name']) ?></strong>
                            </td>
                            <td>
                                <small><?= h($user['email'] ?? '-') ?></small>
                            </td>
                            <td>
                                <span class="badge" style="background: 
                                    <?php
                                        switch($user['role']) {
                                            case 'admin': echo '#EF4444'; break;
                                            case 'lecturer': echo '#3B82F6'; break;
                                            case 'hod': echo '#8B5CF6'; break;
                                            default: echo '#10B981';
                                        }
                                    ?>; color: white;">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td>
                                <small><?= h($user['dept_name'] ?? '-') ?></small>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" 
                                            data-bs-target="#editUserModal" 
                                            onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" 
                                            data-bs-target="#resetPasswordModal"
                                            onclick="resetPasswordUser(<?= $user['id'] ?>, '<?= h($user['username']) ?>')">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteUser(<?= $user['id'] ?>, '<?= h($user['username']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mb-4"></div>
</div>

<!-- ADD USER MODAL -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="student">Student</option>
                            <option value="lecturer">Lecturer</option>
                            <option value="hod">HOD</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department (Optional)</label>
                        <select name="department_id" class="form-select">
                            <option value="">-- None --</option>
                            <?php 
                            $departments->data_seek(0);
                            while ($dept = $departments->fetch_assoc()): 
                            ?>
                                <option value="<?= $dept['id'] ?>"><?= h($dept['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT USER MODAL -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input" value="1" checked>
                            <label class="form-check-label" for="edit_is_active">
                                Active Account
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- RESET PASSWORD MODAL -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-key"></i> Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    
                    <p class="text-muted">Resetting password for: <strong id="reset_username"></strong></p>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="text" name="password" class="form-control" required 
                               placeholder="Enter new password (e.g., password123)">
                        <small class="text-muted">Tip: Use a simple password for testing</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_is_active').checked = user.is_active == 1;
}

function resetPasswordUser(userId, username) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username').textContent = username;
}

function deleteUser(userId, username) {
    if (confirm('Are you sure you want to delete ' + username + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
