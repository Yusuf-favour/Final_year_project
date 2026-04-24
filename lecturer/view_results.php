<?php
/* ================================================================
   LECTURER – View & Edit Course Results
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('lecturer', 'hod');

$userId = (int)$_SESSION['user_id'];
$batchId = (int)($_GET['batch_id'] ?? 0);

if (!$batchId) {
    die('Batch ID required.');
}

/* Get batch */
$batch = $conn->query(
    "SELECT rb.*, c.code, c.title, c.credit_units, u.full_name AS lecturer_name
     FROM result_batches rb
     JOIN courses c ON c.id = rb.course_id
     JOIN users u ON u.id = rb.lecturer_id
     WHERE rb.id = $batchId"
)->fetch_assoc();

if (!$batch) {
    die('Batch not found.');
}

if ($batch['lecturer_id'] != $userId && !hasRole('hod')) {
    die('Access denied.');
}

$msg = '';
$msgType = '';

/* Handle grade update */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grade'])) {
    $resultId = (int)$_POST['result_id'];
    $score = (float)$_POST['score'];
    
    if ($score < 0 || $score > 100) {
        $msg = 'Score must be between 0 and 100.';
        $msgType = 'danger';
    } else {
        $grade = computeGrade($score, $conn);
        
        $stmt = $conn->prepare(
            "UPDATE results SET total_score = ?, grade = ?, grade_point = ? WHERE id = ?"
        );
        $stmt->bind_param('dsdi', $score, $grade['grade'], $grade['grade_point'], $resultId);
        
        if ($stmt->execute()) {
            $msg = '✓ Grade updated successfully.';
            $msgType = 'success';
        } else {
            $msg = 'Error updating grade.';
            $msgType = 'danger';
        }
        $stmt->close();
    }
}

/* Get all results for this batch */
$results = $conn->query(
    "SELECT r.id, r.batch_id, r.student_id, r.total_score, r.grade, r.grade_point, r.remark, 
            u.full_name, s.matric_no
     FROM results r
     JOIN students s ON s.id = r.student_id
     JOIN users u ON u.id = s.user_id
     WHERE r.batch_id = $batchId
     ORDER BY u.full_name ASC"
);

$rows = [];
$totalScore = 0;
$passCount = 0;
if ($results && $results->num_rows > 0) {
    while ($row = $results->fetch_assoc()) {
        $rows[] = $row;
        $score = isset($row['total_score']) ? (float)$row['total_score'] : 0;
        $totalScore += $score;
        if (isset($row['grade']) && $row['grade'] !== 'F') {
            $passCount++;
        }
    }
}

$classAverage = count($rows) > 0 ? round($totalScore / count($rows), 2) : 0;
$passRate = count($rows) > 0 ? round(($passCount / count($rows)) * 100, 1) : 0;

include __DIR__ . '/../includes/header.php';
?>

<style>
    .result-table {
        font-size: 0.95rem;
    }
    .grade-cell {
        font-weight: 600;
        min-width: 50px;
    }
    .grade-a { color: #16A34A; }
    .grade-b { color: #2563EB; }
    .grade-c { color: #F59E0B; }
    .grade-f { color: #EF4444; }
    .batch-header {
        background: #F3F4F6;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 2rem;
    }
</style>

<div class="page-header">
    <div class="container">
        <h2><i class="bi bi-file-earmark-bar-graph"></i> Course Results</h2>
        <small><?= h($batch['code'] . ' - ' . $batch['title']) ?></small>
    </div>
</div>

<div class="container pb-5">
    <!-- Batch Info -->
    <div class="batch-header">
        <div class="row g-4">
            <div class="col-md-3">
                <small class="text-muted">Course Code</small>
                <p class="mb-0 fw-bold"><?= h($batch['code']) ?></p>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Status</small>
                <p class="mb-0">
                    <span class="badge bg-<?php
                        if ($batch['status'] === 'published') echo 'success';
                        elseif ($batch['status'] === 'hod_approved') echo 'info';
                        elseif ($batch['status'] === 'submitted') echo 'warning';
                        elseif ($batch['status'] === 'rejected') echo 'danger';
                        else echo 'secondary';
                    ?>">
                        <?= ucfirst(str_replace('_', ' ', $batch['status'])) ?>
                    </span>
                </p>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Lecturer</small>
                <p class="mb-0 fw-bold"><?= h($batch['lecturer_name']) ?></p>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Credit Units</small>
                <p class="mb-0 fw-bold"><?= (int)$batch['credit_units'] ?></p>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show mb-4">
            <?= h($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6" style="color: #006B3F;">
                        <?= number_format($classAverage, 2) ?>
                    </div>
                    <small class="text-muted">Class Average</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6" style="color: #16A34A;">
                        <?= $passRate ?>%
                    </div>
                    <small class="text-muted">Pass Rate</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6">
                        <?= count($rows) ?>
                    </div>
                    <small class="text-muted">Students</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="display-6" style="color: #EF4444;">
                        <?= count($rows) - $passCount ?>
                    </div>
                    <small class="text-muted">Failed</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light fw-bold">
            <i class="bi bi-list-check"></i> Student Results
        </div>
        <div class="table-responsive">
            <table class="table table-hover result-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Matric No.</th>
                        <th>Student Name</th>
                        <th>Score</th>
                        <th>Grade</th>
                        <th>Grade Point</th>
                        <th>Remark</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): 
                        $score = isset($row['total_score']) ? (float)$row['total_score'] : 0;
                        $grade = isset($row['grade']) ? $row['grade'] : '—';
                        $gradePoint = isset($row['grade_point']) ? (float)$row['grade_point'] : 0;
                    ?>
                    <tr>
                        <td><code><?= h($row['matric_no'] ?? '—') ?></code></td>
                        <td><?= h($row['full_name'] ?? '—') ?></td>
                        <td><?= number_format($score, 2) ?></td>
                        <td>
                            <span class="grade-cell grade-<?= strtolower($grade) ?>">
                                <?= h($grade) ?>
                            </span>
                        </td>
                        <td><?= number_format($gradePoint, 2) ?></td>
                        <td><small><?= h($row['remark'] ?? '—') ?></small></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#editModal" 
                                    onclick="editGrade(<?= (int)($row['id'] ?? 0) ?>, <?= $score ?>)">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-4">
        <a href="my_courses.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Courses
        </a>
    </div>
</div>

<!-- Edit Grade Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Grade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="result_id" id="result_id">
                    <div class="mb-3">
                        <label class="form-label">Score (0-100)</label>
                        <input type="number" name="score" id="score" class="form-control" 
                               min="0" max="100" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_grade" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editGrade(resultId, score) {
    document.getElementById('result_id').value = resultId;
    document.getElementById('score').value = score;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
