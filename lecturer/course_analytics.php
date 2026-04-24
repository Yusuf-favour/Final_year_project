<?php
/* ================================================================
   LECTURER – Course Analytics & Insights
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

/* Get all batches and results for this course */
$batches = $conn->query(
    "SELECT rb.id, rb.status FROM result_batches rb
     WHERE rb.course_id = $courseId AND rb.semester_id = $semId
     ORDER BY rb.created_at DESC"
);

$allResults = [];
while ($batch = $batches->fetch_assoc()) {
    $batchResults = $conn->query(
        "SELECT r.total_score, r.grade FROM results r WHERE r.batch_id = " . (int)$batch['id']
    );
    while ($r = $batchResults->fetch_assoc()) {
        $allResults[] = $r;
    }
}

/* Calculate statistics */
$totalScores = [];
$gradeDistribution = [
    'A' => 0,
    'B' => 0,
    'C' => 0,
    'D' => 0,
    'E' => 0,
    'F' => 0
];

$strugglingStudents = [];

foreach ($allResults as $result) {
    $totalScores[] = (float)$result['total_score'];
    if (isset($gradeDistribution[$result['grade']])) {
        $gradeDistribution[$result['grade']]++;
    }
}

$classAverage = count($totalScores) > 0 ? array_sum($totalScores) / count($totalScores) : 0;
$sortedScores = $totalScores;
sort($sortedScores, SORT_NUMERIC);
$classMedian = count($sortedScores) > 0 ? $sortedScores[floor(count($sortedScores) / 2)] : 0;
$classMin = count($totalScores) > 0 ? min($totalScores) : 0;
$classMax = count($totalScores) > 0 ? max($totalScores) : 0;
$passCount = $gradeDistribution['A'] + $gradeDistribution['B'] + $gradeDistribution['C'] + 
             $gradeDistribution['D'] + $gradeDistribution['E'];
$totalCount = count($allResults);
$passRate = $totalCount > 0 ? ($passCount / $totalCount) * 100 : 0;
$failRate = $totalCount > 0 ? (($totalCount - $passCount) / $totalCount) * 100 : 0;

include __DIR__ . '/../includes/header.php';
?>

<style>
    .stat-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #006B3F;
    }
    .stat-label {
        color: #6B7280;
        font-size: 0.85rem;
        text-transform: uppercase;
    }
    .chart-container {
        position: relative;
        height: 300px;
        margin-bottom: 1rem;
    }
    .grade-bar {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        padding: 0.75rem;
        background: #F9FAFB;
        border-radius: 6px;
    }
    .grade-label {
        min-width: 40px;
        font-weight: 600;
    }
    .grade-bar-fill {
        flex: 1;
        margin: 0 1rem;
        height: 30px;
        background: #E5E7EB;
        border-radius: 4px;
        overflow: hidden;
    }
    .grade-bar-fill-inner {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 0.5rem;
        color: white;
        font-weight: 600;
        font-size: 0.85rem;
    }
</style>

<div class="page-header">
    <div class="container">
        <h2><i class="bi bi-graph-up"></i> Course Analytics</h2>
        <small><?= h($course['code'] . ' - ' . $course['title']) ?></small>
    </div>
</div>

<div class="container pb-5">
    <!-- Key Statistics -->
    <h5 class="mb-3"><i class="bi bi-lightning-fill"></i> Key Metrics</h5>
    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="stat-card card p-4">
                <div class="stat-label">Class Average</div>
                <div class="stat-value"><?= number_format($classAverage, 2) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card card p-4">
                <div class="stat-label">Pass Rate</div>
                <div class="stat-value"><?= number_format($passRate, 1) ?>%</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card card p-4">
                <div class="stat-label">Median Score</div>
                <div class="stat-value"><?= number_format($classMedian, 2) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card card p-4">
                <div class="stat-label">Students</div>
                <div class="stat-value"><?= $totalCount ?></div>
            </div>
        </div>
    </div>

    <!-- Grade Distribution -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-bar-chart"></i> Grade Distribution
                </div>
                <div class="card-body">
                    <?php foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $grade): 
                        $count = $gradeDistribution[$grade];
                        $percentage = $totalCount > 0 ? ($count / $totalCount) * 100 : 0;
                    ?>
                    <div class="grade-bar">
                        <div class="grade-label">
                            <span class="badge bg-<?php
                                if ($grade === 'A' || $grade === 'B') echo 'success';
                                elseif ($grade === 'C' || $grade === 'D') echo 'warning';
                                else echo 'danger';
                            ?>"><?= $grade ?></span>
                        </div>
                        <div class="grade-bar-fill">
                            <div class="grade-bar-fill-inner" style="width: <?= max(5, $percentage) ?>%; 
                                background: <?php
                                    if ($grade === 'A') echo '#16A34A';
                                    elseif ($grade === 'B') echo '#2563EB';
                                    elseif ($grade === 'C') echo '#F59E0B';
                                    elseif ($grade === 'D') echo '#EF9A0F';
                                    else echo '#EF4444';
                                ?>;">
                                <?= $count > 0 ? $count : '' ?>
                            </div>
                        </div>
                        <div style="min-width: 50px; text-align: right;">
                            <?= number_format($percentage, 1) ?>%
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Range Statistics -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light fw-bold">
                    <i class="bi bi-speedometer"></i> Score Range
                </div>
                <div class="card-body">
                    <div class="mb-4 pb-4 border-bottom">
                        <small class="text-muted">Highest Score</small>
                        <p class="display-6 mb-0" style="color: #16A34A;">
                            <?= number_format($classMax, 2) ?>
                        </p>
                    </div>

                    <div class="mb-4 pb-4 border-bottom">
                        <small class="text-muted">Lowest Score</small>
                        <p class="display-6 mb-0" style="color: #EF4444;">
                            <?= number_format($classMin, 2) ?>
                        </p>
                    </div>

                    <div class="mb-4 pb-4 border-bottom">
                        <small class="text-muted">Score Spread</small>
                        <p class="display-6 mb-0" style="color: #006B3F;">
                            <?= number_format($classMax - $classMin, 2) ?>
                        </p>
                    </div>

                    <div>
                        <small class="text-muted">Performance</small>
                        <p class="mb-0">
                            <strong><?= $passCount ?></strong> students passed<br>
                            <strong><?= $totalCount - $passCount ?></strong> students failed
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-5">
        <a href="my_courses.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Courses
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
