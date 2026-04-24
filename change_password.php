<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if(isset($_POST['change'])){

    $newpass = $_POST['new_password'];
    $hash = password_hash($newpass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        UPDATE users
        SET password_hash=?, must_change_password=0, updated_at=NOW()
        WHERE id=?
    ");

    $stmt->bind_param("si",$hash,$user_id);
    $stmt->execute();

    /* Role-based redirect */
    $role = $_SESSION['role'] ?? 'student';
    $redirects = [
        'admin' => '/admin/index.php',
        'lecturer' => '/lecturer/index.php', 
        'hod' => '/hod/index.php',
        'student' => '/student/index.php',
        'staff' => '/staff_dashboard.php' // legacy fallback
    ];
    header("Location: " . ($redirects[$role] ?? '/student/index.php'));
    exit();
}
?>

<?php include "header.php"; ?>

<div class="container mt-5">
<div class="row justify-content-center">
<div class="col-md-5">

<div class="card shadow p-4">

<h4 class="text-center mb-3">Change Password</h4>

<form method="POST">

<label class="form-label">New Password</label>
<input type="password"
name="new_password"
class="form-control mb-3"
required>

<button name="change"
class="btn btn-primary w-100">
Update Password
</button>

</form>

</div>
</div>
</div>
</div>

<?php include "footer.php"; ?>
