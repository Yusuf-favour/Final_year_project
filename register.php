<?php
session_start();
include("db.php");

$msg = "";

if(isset($_POST['register']))
{
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);

    /* =========================
       CHECK USER EXISTS
    ========================= */

    $check = $conn->prepare(
        "SELECT id FROM users WHERE username=?"
    );

    $check->bind_param("s",$username);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0){
        $msg = "Username already exists!";
    }
    else{

        /* HASH PASSWORD */
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        /* INSERT STUDENT USER */
        $stmt = $conn->prepare(
            "INSERT INTO users(username,password_hash,full_name,role)
             VALUES(?, ?, ?, 'student')"
        );

        $stmt->bind_param("sss",$username,$hashed,$full_name);

        if($stmt->execute()){
            $user_id = $conn->insert_id;
            $matric_no = strtoupper('STU' . str_pad($user_id, 5, '0', STR_PAD_LEFT));

            $department_id = null;
            $program_id = null;
            $admission_year = date('Y');

            $deptRes = $conn->query("SELECT id FROM departments ORDER BY id LIMIT 1");
            if($deptRes && $deptRes->num_rows > 0){
                $department_id = (int)$deptRes->fetch_assoc()['id'];
            }

            $progRes = $conn->query("SELECT id FROM programs ORDER BY id LIMIT 1");
            if($progRes && $progRes->num_rows > 0){
                $program_id = (int)$progRes->fetch_assoc()['id'];
            }

            $stmt2 = $conn->prepare(
                "INSERT INTO students (user_id, matric_no, department_id, program_id, level, admission_year) VALUES (?, ?, ?, ?, 100, ?)"
            );
            $stmt2->bind_param('isiii', $user_id, $matric_no, $department_id, $program_id, $admission_year);

            if($stmt2 && $stmt2->execute()){
                $msg = "Registration Successful! Your student dashboard is now ready.";
            } else {
                $msg = "Registration completed, but student profile setup failed. Contact admin.";
            }
        }else{
            $msg = "Error occurred!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Register – UNIDEL</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="main-content d-flex align-items-center justify-content-center">

<div class="card shadow-lg p-4" style="width:420px;">

<h4 class="text-center mb-3 text-dark">
Student Registration
</h4>

<?php if($msg!=""){ ?>
<div class="alert alert-info text-center">
<?= $msg ?>
</div>
<?php } ?>

<form method="POST">

<div class="mb-3">
<label class="form-label">Username</label>
<input
type="text"
name="username"
class="form-control"
placeholder="Enter username"
required>
</div>

<div class="mb-3">
<label class="form-label">Password</label>
<input
type="password"
name="password"
class="form-control"
placeholder="Create password"
required>
</div>

<button name="register" class="btn btn-primary w-100">
Create Account
</button>

</form>

<p class="text-center mt-3">
Already have account?
<a href="login.php">Login</a>
</p>

</div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
