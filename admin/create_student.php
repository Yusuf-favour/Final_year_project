<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header('Location: ../admin_login.php');
    exit;
}

$message = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $matric_no = trim($_POST['matric_no']);

    // Hash password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, role, must_change_password, is_active) VALUES (?, ?, ?, 'student', 1, 1)");
    $stmt->bind_param('sss', $username, $hash, $full_name);
    $stmt->execute();
    $user_id = $conn->insert_id;

    // Insert student profile
    $dept_id = 1; // Default
$stmt2 = $conn->prepare("INSERT IGNORE INTO students (user_id, matric_no, department_id, level) VALUES (?, ?, ?, 100)");
$stmt2->bind_param('isi', $user_id, $matric_no, $dept_id);
if (!$stmt2->execute()) {
    $message .= ' (Student profile already exists or error)';
}


    $message = "Student '$username' created! Login: $username / $password";
    header('Location: users.php');
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
<title>Create Student - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
<div class="card mx-auto" style="max-width:500px;">
<h4 class="card-header">Create Student Account</h4>
<div class="card-body">
<?php if ($message): ?>
<div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>
<form method="POST">
<div class="mb-3">
<label>Username</label>
<input type="text" name="username" class="form-control" required>
</div>
<div class="mb-3">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>
<div class="mb-3">
<label>Full Name</label>
<input type="text" name="full_name" class="form-control" required>
</div>
<div class="mb-3">
<label>Matric No</label>
<input type="text" name="matric_no" class="form-control" required>
</div>
<button type="submit" class="btn btn-primary w-100">Create Student</button>
</form>
<a href="index.php" class="btn btn-secondary w-100 mt-2">Back to Dashboard</a>
</div>
</div>
</div>
</body>
</html>

