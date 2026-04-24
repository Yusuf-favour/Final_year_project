<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
include "db.php";

$error = "";

if(isset($_GET['error']) && $_GET['error'] === 'no_profile'){
    $error = "Your account login succeeded, but no student profile is attached. Please contact the administrator or register again.";
}

if(isset($_POST['login'])){

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $institution_id = isset($_POST['institution_id']) ? intval($_POST['institution_id']) : 0;

    /* =========================
       VALIDATE INSTITUTION SELECTED
    ========================= */
    if($institution_id <= 0) {
        $error = "Please select your institution.";
    } else {
        /* =========================
           FETCH USER FOR INSTITUTION
        ========================= */

        $stmt = $conn->prepare(
            "SELECT id, username, password_hash AS pass, role, must_change_password, full_name, institution_id
             FROM users
             WHERE username=? AND role='student' AND is_active=1 AND institution_id=?"
        );

        if (!$stmt) {
            $error = "Database error. Please try again later.";
        } else {
            $stmt->bind_param("si", $username, $institution_id);
            $stmt->execute();

            $result = $stmt->get_result();

            if($result->num_rows === 1){

                $data = $result->fetch_assoc();

                /* Accept hashed OR plain password */
                if(password_verify($password,$data['pass']) || $password === $data['pass']){

                    /* SESSION FORMAT (USED EVERYWHERE) */
                    $_SESSION['user_id'] = $data['id'];
                    $_SESSION['username'] = $data['username'];
                    $_SESSION['role']    = $data['role'];
                    $_SESSION['full_name'] = $data['full_name'];
                    $_SESSION['must_change_password'] = $data['must_change_password'];
                    $_SESSION['institution_id'] = $data['institution_id'];


                    /* ROLE REDIRECT */
                    if($data['role'] === "admin"){
            header("Location: dashboard.php");
            exit();
        }

        /* FORCE PASSWORD CHANGE */
        if($data['must_change_password'] == 1){
            header("Location: change_password.php");
            exit();
        }

        header("Location: student/index.php");
        exit();

                }
            }

            if(!isset($error) || $error === "") {
                $error = "Student not found or wrong password at " . htmlspecialchars($_POST['institution_name'] ?? 'selected institution') . ". For admins use admin login.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Student Login – SwiftGrade University</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="main-content d-flex align-items-center justify-content-center">

<div class="card shadow p-4" style="width:400px;">

<h4 class="text-center mb-3 text-dark">Student Login</h4>

<?php if($error!=""){ ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php } ?>

<form method="POST">

<!-- Institution Selection Dropdown -->
<label for="institution_id" class="form-label fw-bold">Select Your Institution</label>
<select 
    name="institution_id" 
    id="institution_id"
    class="form-select mb-3" 
    required>
    <option value="">-- Choose your institution --</option>
</select>
<small class="text-muted d-block mb-3">Select your university before entering credentials</small>

<input
type="text"
name="username"
class="form-control mb-3"
placeholder="Username"
required>

<input
type="hidden"
name="institution_name"
id="institution_name">

<input
type="password"
name="password"
class="form-control mb-3"
placeholder="Password"
required>

<button type="submit" name="login"
class="btn btn-primary w-100">
Login
</button>

</form>

<p class="mt-3 text-center">

Forgot Password?
<a href="forgot_password.php">Click here</a><br>

New user?
<a href="register.php">Register</a><br>

Academic Staff?
<a href="staff_login.php">Staff Login</a><br>

Admin login?
<a href="admin_login.php">Click Here</a>

</p>

</div>
</div>

<?php include "footer.php"; ?>

<script>
// Load institutions on page load
document.addEventListener('DOMContentLoaded', function() {
    loadInstitutions();
    
    // Update hidden field when institution changes
    document.getElementById('institution_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('institution_name').value = selectedOption.text;
    });
});

function loadInstitutions() {
    const select = document.getElementById('institution_id');
    
    fetch('api/get_institutions.php')
        .then(response => response.json())
        .then(data => {
            if(data.success && data.data && data.data.length > 0) {
                // Clear existing options except the placeholder
                select.innerHTML = '<option value="">-- Choose your institution --</option>';
                
                // Add institutions to dropdown
                data.data.forEach(institution => {
                    const option = document.createElement('option');
                    option.value = institution.id;
                    option.textContent = institution.name + ' (' + institution.short_name + ')';
                    select.appendChild(option);
                });
            } else {
                // No institutions found - show message
                select.innerHTML = '<option value="">No institutions available</option>';
                console.warn('No institutions found in database');
            }
        })
        .catch(error => {
            console.error('Error loading institutions:', error);
            select.innerHTML = '<option value="">Error loading institutions</option>';
        });
}
</script>

</body>
</html>
