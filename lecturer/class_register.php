<?php
/* ================================================================
   LECTURER – Class Register (Attendance)
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('lecturer', 'hod');

$userId = (int)$_SESSION['user_id'];
$courseId = (int)($_GET['course_id'] ?? 0);

if (!$courseId) {
    die('Course ID required.');
}

/* Get course */
$course = $conn->query(
    "SELECT c.* FROM courses c WHERE c.id = $courseId"
)->fetch_assoc();

if (!$course) {
    die('Course not found.');
}

$sem = currentSemester($conn);
$semId = $sem ? (int)$sem['id'] : 0;

/* Ensure attendance table exists in deployments where migration was not run */
if ($conn) {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            student_id INT NOT NULL,
            semester_id INT NOT NULL,
            attendance_date DATE NOT NULL,
            is_present TINYINT(1) NOT NULL DEFAULT 0,
            lecturer_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_attendance (course_id, student_id, attendance_date),
            INDEX idx_attendance_date (attendance_date),
            INDEX idx_attendance_course_semester (course_id, semester_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

if ($semId <= 0) {
    include __DIR__ . '/../includes/header.php';
    echo '<div class="container py-4"><div class="alert alert-warning">No active semester is configured. Please contact admin.</div><a href="my_courses.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

/* Handle attendance submission */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $date = $_POST['attendance_date'] ?? date('Y-m-d');
    $studentIds = $_POST['student_id'] ?? [];
    $attendance = $_POST['attendance'] ?? [];

    $stmt = $conn->prepare(
        "INSERT INTO attendance (course_id, student_id, semester_id, attendance_date, is_present, lecturer_id)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE is_present = VALUES(is_present)"
    );

    $saved = 0;
    foreach ($studentIds as $studentId) {
        $studentId = (int)$studentId;
        $isPresent = isset($attendance[$studentId]) ? 1 : 0;

        $stmt->bind_param(
            'iiisii',
            $courseId, $studentId, $semId, $date, $isPresent, $userId
        );

        if ($stmt->execute()) {
            $saved++;
        }
    }

    $msg = "✓ Attendance saved for $saved student(s) on " . formatDate($date, 'M d, Y');
}

/* Get students enrolled in course */
$students = $conn->query(
    "SELECT s.id, u.full_name, cr.id AS reg_id,
            COALESCE(att.is_present, 0) AS present_count,
            (SELECT COUNT(*) FROM attendance 
             WHERE course_id = $courseId AND student_id = s.id AND semester_id = $semId) AS total_attendance
     FROM course_registrations cr
     JOIN students s ON s.id = cr.student_id
     JOIN users u ON u.id = s.user_id
     LEFT JOIN attendance att ON att.course_id = cr.course_id AND att.student_id = s.id 
             AND att.attendance_date = CURDATE() AND att.semester_id = $semId
     WHERE cr.course_id = $courseId AND cr.semester_id = $semId
     ORDER BY u.full_name"
);

$classSize = $students ? $students->num_rows : 0;

include __DIR__ . '/../includes/header.php';
?>

<style>
    .attendance-card {
        border: 2px solid #E8F5EE;
        border-radius: 10px;
        padding: 1.5rem;
        transition: all 0.3s;
    }
    .attendance-card:hover {
        border-color: #006B3F;
        background: #F9FAFB;
    }
    .attendance-checkbox {
        width: 1.5rem;
        height: 1.5rem;
        cursor: pointer;
        accent-color: #006B3F;
    }
</style>

<div class="page-header">
    <div class="container">
        <h2><i class="bi bi-clipboard-check"></i> Class Register</h2>
        <small><?= h($course['code']) ?> • Class Attendance Tracking</small>
    </div>
</div>

<div class="container pb-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <form method="POST">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">
                                <i class="bi bi-calendar3"></i> Attendance Date
                            </span>
                            <input type="date" name="attendance_date" class="form-control" style="width: 200px;" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($msg): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?= h($msg) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($classSize === 0): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                No students registered for this course yet.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php 
                                $index = 1;
                                $students->data_seek(0);
                                while ($student = $students->fetch_assoc()): 
                                ?>
                                <label class="list-group-item p-3">
                                    <div class="d-flex align-items-center gap-3">
                                         <input type="checkbox" name="attendance[<?= (int)$student['id'] ?>]" class="attendance-checkbox" 
                                             value="1" 
                                               <?= (int)$student['present_count'] ? 'checked' : '' ?>>
                                        <input type="hidden" name="student_id[]" value="<?= $student['id'] ?>">
                                        
                                        <div class="flex-grow-1">
                                            <div class="fw-bold"><?= h($student['full_name']) ?></div>
                                            <small class="text-muted">
                                                <?= (int)$student['total_attendance'] ?> classes attended
                                            </small>
                                        </div>
                                        
                                        <div class="text-end">
                                            <span class="badge bg-light text-dark">
                                                Attendance: <?= (int)$student['total_attendance'] ?>
                                            </span>
                                        </div>
                                    </div>
                                </label>
                                <?php $index++; endwhile; ?>
                            </div>

                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex justify-content-between">
                                    <a href="my_courses.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back
                                    </a>
                                    <button type="submit" name="save_attendance" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Save Attendance
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-graph-up"></i> Attendance Summary
                </div>
                <div class="card-body">
                    <div class="mb-3 p-3 rounded bg-light text-center">
                        <div class="h3 mb-1" style="color: #006B3F;"><?= $classSize ?></div>
                        <small class="text-muted">Total Students</small>
                    </div>

                    <hr>

                    <p class="mb-2"><strong>Quick Actions:</strong></p>
                    <button onclick="checkAll()" class="btn btn-sm btn-outline-success w-100 mb-2">
                        <i class="bi bi-check-all"></i> Mark All Present
                    </button>
                    <button onclick="uncheckAll()" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-dash-circle"></i> Mark All Absent
                    </button>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-info-circle"></i> About Attendance
                </div>
                <div class="card-body" style="font-size: 0.9rem;">
                    <p class="mb-2">
                        Track daily attendance for each student in this course.
                    </p>
                    <ul class="mb-0">
                        <li>Check the boxes for present students</li>
                        <li>Unchecked = Absent</li>
                        <li>Records are saved per date</li>
                        <li>You can update past dates</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checkAll() {
    document.querySelectorAll('.attendance-checkbox').forEach(cb => cb.checked = true);
}
function uncheckAll() {
    document.querySelectorAll('.attendance-checkbox').forEach(cb => cb.checked = false);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
