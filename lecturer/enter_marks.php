<?php
/* ================================================================
   LECTURER – Enter Marks for Course
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('lecturer', 'hod');

$userId = (int)$_SESSION['user_id'];
$courseId = (int)($_GET['course_id'] ?? 0);
$batchId = (int)($_GET['batch_id'] ?? 0);

if (!$courseId) {
    die('Course ID is required.');
}

/* Get course details */
$course = $conn->query(
    "SELECT c.*, d.name AS dept_name FROM courses c 
     LEFT JOIN departments d ON d.id = c.department_id 
     WHERE c.id = $courseId"
)->fetch_assoc();

if (!$course) {
    die('Course not found.');
}

/* Get or create result batch */
$sem = currentSemester($conn);
$semId = $sem ? (int)$sem['id'] : 0;

if (!$batchId) {
    $batch = $conn->query(
        "SELECT id, status FROM result_batches 
         WHERE course_id = $courseId AND semester_id = $semId AND lecturer_id = $userId"
    )->fetch_assoc();

    if ($batch) {
        $batchId = (int)$batch['id'];
    } else {
        // Create new batch
        $stmt = $conn->prepare(
            "INSERT INTO result_batches (course_id, semester_id, lecturer_id, status) 
             VALUES (?, ?, ?, 'draft')"
        );
        $stmt->bind_param('iii', $courseId, $semId, $userId);
        $stmt->execute();
        $batchId = $conn->insert_id;
        $batch = ['id' => $batchId, 'status' => 'draft'];
        $stmt->close();
    }
} else {
    $batch = $conn->query(
        "SELECT id, status FROM result_batches WHERE id = $batchId"
    )->fetch_assoc();
}

if (!$batch) {
    die('Cannot access batch.');
}

/* Handle form submission */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $studentIds = $_POST['student_id'] ?? [];
    $caScores = $_POST['ca_score'] ?? [];
    $examScores = $_POST['exam_score'] ?? [];

    $updateStmt = $conn->prepare(
        "INSERT INTO results (batch_id, student_id, course_id, semester_id, ca_score, exam_score, total_score, grade, grade_point, remark, entered_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE ca_score=VALUES(ca_score), exam_score=VALUES(exam_score), 
         total_score=VALUES(total_score), grade=VALUES(grade), grade_point=VALUES(grade_point), 
         remark=VALUES(remark), entered_by=VALUES(entered_by)"
    );

    $updated = 0;
    foreach ($studentIds as $idx => $studentId) {
        $studentId = (int)$studentId;
        $ca = (float)($caScores[$idx] ?? 0);
        $exam = (float)($examScores[$idx] ?? 0);
        
        // Validate scores
        $ca = max(0, min(30, $ca));
        $exam = max(0, min(70, $exam));
        $total = $ca + $exam;

        // Compute grade
        $gradeInfo = computeGrade($total, $conn);
        $grade = $gradeInfo['grade'];
        $gp = $gradeInfo['grade_point'];
        $remark = $gradeInfo['remark'] ?? '';

        $updateStmt->bind_param(
            'iiiiddddsss',
            $batchId, $studentId, $courseId, $semId,
            $ca, $exam, $total, $grade, $gp, $remark, $userId
        );

        if ($updateStmt->execute()) {
            $updated++;
        }
    }

    $msg = "✓ Grades saved for $updated student(s).";
}

