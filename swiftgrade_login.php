<?php
/* ================================================================
   SwiftGrade – Unified Login (all roles)
   ================================================================ */
session_start();

/* ── Auto-redirect if already logged in ────────────────────── */
if (isset($_SESSION['user_id'])) {
    @require_once __DIR__ . '/includes/config.php';
    @require_once __DIR__ . '/includes/auth.php';
    if (function_exists('dashboardURL')) {
        header('Location: ' . dashboardURL());
        exit();
    }
}

$error = '';

/* ── Handle POST (login attempt) ─────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Load config (creates $conn) — catch DB failure gracefully */
    try {
        require_once __DIR__ . '/includes/config.php';
    } catch (\Throwable $e) {
        $error = 'Database is not available. Please run <a href="setup_database.php">setup_database.php</a> first.';
    }

    if ($error === '') {
        global $conn;
        $dbConn = $conn;

        if (!$dbConn || $dbConn->connect_error) {
            $error = 'Database connection failed. Please run <a href="setup_database.php">setup_database.php</a>.';
        } else {

            $username       = trim($_POST['username'] ?? '');
            $password       = trim($_POST['password'] ?? '');
            $institution_id = intval($_POST['institution_id'] ?? 0);

            if ($username === '' || $password === '') {
                $error = 'Please enter your username and password.';
            } elseif ($institution_id <= 0) {
                $error = 'Please select institution type and choose your school.';
            } else {

                /* Try matching by username OR matric_number */
                $sql = "SELECT u.id, u.username, u.password_hash, u.full_name, u.role,
                        u.department_id, u.must_change_password, u.is_active
                     FROM users u
                     LEFT JOIN students s ON s.user_id = u.id
                     WHERE (u.username = ? OR s.matric_no = ?) AND u.institution_id = ?";
                $stmt = $dbConn->prepare($sql);

                if (!$stmt) {
                    $error = 'Database error — please run <a href="setup_database.php">setup_database.php</a> to fix tables.';
                } else {
                    $stmt->bind_param('ssi', $username, $username, $institution_id);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();

                    /* Accept bcrypt hash OR plain-text match (legacy) */
                    $pw_ok = false;
                    if ($row) {
                        $pw_ok = password_verify($password, $row['password_hash'])
                              || $password === $row['password_hash'];
                    }

                    if ($row && $pw_ok) {

                        if (!$row['is_active']) {
                            $error = 'Your account has been deactivated. Contact the administrator.';
                        } else {
                            /* Regenerate session ID to prevent fixation */
                            session_regenerate_id(true);

                            $_SESSION['user_id']              = (int)$row['id'];
                            $_SESSION['username']             = $row['username'];
                            $_SESSION['full_name']            = $row['full_name'];
                            $_SESSION['role']                 = $row['role'];
                            $_SESSION['department_id']        = $row['department_id'];
                            $_SESSION['institution_id']       = $institution_id;
                            $_SESSION['must_change_password'] = (int)$row['must_change_password'];

                            /* Update last login */
                            $dbConn->query("UPDATE users SET last_login = NOW() WHERE id = " . (int)$row['id']);

                            /* Load auth helpers for redirect */
                            require_once __DIR__ . '/includes/auth.php';
                            @require_once __DIR__ . '/includes/audit.php';
                            if (function_exists('logAudit')) {
                                logAudit($dbConn, 'LOGIN', 'user', (int)$row['id'], 'Login successful');
                            }

                            /* Redirect: force password change or dashboard */
                            if ($row['must_change_password']) {
                                header('Location: ' . BASE_URL . '/swiftgrade_change_password.php');
                            } else {
                                header('Location: ' . dashboardURL());
                            }
                            exit();
                        }
                    } else {
                        $error = 'Invalid matric number / staff ID or password.';
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign In – SwiftGrade</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/swiftgrade.css" rel="stylesheet">
</head>
<body>

<div class="login-page">
    <!-- LEFT PANEL -->
    <div class="login-left">
        <div class="login-left-content">
            <div class="mb-4">
                <span style="font-size:2rem;color:var(--sg-gold)"><i class="bi bi-mortarboard-fill"></i></span>
            </div>
            <h1>Welcome to<br>Swift<span style="color:var(--sg-gold)">Grade</span></h1>
            <p>
                The secure, web-based result processing system for innovative colleges and academic institutions.
            </p>
            <ul class="login-features">
                <li>
                    <span class="check-circle"><i class="bi bi-check-lg"></i></span>
                    Role-based dashboards for Admin, Lecturers, HODs &amp; Students
                </li>
                <li>
                    <span class="check-circle"><i class="bi bi-check-lg"></i></span>
                    Automatic GPA / CGPA computation &amp; academic standing
                </li>
                <li>
                    <span class="check-circle"><i class="bi bi-check-lg"></i></span>
                    Multi-level approval workflow with full audit trail
                </li>
                <li>
                    <span class="check-circle"><i class="bi bi-check-lg"></i></span>
                    Downloadable PDF transcripts &amp; result sheets
                </li>
                <li>
                    <span class="check-circle"><i class="bi bi-check-lg"></i></span>
                    Enterprise-grade security &amp; data protection
                </li>
            </ul>
        </div>
    </div>

    <!-- RIGHT PANEL – LOGIN FORM -->
    <div class="login-right">
        <div class="login-form-wrap">
            <div class="logo-area">
                <h4><i class="bi bi-mortarboard-fill"></i> Swift<span>Grade</span></h4>
                <p>Academic Results Portal</p>
            </div>

            <h3>Sign In</h3>
            <p class="welcome-text">Enter your matric number (students) or staff ID to access your portal.</p>

            <?php if ($error): ?>
            <div class="alert alert-danger py-2">
                <i class="bi bi-exclamation-triangle-fill me-1"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>


            <form method="POST" autocomplete="off">
                <!-- First Dropdown: Institution Type -->
                <div class="mb-3">
                    <label class="form-label">Select Institution Type</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-building"></i></span>
                        <select name="institution_type" id="institution_type" class="form-select" required>
                            <option value="">Choose Type...</option>
                        </select>
                    </div>
                </div>

                <!-- Second Dropdown: Institution Name -->
                <div class="mb-3">
                    <label class="form-label">Select Your Institution</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-mortarboard"></i></span>
                        <select name="institution_id" id="institution_id" class="form-select" required disabled>
                            <option value="">Choose institution type first...</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Matric No / Staff ID</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-vcard-fill"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="e.g. LSC/ND/SLT/22/001"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="password" class="form-control"
                               placeholder="Enter your password" required id="loginPass">
                        <button class="btn btn-outline-secondary" type="button" id="togglePass"
                                style="border-color:var(--sg-gray-300)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-sg w-100 py-2 mb-3" style="font-size:1rem">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </button>

                <script>
                // Cascading dropdowns for institution selection
                const typeSelect = document.getElementById('institution_type');
                const institutionSelect = document.getElementById('institution_id');

                // Fallback data in case API fails
                const fallbackData = {
                  'Federal University': [
                    {id: 1, name: 'University of Ibadan', short_name: 'UNIBADAN'},
                    {id: 2, name: 'Ahmadu Bello University', short_name: 'ABU'},
                    {id: 3, name: 'Obafemi Awolowo University', short_name: 'OAU'}
                  ],
                  'State University': [
                    {id: 4, name: 'Lagos State University', short_name: 'LASU'},
                    {id: 5, name: 'Lagos State College of Health Technology', short_name: 'LASCOHET'}
                  ],
                  'Polytechnic': [
                    {id: 6, name: 'Yaba College of Technology', short_name: 'YABATECH'}
                  ]
                };

                // Load institution types on page load
                fetch('api/get_institutions_by_type.php')
                  .then(res => res.json())
                  .then(data => {
                    if (data.success && Array.isArray(data.data)) {
                      data.data.forEach(type => {
                        const opt = document.createElement('option');
                        opt.value = type.value;
                        opt.textContent = type.label;
                        typeSelect.appendChild(opt);
                      });
                    } else {
                      loadFallbackTypes();
                    }
                    // Restore type selection if form was submitted
                    <?php if (isset($_POST['institution_type'])): ?>
                      typeSelect.value = "<?= htmlspecialchars($_POST['institution_type']) ?>";
                      loadInstitutions("<?= htmlspecialchars($_POST['institution_type']) ?>");
                    <?php endif; ?>
                  })
                  .catch(err => {
                    console.error('Error loading institution types:', err);
                    loadFallbackTypes();
                  });

                // Load fallback types
                function loadFallbackTypes() {
                  Object.keys(fallbackData).forEach(type => {
                    const opt = document.createElement('option');
                    opt.value = type;
                    opt.textContent = type;
                    typeSelect.appendChild(opt);
                  });
                }

                // Load institutions when type changes
                typeSelect.addEventListener('change', function() {
                  if (this.value) {
                    loadInstitutions(this.value);
                  } else {
                    institutionSelect.innerHTML = '<option value="">Select institution...</option>';
                    institutionSelect.disabled = true;
                  }
                });

                // Function to load institutions by type
                function loadInstitutions(type) {
                  return fetch('api/get_institutions_by_type.php?type=' + encodeURIComponent(type))
                    .then(res => res.json())
                    .then(data => {
                      if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                        fillInstitutionDropdown(data.data);
                      } else if (fallbackData[type]) {
                        fillInstitutionDropdown(fallbackData[type]);
                      } else {
                        institutionSelect.innerHTML = '<option value="">No institutions found</option>';
                        institutionSelect.disabled = true;
                      }
                    })
                    .catch(err => {
                      console.error('Error loading institutions:', err);
                      if (fallbackData[type]) {
                        fillInstitutionDropdown(fallbackData[type]);
                      } else {
                        institutionSelect.innerHTML = '<option value="">Error loading institutions</option>';
                        institutionSelect.disabled = true;
                      }
                    });
                }

                // Fill institution dropdown
                function fillInstitutionDropdown(institutions) {
                  institutionSelect.innerHTML = '<option value="">Select institution...</option>';
                  institutions.forEach(inst => {
                    const opt = document.createElement('option');
                    opt.value = inst.id;
                    opt.textContent = inst.name + ' (' + inst.short_name + ')';
                    institutionSelect.appendChild(opt);
                  });
                  institutionSelect.disabled = false;
                  // Restore institution selection if form was submitted
                  <?php if (isset($_POST['institution_id'])): ?>
                    institutionSelect.value = "<?= htmlspecialchars($_POST['institution_id']) ?>";
                  <?php endif; ?>
                }

                function selectInstitutionById(id) {
                  const value = String(id);
                  for (let i = 0; i < institutionSelect.options.length; i++) {
                    if (institutionSelect.options[i].value === value) {
                      institutionSelect.selectedIndex = i;
                      institutionSelect.disabled = false;
                      return true;
                    }
                  }
                  return false;
                }

                // Demo login function - fill credentials and auto-select first institution type
                function fillLogin(username, password, institutionId = 1) {
                  document.querySelector('input[name="username"]').value = username;
                  document.querySelector('input[name="password"]').value = password;

                  function chooseInstitution() {
                    if (selectInstitutionById(institutionId)) {
                      return Promise.resolve();
                    }

                    let index = 0;
                    function next() {
                      if (index >= typeSelect.options.length) {
                        return Promise.resolve();
                      }

                      const typeOpt = typeSelect.options[index];
                      index++;
                      if (!typeOpt || !typeOpt.value) {
                        return next();
                      }

                      typeSelect.value = typeOpt.value;
                      return loadInstitutions(typeOpt.value).then(() => {
                        if (selectInstitutionById(institutionId)) {
                          return Promise.resolve();
                        }
                        return next();
                      });
                    }

                    return next();
                  }

                  chooseInstitution().finally(() => {
                    document.querySelector('input[name="username"]').focus();
                  });
                }
                </script>

                <div class="text-center">
                    <a href="index.php" class="text-decoration-none" style="color:var(--sg-primary);font-weight:500;font-size:.9rem">
                        <i class="bi bi-arrow-left me-1"></i>Back to Home
                    </a>
                </div>
            </form>

            <!-- Demo Credentials -->
            <div class="demo-credentials mt-4">
                <p class="text-center mb-2" style="font-size:.78rem;color:var(--sg-gray-400);text-transform:uppercase;letter-spacing:1px;font-weight:600">
                    <i class="bi bi-info-circle me-1"></i>Demo Accounts
                </p>
                <div class="d-flex flex-column gap-2">
                    <button type="button" class="demo-card" onclick="fillLogin('admin','Admin@2026', 1)">
                        <span class="demo-icon" style="background:var(--sg-primary-light);color:var(--sg-primary)"><i class="bi bi-shield-lock-fill"></i></span>
                        <div class="flex-grow-1 text-start">
                            <div class="demo-role">Admin</div>
                            <div class="demo-user">admin</div>
                        </div>
                        <i class="bi bi-arrow-right-circle text-muted"></i>
                    </button>
                    <button type="button" class="demo-card" onclick="fillLogin('mr.okeke','Lascohet@2026', 1)">
                        <span class="demo-icon" style="background:var(--sg-gold-light);color:var(--sg-gold-hover)"><i class="bi bi-person-workspace"></i></span>
                        <div class="flex-grow-1 text-start">
                            <div class="demo-role">Lecturer</div>
                            <div class="demo-user">mr.okeke</div>
                        </div>
                        <i class="bi bi-arrow-right-circle text-muted"></i>
                    </button>
                    <button type="button" class="demo-card" onclick="fillLogin('adesanya.john','Lascohet@2026', 1)">
                        <span class="demo-icon" style="background:#EFF6FF;color:#2563EB"><i class="bi bi-mortarboard-fill"></i></span>
                        <div class="flex-grow-1 text-start">
                            <div class="demo-role">Student</div>
                        <div class="demo-user">adesanya.john</div>
                        </div>
                        <i class="bi bi-arrow-right-circle text-muted"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePass').addEventListener('click', function(){
    const p = document.getElementById('loginPass');
    const icon = this.querySelector('i');
    if (p.type === 'password') { p.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else { p.type = 'password'; icon.className = 'bi bi-eye'; }
});
</script>
</body>
</html>
