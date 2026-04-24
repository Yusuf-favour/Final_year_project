<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
include("db.php");

$error = "";

if (isset($_POST['login'])) {
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
           FETCH STAFF FOR INSTITUTION
        ========================= */

        $stmt = $conn->prepare(
            "SELECT id, username, password_hash as pass, role, must_change_password, full_name, institution_id
             FROM users
             WHERE username=? AND role IN ('lecturer','hod') AND is_active=1 AND institution_id=?"
        );
        $stmt->bind_param("si", $username, $institution_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if (password_verify($password, $row['pass']) || $password === $row['pass']) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role']    = $row['role'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['institution_id'] = $row['institution_id'];

                /* Staff profile data from users table (no separate staff table) */
                $_SESSION['staff_name'] = $row['full_name'];

                if ($row['must_change_password'] == 1) {
                    header("Location: change_password.php");
                    exit();
                }
                if ($row['role'] === 'lecturer') {
                    header("Location: lecturer/index.php");
                    exit();
                } elseif ($row['role'] === 'hod') {
                    header("Location: hod/index.php");
                    exit();
                } else {
                    header("Location: staff_dashboard.php");
                    exit();
                }
            } else {
                $error = "Wrong Password";
            }
        } else {
            $error = "Staff account not found at " . htmlspecialchars($_POST['institution_name'] ?? 'selected institution');
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Academic Staff Login – SwiftGrade University</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>

<body class="d-flex flex-column min-vh-100">

<?php include "header.php"; ?>

<div class="main-content d-flex align-items-center justify-content-center">
<div class="card shadow p-4" style="width:420px;">

<h4 class="text-center mb-3 text-success">
<i class="bi bi-person-badge-fill"></i> SwiftGrade Academic Portal
</h4>

<?php if ($error != "") { ?>
<div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
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

<input name="username"
       class="form-control mb-3"
       placeholder="Staff Username"
       required>

<input
type="hidden"
name="institution_name"
id="institution_name">

<input type="password"
       name="password"
       class="form-control mb-3"
       placeholder="Password"
       required>

<button name="login" class="btn btn-success w-100">
<i class="bi bi-box-arrow-in-right"></i> Academic Login
</button>

</form>

<div class="text-center mt-3">
    Student login? <a href="login.php">Click Here</a><br>
    Admin login? <a href="admin_login.php">Click Here</a>
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