/* Get enrolled students and their current grades */
$students = [];
$result = $conn->query(
    "SELECT DISTINCT s.id, u.full_name, cr.id AS reg_id,
            COALESCE(r.ca_score, 0) AS ca_score,
            COALESCE(r.exam_score, 0) AS exam_score,
            COALESCE(r.total_score, 0) AS total_score,
            COALESCE(r.grade, '-') AS grade,
            COALESCE(r.grade_point, 0) AS grade_point
     FROM course_registrations cr
     JOIN students s ON s.id = cr.student_id
     JOIN users u ON u.id = s.user_id
     LEFT JOIN results r ON r.batch_id = $batchId AND r.student_id = s.id
     WHERE cr.course_id = $courseId AND cr.semester_id = $semId
     ORDER BY u.full_name ASC"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .score-input {
        max-width: 80px;
        text-align: center;
        font-weight: 600;
    }
    .grade-cell {
        background: #E8F5EE;
        font-weight: 600;
        color: #006B3F;
    }
    .table-row-hover:hover {
        background: #F5F5F5;
    }
    .batch-status {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .badge-draft { background: #F3F4F6; color: #4B5563; }
    .badge-submitted { background: #DBEAFE; color: #0C4A6E; }
    .badge-approved { background: #DCFCE7; color: #166534; }
</style>

<div class="page-header">
    <div class="container">
        <h2><i class="bi bi-pencil-square"></i> Enter Marks</h2>
        <small><?= h($course['code']) ?> • <?= h($course['title']) ?></small>
    </div>
</div>

<div class="container pb-4">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-2"><?= h($course['code']) ?> - <?= h($course['title']) ?></h5>
                    <small class="text-muted">
                        <?= (int)$course['credit_units'] ?> Credit Units • 
                        Level <?= (int)$course['level'] ?> •
                        <?= h($course['dept_name']) ?>
                    </small>
                </div>
                <div class="col-auto">
                    <span class="batch-status badge-<?= strtolower($batch['status']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $batch['status'])) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= h($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($students)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            No students registered for this course yet.
        </div>
    <?php else: ?>
        <form method="POST" id="marksForm">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span class="fw-bold">
                        <i class="bi bi-list-ul"></i> 
                        Student Grades (<?= count($students) ?> students)
                    </span>
                    <small class="text-muted">
                        CA Score (max 30) + Exam Score (max 70) = Total (max 100)
                    </small>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th>Full Name</th>
                                <th style="width: 10%;">CA Score</th>
                                <th style="width: 10%;">Exam Score</th>
                                <th style="width: 10%;">Total</th>
                                <th style="width: 8%;" class="text-center">Grade</th>
                                <th style="width: 8%;" class="text-center">GP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $idx => $student): ?>
                                <tr class="table-row-hover">
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <input type="hidden" name="student_id[]" value="<?= $student['id'] ?>">
                                        <strong><?= h($student['full_name']) ?></strong>
                                    </td>
                                    <td>
                                        <input type="number" name="ca_score[]" class="form-control score-input" 
                                               min="0" max="30" step="0.5" value="<?= $student['ca_score'] ?>" 
                                               onchange="calculateRow(this)">
                                    </td>
                                    <td>
                                        <input type="number" name="exam_score[]" class="form-control score-input" 
                                               min="0" max="70" step="0.5" value="<?= $student['exam_score'] ?>" 
                                               onchange="calculateRow(this)">
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary p-2" data-total="0">
                                            <?= (int)$student['total_score'] ?>
                                        </span>
                                    </td>
                                    <td class="grade-cell text-center"><?= h($student['grade']) ?></td>
                                    <td class="text-center text-muted"><?= number_format($student['grade_point'], 1) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-light d-flex gap-2 justify-content-between">
                    <div>
                        <a href="my_courses.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </button>
                        <button type="submit" name="save_marks" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle"></i> Save Grades
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="card mt-4 border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="mb-3"><i class="bi bi-lightbulb"></i> Tips for Data Entry</h6>
                <ul class="mb-0" style="font-size: 0.95rem;">
                    <li><strong>CA Score:</strong> Continuous Assessment (max 30 points)</li>
                    <li><strong>Exam Score:</strong> Final Exam (max 70 points)</li>
                    <li><strong>Total:</strong> Automatically calculated (CA + Exam)</li>
                    <li><strong>Grade:</strong> Automatically assigned based on total score</li>
                    <li><strong>GP:</strong> Grade Point used for GPA calculation</li>
                    <li>You can update scores anytime until <strong>Submitted</strong></li>
                    <li>After submission, only HOD and Admin can modify</li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function calculateRow(input) {
    const row = input.closest('tr');
    const caInput = row.querySelector('input[name="ca_score[]"]');
    const examInput = row.querySelector('input[name="exam_score[]"]');
    const totalBadge = row.querySelector('[data-total]');
    
    const ca = parseFloat(caInput.value) || 0;
    const exam = parseFloat(examInput.value) || 0;
    const total = ca + exam;
    
    totalBadge.textContent = Math.round(total);
    
    // Update color based on total
    if (total >= 70) totalBadge.className = 'badge bg-success p-2';
    else if (total >= 50) totalBadge.className = 'badge bg-info p-2';
    else if (total >= 40) totalBadge.className = 'badge bg-warning p-2';
    else totalBadge.className = 'badge bg-danger p-2';
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="ca_score[]"], input[name="exam_score[]"]').forEach(input => {
        calculateRow(input);
    });
});

// Prevent form if no changes
document.getElementById('marksForm').addEventListener('submit', function(e) {
    if (!confirm('Are you sure? This will save all entered grades.')) {
        e.preventDefault();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
