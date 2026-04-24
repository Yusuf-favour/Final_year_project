<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$userId = currentUserId();
$error = '';
$success = '';
$studentInfo = [];
$totalCreditUnits = 0;

// Get student information (use correct column names)
$studentRes = $conn->query("SELECT s.matric_no, u.full_name, s.admission_year, p.name as program_name, d.name as department_name, s.level 
                           FROM students s 
                           JOIN users u ON u.id = s.user_id 
                           JOIN programs p ON p.id = s.program_id 
                           JOIN departments d ON d.id = s.department_id 
                           WHERE s.user_id = $userId");
if ($studentRes && $studentRes->num_rows > 0) {
    $studentInfo = $studentRes->fetch_assoc();
}

// Fetch all semesters (joined with session)
$semesters = $conn->query("SELECT sem.id, a.session_name, sem.semester_number FROM semesters sem 
                           JOIN academic_sessions a ON a.id = sem.session_id 
                           ORDER BY a.session_name DESC, sem.semester_number");

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semesterId = (int)($_POST['semester_id'] ?? 0);
    $selectedCourses = $_POST['courses'] ?? [];

    if ($semesterId) {
        // Validate semester exists
        $semCheck = $conn->query("SELECT id FROM semesters WHERE id = $semesterId");
        if ($semCheck && $semCheck->num_rows > 0) {
            // Get student ID from user ID
            $studentIdRes = $conn->query("SELECT id FROM students WHERE user_id = $userId");
            $studentIdRow = $studentIdRes->fetch_assoc();
            $studentId = (int)$studentIdRow['id'];
            
            // Remove previous registrations for this student/semester
            $conn->query("DELETE FROM course_registrations WHERE student_id = $studentId AND semester_id = $semesterId");
            
            // Insert new registrations using prepared statements
            $stmt = $conn->prepare("INSERT INTO course_registrations (student_id, course_id, semester_id) VALUES (?, ?, ?)");
            $insertedCount = 0;
            
            foreach ($selectedCourses as $courseId) {
                $courseId = (int)$courseId;
                $stmt->bind_param('iii', $studentId, $courseId, $semesterId);
                if ($stmt->execute()) {
                    $insertedCount++;
                }
            }
            $stmt->close();

            $autoInserted = autoRegisterOutstandingCourses($conn, $studentId, $semesterId);
            
            if (($insertedCount + $autoInserted) > 0) {
                $success = "✅ Registration updated. $insertedCount selected course(s) saved";
                if ($autoInserted > 0) {
                    $success .= " and $autoInserted outstanding course(s) were auto-registered.";
                } else {
                    $success .= ".";
                }
            } else {
                $error = 'No courses were registered. Please select at least one course.';
            }
        } else {
            $error = 'Invalid semester selected.';
        }
    } else {
        $error = 'Please select a semester.';
    }
}

// Fetch courses for selected semester
$availableCourses = [];
$registeredCourses = [];
$selectedSemesterId = $_POST['semester_id'] ?? $_GET['semester_id'] ?? null;

if (!empty($selectedSemesterId)) {
    $selectedSemesterId = (int)$selectedSemesterId;
    
    // Get semester info to find which semester number
    $semInfoRes = $conn->query("SELECT semester_number FROM semesters WHERE id = $selectedSemesterId");
    $semInfoRow = $semInfoRes->fetch_assoc();
    $semesterNumber = (int)($semInfoRow['semester_number'] ?? 1);
    
    // Get student level
    $studentLevel = (int)($studentInfo['level'] ?? 100);
    
    // Get student ID for fetching registered courses
    $studentIdRes = $conn->query("SELECT id FROM students WHERE user_id = $userId");
    $studentIdRow = $studentIdRes->fetch_assoc();
    $studentId = (int)$studentIdRow['id'];

        // Keep all outstanding failed courses automatically registered in the selected semester.
        autoRegisterOutstandingCourses($conn, $studentId, $selectedSemesterId);

    // Get courses for this semester and student level, plus any already registered carry-over courses
    $courseQuery = "SELECT DISTINCT c.id, c.code, c.title, COALESCE(c.credit_units, c.unit, 0) AS credit_units, c.level, c.semester AS original_semester, d.name as department_name
                    FROM courses c
                    JOIN departments d ON d.id = c.department_id
                    LEFT JOIN course_registrations cr ON cr.course_id = c.id
                        AND cr.student_id = $studentId
                        AND cr.semester_id = $selectedSemesterId
                                        WHERE ((c.level = $studentLevel AND c.semester = $semesterNumber) OR cr.id IS NOT NULL)
                    ORDER BY c.code ASC";
    
    $availableCourses = $conn->query($courseQuery);
    
    // Fetch registered courses for this student/semester
    $regRes = $conn->query("SELECT course_id FROM course_registrations WHERE student_id = $studentId AND semester_id = $selectedSemesterId");
    if ($regRes) {
        while ($row = $regRes->fetch_assoc()) {
            $registeredCourses[] = $row['course_id'];
        }
    }
    
    // Calculate total credit units for registered courses
    if (!empty($registeredCourses)) {
        $ids = implode(',', array_map('intval', $registeredCourses));
        $cuRes = $conn->query("SELECT SUM(credit_units) as total_cu FROM courses WHERE id IN ($ids)");
        if ($cuRes) {
            $cuRow = $cuRes->fetch_assoc();
            $totalCreditUnits = $cuRow['total_cu'] ?? 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Course Registration Portal - Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/modern-dashboard.css" rel="stylesheet">
    <style>
        :root {
            --kofa-primary: #006B3F;
            --kofa-success: #16A34A;
            --kofa-light: #E8F5EE;
            --kofa-dark: #004D2C;
            --kofa-danger: #EF4444;
            --kofa-warning: #FBBF24;
            --text-primary: #1F2937;
            --text-secondary: #6B7280;
            --border-color: #E5E7EB;
            --bg-light: #F9FAFB;
        }

        * {
            --primary: var(--kofa-primary);
            --primary-dark: var(--kofa-dark);
        }

        body {
            background: linear-gradient(135deg, var(--bg-light) 0%, #E8F5E9 100%);
            color: var(--text-primary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* KOFA Header */
        .kofa-header {
            background: linear-gradient(135deg, var(--kofa-primary) 0%, var(--kofa-dark) 100%);
            color: white;
            padding: 2rem 0;
            box-shadow: 0 4px 12px rgba(0,0,0, 0.15);
            margin-bottom: 2rem;
        }

        .kofa-header h1 {
            font-weight: 700;
            font-size: 2rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .kofa-header .breadcrumb {
            background: transparent;
            margin-top: 1rem;
            padding: 0;
        }

        .kofa-header .breadcrumb-item {
            color: rgba(255,255,255, 0.8);
        }

        .kofa-header .breadcrumb-item.active {
            color: white;
            font-weight: 600;
        }

        /* Student Info Card */
        .student-info-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0, 0.06);
        }

        .student-info-card h6 {
            color: var(--kofa-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .student-info-card p {
            margin: 0;
            font-size: 1rem;
            color: var(--text-primary);
        }

        /* Registration Form */
        .registration-panel {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0, 0.06);
            margin-bottom: 2rem;
        }

        .registration-panel h4 {
            color: var(--kofa-primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }

        .form-select, .form-control {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--kofa-primary);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
            outline: none;
        }

        /* Course Selection Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .course-card {
            background: #FAFAFA;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 1.25rem;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .course-card:hover {
            border-color: var(--kofa-primary);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.15);
            transform: translateY(-2px);
        }

        .course-card.selected {
            background: var(--kofa-light);
            border-color: var(--kofa-primary);
        }

        .course-card input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.75rem;
            cursor: pointer;
            accent-color: var(--kofa-primary);
        }

        .course-card label {
            cursor: pointer;
            margin: 0;
            flex: 1;
        }

        .course-code {
            font-weight: 700;
            color: var(--kofa-primary);
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .course-title {
            font-weight: 600;
            color: var(--text-primary);
            margin: 0.5rem 0;
            font-size: 0.95rem;
        }

        .course-credits {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
        }

        .course-credits strong {
            color: var(--kofa-primary);
        }

        /* Registered Courses Summary */
        .summary-panel {
            background: linear-gradient(135deg, var(--kofa-light) 0%, #E8F5E9 100%);
            border: 2px solid var(--kofa-primary);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
        }

        .summary-panel h5 {
            color: var(--kofa-dark);
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-box {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid var(--kofa-primary);
        }

        .stat-box-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-weight: 600;
        }

        .stat-box-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--kofa-primary);
            margin-top: 0.5rem;
        }

        .registered-courses-list {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .registered-courses-list table {
            margin: 0;
        }

        .registered-courses-list thead {
            background: var(--kofa-light);
        }

        .registered-courses-list th {
            color: var(--kofa-dark);
            font-weight: 700;
            border: none;
            padding: 1rem;
        }

        .registered-courses-list td {
            padding: 0.875rem 1rem;
            border-color: var(--border-color);
        }

        .registered-courses-list tbody tr:hover {
            background: var(--bg-light);
        }

        /* Alert Styling */
        .alert-success {
            background: var(--kofa-light);
            border: 2px solid var(--kofa-primary);
            color: var(--kofa-dark);
            border-radius: 8px;
            padding: 1rem 1.5rem;
        }

        .alert-danger {
            background: #FEE2E2;
            border: 2px solid #DC2626;
            color: #7F1D1D;
            border-radius: 8px;
            padding: 1rem 1.5rem;
        }

        /* Action Buttons */
        .btn-register {
            background: linear-gradient(135deg, var(--kofa-primary) 0%, var(--kofa-success) 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .btn-register:hover {
            background: linear-gradient(135deg, var(--kofa-dark) 0%, var(--kofa-primary) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary-green {
            background: white;
            border: 2px solid var(--kofa-primary);
            color: var(--kofa-primary);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-secondary-green:hover {
            background: var(--kofa-primary);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            background: white;
            border-radius: 12px;
            border: 2px dashed var(--border-color);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            display: block;
        }

        .empty-state p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .kofa-header h1 {
                font-size: 1.5rem;
            }

            .courses-grid {
                grid-template-columns: 1fr;
            }

            .student-info-card {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }

            .registration-panel {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- KOFA Header -->
<div class="kofa-header">
    <div class="container">
        <h1><i class="bi bi-pencil-square"></i> Course Registration Portal</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" style="color: rgba(255,255,255, 0.8);">Dashboard</a></li>
                <li class="breadcrumb-item active">Register Courses</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container" style="padding-bottom: 3rem;">

    <!-- Student Information -->
    <?php if ($studentInfo): ?>
    <div class="row g-2 mb-4">
        <div class="col-md-3">
            <div class="student-info-card">
                <h6><i class="bi bi-person"></i> Full Name</h6>
                <p><?= htmlspecialchars($studentInfo['full_name']) ?></p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="student-info-card">
                <h6><i class="bi bi-card-text"></i> Matric Number</h6>
                <p><?= htmlspecialchars($studentInfo['matric_no']) ?></p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="student-info-card">
                <h6><i class="bi bi-building"></i> Program</h6>
                <p><?= htmlspecialchars($studentInfo['program_name']) ?></p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="student-info-card">
                <h6><i class="bi bi-graph-up"></i> Level</h6>
                <p><?= (int)$studentInfo['level'] ?>/400</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alerts -->
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Registration Form -->
    <form method="POST" id="courseRegistrationForm">
        <div class="registration-panel">
            <h4><i class="bi bi-calendar-event"></i> Select Semester</h4>
            
            <div class="mb-3">
                <label for="semesterSelect" class="form-label">Academic Session & Semester</label>
                <select name="semester_id" id="semesterSelect" class="form-select" required onchange="loadCourses()">
                    <option value="">-- Select an academic session --</option>
                    <?php 
                    if ($semesters && $semesters->num_rows > 0):
                        $semesters->data_seek(0);
                        while ($sem = $semesters->fetch_assoc()): 
                    ?>
                    <option value="<?= $sem['id'] ?>" <?= (isset($_POST['semester_id']) && $_POST['semester_id'] == $sem['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sem['session_name']) ?> - Semester <?= $sem['semester_number'] ?>
                    </option>
                    <?php 
                        endwhile;
                    endif;
                    ?>
                </select>
                <small class="text-secondary">Select your academic session and semester to view available courses.</small>
            </div>
        </div>

        <!-- Courses Selection -->
        <?php if ($availableCourses && $availableCourses->num_rows > 0): ?>
            <div class="registration-panel">
                <h4><i class="bi bi-book-fill"></i> Available Courses (<?= $availableCourses->num_rows ?>)</h4>
                
                <div class="courses-grid" id="coursesGrid">
                    <?php 
                    $availableCourses->data_seek(0);
                    while ($course = $availableCourses->fetch_assoc()): 
                        $isChecked = in_array($course['id'], $registeredCourses);
                    ?>
                    <div class="course-card <?= $isChecked ? 'selected' : '' ?>" onclick="toggleCourse(event, this)">
                        <div style="display: flex; align-items: flex-start;">
                            <input type="checkbox" name="courses[]" value="<?= $course['id'] ?>" 
                                   id="course<?= $course['id'] ?>" class="form-check-input mt-0" <?= $isChecked ? 'checked' : '' ?>>
                            <label for="course<?= $course['id'] ?>" style="flex: 1; margin-left: 0.75rem;">
                                <div class="course-code"><?= htmlspecialchars($course['code']) ?></div>
                                <div class="course-title"><?= htmlspecialchars($course['title']) ?></div>
                                <?php if ($course['original_semester'] != $semesterNumber): ?>
                                    <small class="badge rounded-pill bg-warning text-dark ms-0" style="font-size:0.75rem;">Carry-over course</small>
                                <?php endif; ?>
                                <div class="course-credits">
                                    <strong><?= (int)$course['credit_units'] ?></strong> Credit Units
                                </div>
                            </label>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="d-flex gap-2 mt-3 justify-content-between align-items-center">
                    <div>
                        <button type="submit" class="btn btn-register">
                            <i class="bi bi-check-circle"></i> Register Selected Courses
                        </button>
                        <button type="reset" class="btn btn-secondary-green ms-2">
                            <i class="bi bi-arrow-clockwise"></i> Clear Selection
                        </button>
                    </div>
                    <small class="text-secondary">Selected: <strong id="selectedCount">0</strong> course(s)</small>
                </div>
            </div>
        <?php endif; ?>

        <!-- Summary Panel -->
        <?php if (!empty($registeredCourses) && $availableCourses && $availableCourses->num_rows > 0): ?>
            <div class="summary-panel">
                <h5><i class="bi bi-check2-circle"></i> Your Registered Courses</h5>
                
                <div class="summary-stats">
                    <div class="stat-box">
                        <div class="stat-box-label">Total Courses</div>
                        <div class="stat-box-value"><?= count($registeredCourses) ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-label">Total CU</div>
                        <div class="stat-box-value"><?= $totalCreditUnits ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-box-label">Status</div>
                        <div style="margin-top: 0.5rem;">
                            <span class="badge" style="background: var(--kofa-success); font-size: 0.9rem;">Active</span>
                        </div>
                    </div>
                </div>

                <?php
                // Fetch course details for registered courses
                $ids = implode(',', array_map('intval', $registeredCourses));
                $regCoursesRes = $conn->query("SELECT code, title, credit_units FROM courses WHERE id IN ($ids) ORDER BY code ASC");
                ?>
                <div class="registered-courses-list">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-bookmark"></i> Course Code</th>
                                <th>Title</th>
                                <th style="text-align: right;"><i class="bi bi-collection"></i> CU</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($rc = $regCoursesRes->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($rc['code']) ?></strong></td>
                                <td><?= htmlspecialchars($rc['title']) ?></td>
                                <td style="text-align: right;"><span class="badge" style="background: var(--kofa-primary);"><?= (int)$rc['credit_units'] ?> CU</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($selectedSemesterId && (!$availableCourses || $availableCourses->num_rows == 0)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>No courses available for your current level in this semester.</p>
                <small class="text-secondary">Please select a different semester or contact the registrar's office.</small>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleCourse(event, cardElement) {
        const checkbox = cardElement.querySelector('input[type="checkbox"]');

        if (!checkbox) {
            return;
        }

        // Let the browser handle direct checkbox and label clicks.
        if (event.target === checkbox || event.target.closest('label')) {
            return;
        }

        checkbox.checked = !checkbox.checked;
        syncCourseCard(cardElement, checkbox.checked);
        updateSelectedCount();
    }

    function syncCourseCard(cardElement, isSelected) {
        cardElement.classList.toggle('selected', isSelected);
    }

    function updateSelectedCount() {
        const selected = document.querySelectorAll('input[name="courses[]"]:checked').length;
        document.getElementById('selectedCount').textContent = selected;
    }

    function loadCourses() {
        const semesterId = document.getElementById('semesterSelect').value;
        if (semesterId) {
            document.getElementById('courseRegistrationForm').submit();
        }
    }

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.course-card input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const cardElement = checkbox.closest('.course-card');
                if (cardElement) {
                    syncCourseCard(cardElement, checkbox.checked);
                }
                updateSelectedCount();
            });

            const cardElement = checkbox.closest('.course-card');
            if (cardElement) {
                syncCourseCard(cardElement, checkbox.checked);
            }
        });

        updateSelectedCount();
    });
</script>

</body>
</html>
