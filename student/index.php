<?php
/* ================================================================
   STUDENT – Dashboard
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
    // Clear invalid session and redirect to login
    session_destroy();
    header('Location: ' . BASE_URL . '/swiftgrade_login.php?error=no_profile');
    exit();
}

$studentId = (int)$student['id'];
$sem = currentSemester($conn);

$currentRegistrationCount = 0;
$currentRegistrationUnits = 0;
if ($sem) {
    $regSummary = $conn->query(
        "SELECT COUNT(cr.id) AS total_courses, COALESCE(SUM(c.credit_units), 0) AS total_units
         FROM course_registrations cr
         JOIN courses c ON c.id = cr.course_id
         WHERE cr.student_id = $studentId AND cr.semester_id = " . (int)$sem['id']
    );
    if ($regSummary) {
        $regRow = $regSummary->fetch_assoc();
        $currentRegistrationCount = (int)($regRow['total_courses'] ?? 0);
        $currentRegistrationUnits = (int)($regRow['total_units'] ?? 0);
    }
}

/* Published results overview */
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
     ORDER BY a.session_name, sem.semester_number"
);

/* CGPA */
$cgpaQ = $conn->query(
    "SELECT ROUND(SUM(r.grade_point * c.credit_units) / NULLIF(SUM(c.credit_units),0), 2) AS cgpa,
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
    <div class="container d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <h2><i class="bi bi-mortarboard"></i> Student Dashboard</h2>
            <small><?= h($sem['session_name'] ?? 'Current Session') ?> · Semester <?= h($sem['semester_number'] ?? '-') ?> · Welcome, <?= h(currentFullName()) ?></small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="notifications.php" class="btn btn-white">
                <i class="bi bi-bell me-1"></i> Notifications
            </a>
            <a href="register_courses.php" class="btn btn-white">
                <i class="bi bi-journal-plus me-1"></i> Register Courses
            </a>
            <a href="results.php" class="btn btn-sg-outline">
                <i class="bi bi-bar-chart-line me-1"></i> View Results
            </a>
        </div>
    </div>
</div>

<div class="container pb-4">
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value metric-accent"><?= number_format($cgpa, 2) ?></div>
                <div class="stat-label">CGPA</div>
                <small class="text-muted d-block mt-2">Cumulative performance</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value metric-accent"><?= h($standing) ?></div>
                <div class="stat-label">Standing</div>
                <small class="text-muted d-block mt-2">Academic status</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value metric-accent"><?= $currentRegistrationCount ?></div>
                <div class="stat-label">Current Courses</div>
                <small class="text-muted d-block mt-2">Registered this semester</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="stat-value metric-accent"><?= $currentRegistrationUnits ?></div>
                <div class="stat-label">Current Units</div>
                <small class="text-muted d-block mt-2">Loaded credit units</small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-7">
            <div class="dashboard-card card h-100">
                <div class="card-header">
                    <i class="bi bi-person-circle me-2"></i>Student Profile
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="action-tile h-100">
                                <div class="label">Full Name</div>
                                <div class="value"><?= h(currentFullName()) ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="action-tile h-100">
                                <div class="label">Matric Number</div>
                                <div class="value"><?= h($student['matric_no']) ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="action-tile h-100">
                                <div class="label">Department</div>
                                <div class="value"><?= h($student['dept_name']) ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="action-tile h-100">
                                <div class="label">Program</div>
                                <div class="value"><?= h($student['program_name']) ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="action-tile h-100">
                                <div class="label">Level</div>
                                <div class="value"><?= (int)$student['level'] ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="action-tile h-100">
                                <div class="label">Admission Year</div>
                                <div class="value"><?= h($student['admission_year']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="dashboard-card card h-100">
                <div class="card-header">
                    <i class="bi bi-lightning-charge me-2"></i>Quick Actions
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="notifications.php" class="action-tile bg-primary bg-opacity-10">
                        <div class="label" style="color: #0066CC;">Alerts</div>
                        <div class="value"><i class="bi bi-bell me-2" style="color: #0066CC;"></i><span style="color: #0066CC;">View Notifications</span></div>
                    </a>
                    <a href="register_courses.php" class="action-tile">
                        <div class="label">Registration</div>
                        <div class="value"><i class="bi bi-journal-plus me-2"></i>Register Courses</div>
                    </a>
                    <a href="course_schedule.php" class="action-tile">
                        <div class="label">Schedule</div>
                        <div class="value"><i class="bi bi-calendar-event me-2"></i>View Course Schedule</div>
                    </a>
                    <a href="results.php" class="action-tile">
                        <div class="label">Results</div>
                        <div class="value"><i class="bi bi-file-earmark-bar-graph me-2"></i>Check Published Results</div>
                    </a>
                    <a href="transcript.php" class="action-tile">
                        <div class="label">Records</div>
                        <div class="value"><i class="bi bi-book me-2"></i>Download Transcript</div>
                    </a>
                    <a href="gpa_trends.php" class="action-tile">
                        <div class="label">Analytics</div>
                        <div class="value"><i class="bi bi-graph-up me-2"></i>View GPA Trends</div>
                    </a>
                    <a href="degree_progress.php" class="action-tile">
                        <div class="label">Progress</div>
                        <div class="value"><i class="bi bi-mortarboard me-2"></i>Degree Progress</div>
                    </a>
                    <a href="academic_standing.php" class="action-tile">
                        <div class="label">Standing</div>
                        <div class="value"><i class="bi bi-shield-check me-2"></i>Academic Standing</div>
                    </a>
                    <a href="profile.php" class="action-tile">
                        <div class="label">Account</div>
                        <div class="value"><i class="bi bi-person-circle me-2"></i>My Profile</div>
                    </a>
                    <a href="<?= BASE_URL ?>/swiftgrade_change_password.php" class="action-tile">
                        <div class="label">Security</div>
                        <div class="value"><i class="bi bi-key me-2"></i>Change Password</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <h4 class="section-heading mb-3"><i class="bi bi-graph-up me-2"></i>Semester Results Overview</h4>

    <?php if ($resultsSummary->num_rows === 0): ?>
        <div class="alert alert-info">No published results yet. Results will appear here after the approval workflow is complete.</div>
    <?php else: ?>
        <div class="dashboard-card card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Session</th><th>Semester</th><th>Courses</th><th>Credit Units</th><th>GPA</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php $resultsSummary->data_seek(0); while ($rs = $resultsSummary->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= h($rs['session_name']) ?></strong></td>
                            <td><span class="soft-badge"><i class="bi bi-calendar3"></i>Semester <?= (int)$rs['semester_number'] ?></span></td>
                            <td><?= (int)$rs['course_count'] ?></td>
                            <td><?= (int)$rs['total_cu'] ?></td>
                            <td><strong class="metric-accent"><?= number_format($rs['gpa'], 2) ?></strong></td>
                            <td><a href="results.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>View</a></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
