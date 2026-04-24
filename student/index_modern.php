<?php
/* ================================================================
   STUDENT – Modernized Dashboard
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('student');

if ($_SESSION['must_change_password'] ?? 0) {
    header('Location: ' . BASE_URL . '/swiftgrade_change_password.php');
    exit();
}

$pageTitle = 'Student Dashboard';
$userId = (int)$_SESSION['user_id'];

/* Fetch student profile */
$sq = $conn->prepare(
    "SELECT s.*, p.name AS program_name, p.code AS program_code,
            d.name AS dept_name, d.code AS dept_code
     FROM students s
     JOIN programs p ON p.id = s.program_id
     JOIN departments d ON d.id = s.department_id
     WHERE s.user_id = ?"
);
$sq->bind_param('i', $userId);
$sq->execute();
$student = $sq->get_result()->fetch_assoc();

if (!$student) {
    session_destroy();
    header('Location: ' . BASE_URL . '/swiftgrade_login.php?error=no_profile');
    exit();
}

$studentId = (int)$student['id'];
$sem = currentSemester($conn);

/* Published results */
$resultsSummary = $conn->query(
    "SELECT sem.semester_number, a.session_name,
            COUNT(r.id) AS course_count,
            ROUND(SUM(r.grade_point * c.credit_units) / NULLIF(SUM(c.credit_units),0), 2) AS gpa,
            SUM(c.credit_units) AS total_cu
     FROM results r
     JOIN result_batches rb ON rb.id = r.batch_id
     JOIN courses c ON c.id = r.course_id
     JOIN semesters sem ON sem.id = r.semester_id
     JOIN academic_sessions a ON a.id = sem.session_id
     WHERE r.student_id = $studentId
       AND rb.status = 'published'
     GROUP BY sem.id
     ORDER BY a.session_name DESC, sem.semester_number DESC"
);

/* CGPA */
$cgpaQ = $conn->query(
    "SELECT ROUND(SUM(r.grade_point * c.credit_units) / NULLIF(SUM(c.credit_units),0), 2) AS cgpa,
            COUNT(r.id) AS total_courses,
            SUM(c.credit_units) AS total_cu
     FROM results r
     JOIN result_batches rb ON rb.id = r.batch_id
     JOIN courses c ON c.id = r.course_id
     WHERE r.student_id = $studentId
       AND rb.status = 'published'"
)->fetch_assoc();

$cgpa = (float)($cgpaQ['cgpa'] ?? 0);
$standing = academicStanding($cgpa);

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern-dashboard.css">

<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-mortarboard"></i> Student Dashboard</h2>
                <small>Welcome, <?= h(currentFullName()) ?></small>
            </div>
            <a href="register_courses.php" class="btn btn-primary">
                <i class="bi bi-journal-plus"></i> Register Courses
            </a>
        </div>
    </div>
</div>

<div class="container">
    <!-- ACADEMIC PERFORMANCE -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-center">
                    <div class="stat-value" style="color: #3B82F6;"><?= number_format($cgpa, 2) ?></div>
                    <div class="stat-label">CGPA</div>
                    <small class="text-muted d-block mt-2">Cumulative GPA</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-center">
                    <div class="stat-value" style="color: #10B981;"><?= h($standing) ?></div>
                    <div class="stat-label">Academic Standing</div>
                    <small class="text-muted d-block mt-2">Current status</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-center">
                    <div class="stat-value" style="color: #F59E0B;"><?= (int)($cgpaQ['total_courses'] ?? 0) ?></div>
                    <div class="stat-label">Courses Completed</div>
                    <small class="text-muted d-block mt-2">Published results</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-center">
                    <div class="stat-value" style="color: #8B5CF6;"><?= (int)($cgpaQ['total_cu'] ?? 0) ?></div>
                    <div class="stat-label">Credit Units Earned</div>
                    <small class="text-muted d-block mt-2">Total CU</small>
                </div>
            </div>
        </div>
    </div>

    <!-- STUDENT PROFILE -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-person-circle"></i> Personal Information
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-5 text-muted">Full Name:</div>
                        <div class="col-7"><strong><?= h(currentFullName()) ?></strong></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-5 text-muted">Matric Number:</div>
                        <div class="col-7"><code><?= h($student['matric_no']) ?></code></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-5 text-muted">Level:</div>
                        <div class="col-7"><strong><?= $student['level'] ?></strong></div>
                    </div>
                    <div class="row">
                        <div class="col-5 text-muted">Entry Year:</div>
                        <div class="col-7"><strong><?= $student['admission_year'] ?? date('Y') ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-building"></i> Academic Information
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-5 text-muted">Department:</div>
                        <div class="col-7"><strong><?= h($student['dept_name']) ?></strong></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-5 text-muted">Dept Code:</div>
                        <div class="col-7"><code><?= h($student['dept_code']) ?></code></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-5 text-muted">Program:</div>
                        <div class="col-7"><strong><?= h($student['program_name']) ?></strong></div>
                    </div>
                    <div class="row">
                        <div class="col-5 text-muted">Program Code:</div>
                        <div class="col-7"><code><?= h($student['program_code']) ?></code></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEMESTER RESULTS -->
    <h4 class="mb-4"><i class="bi bi-graph-up"></i> Semester Results</h4>

    <?php if ($resultsSummary->num_rows === 0): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No published results yet. Results will appear here after the approval workflow is complete.
        </div>
    <?php else: ?>
        <div class="card mb-5">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Session</th>
                                <th>Semester</th>
                                <th>Courses</th>
                                <th>Credit Units</th>
                                <th style="text-align: center;">GPA</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($rs = $resultsSummary->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= h($rs['session_name']) ?></strong></td>
                                <td>
                                    <span class="badge" style="background: #E0E7FF; color: #4338CA;">
                                        Sem <?= $rs['semester_number'] ?>
                                    </span>
                                </td>
                                <td><?= $rs['course_count'] ?> courses</td>
                                <td><?= (int)$rs['total_cu'] ?> CU</td>
                                <td style="text-align: center;">
                                    <strong style="font-size: 1.1rem; color: var(--primary);">
                                        <?= number_format($rs['gpa'], 2) ?>
                                    </strong>
                                </td>
                                <td>
                                    <a href="results.php?semester_id=<?= /* TODO: pass semester_id */ 0 ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- QUICK LINKS -->
    <div class="card mb-5">
        <div class="card-header">
            <i class="bi bi-lightning"></i> Quick Actions
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="register_courses.php" class="btn btn-primary w-100 py-3">
                        <i class="bi bi-journal-plus"></i><br>
                        <small>Register Courses</small>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="results.php" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-file-earmark-bar-graph"></i><br>
                        <small>View Results</small>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= BASE_URL ?>/swiftgrade_change_password.php" class="btn btn-outline-primary w-100 py-3">
                        <i class="bi bi-key"></i><br>
                        <small>Change Password</small>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= BASE_URL ?>/swiftgrade_logout.php" class="btn btn-outline-danger w-100 py-3">
                        <i class="bi bi-box-arrow-right"></i><br>
                        <small>Logout</small>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4"></div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
