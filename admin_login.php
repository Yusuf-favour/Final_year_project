<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
include("db.php");

$error = "";

if(isset($_POST['login']))
{
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
           FETCH ADMIN USER FOR INSTITUTION
        ========================= */

        $stmt = $conn->prepare(
            "SELECT id, username, password_hash as pass, role, must_change_password, institution_id
             FROM users 
             WHERE username=? AND role='admin' AND is_active=1 AND institution_id=?"
        );

        $stmt->bind_param("si", $username, $institution_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if($result->num_rows === 1)
        {
            $row = $result->fetch_assoc();

            /* accept hashed OR plain password */
            if(password_verify($password,$row['pass']) || $password === $row['pass'])
            {
                /* SAME SESSION STRUCTURE (DO NOT CHANGE) */
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role']    = $row['role'];
                $_SESSION['must_change_password'] = $row['must_change_password'] ?? 0;
                $_SESSION['institution_id'] = $row['institution_id'];

                header("Location: admin/index_modern.php");
                exit();
            }
            else
            {
                $error = "Wrong Password";
            }
        }
        else
        {
            $error = "Admin not found at " . htmlspecialchars($_POST['institution_name'] ?? 'selected institution');
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Login – SwiftGrade University</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<?php include "header.php"; ?>

<div class="main-content d-flex align-items-center justify-content-center">

<div class="container d-flex justify-content-center mt-5">

<div class="card shadow p-4" style="width:420px;">

<h4 class="text-center mb-3 text-primary">SwiftGrade Admin Portal</h4>

<?php if($error!=""){ ?>
<div class="alert alert-danger text-center">
<?php echo $error; ?>
</div>
<?php } ?>

<form method="POST">

<label for="institution_id" class="form-label fw-bold">Select Your Institution</label>
<select 
    name="institution_id" 
    id="institution_id"
    class="form-select mb-3" 
    required>
    <option value="">-- Choose your institution --</option>
</select>
<small class="text-muted d-block mb-3">Select your institution before logging in</small>

<input
name="username"
class="form-control mb-3"
placeholder="Admin Username"
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

<button name="login" class="btn btn-dark w-100">
Login
</button>

</form>

<div class="text-center mt-3">
        Student login?
        <a href="login.php" class="text-decoration-none fw-semibold">Click Here</a>
        <br>
        Academic Staff?
        <a href="staff_login.php" class="text-decoration-none fw-semibold">Staff Login</a>
</div>

</div>
</div>
</div>

<?php include "footer.php"; ?>

<script>
// Load institutions on page load
document.addEventListener('DOMContentLoaded', function() {
    loadInstitutions();
    
    const institutionSelect = document.getElementById('institution_id');
    const institutionName = document.getElementById('institution_name');

    if (institutionSelect && institutionName) {
        institutionSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            institutionName.value = selectedOption ? selectedOption.text : '';
        });
    }
});

function loadInstitutions() {
    const select = document.getElementById('institution_id');
    
    fetch('api/get_institutions.php')
        .then(response => response.json())
        .then(data => {
            if(data.success && data.data) {
                select.innerHTML = '<option value="">-- Choose your institution --</option>';
                
                data.data.forEach(institution => {
                    const option = document.createElement('option');
                    option.value = institution.id;
                    option.textContent = institution.name + ' (' + institution.short_name + ')';
                    select.appendChild(option);
                });
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
