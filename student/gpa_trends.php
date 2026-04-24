<?php
/* ================================================================
   STUDENT – Academic Performance & GPA Trends
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('student');

$userId = (int)$_SESSION['user_id'];

/* Get student info */
$student = $conn->query(
    "SELECT s.*, p.name AS program_name FROM students s
     JOIN programs p ON p.id = s.program_id WHERE s.user_id = $userId"
)->fetch_assoc();

$studentId = (int)$student['id'];

/* Get all results by semester */
$semesters = $conn->query(
    "SELECT DISTINCT sem.id, sem.semester_number, a.session_name,
            ROUND(SUM(r.grade_point * c.credit_units) / NULLIF(SUM(c.credit_units),0), 2) AS gpa,
            COUNT(r.id) AS course_count,
            SUM(CASE WHEN r.grade='F' THEN 1 ELSE 0 END) AS fail_count,
            SUM(c.credit_units) AS total_cu
     FROM results r
     JOIN result_batches rb ON rb.id = r.batch_id
     JOIN courses c ON c.id = r.course_id
     JOIN semesters sem ON sem.id = r.semester_id
     JOIN academic_sessions a ON a.id = sem.session_id
     WHERE r.student_id = $studentId AND rb.status = 'published'
     GROUP BY sem.id, sem.semester_number, a.session_name
     ORDER BY a.session_name DESC, sem.semester_number DESC"
);

/* Get overall CGPA */
$cgpaData = $conn->query(
    "SELECT ROUND(SUM(r.grade_point * c.credit_units) / NULLIF(SUM(c.credit_units),0), 2) AS cgpa
     FROM results r
     JOIN result_batches rb ON rb.id = r.batch_id
     JOIN courses c ON c.id = r.course_id
     WHERE r.student_id = $studentId AND rb.status = 'published'"
)->fetch_assoc();

$cgpa = (float)($cgpaData['cgpa'] ?? 0);

/* Calculate GPA trend */
$semesters->data_seek(0);
$gpaTrend = [];
while ($sem = $semesters->fetch_assoc()) {
    $gpaTrend[] = [
        'label' => $sem['session_name'] . ' S' . $sem['semester_number'],
        'gpa' => (float)$sem['gpa'],
        'semester_id' => $sem['id']
    ];
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .gpa-card {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .gpa-value {
        font-size: 3rem;
        font-weight: 700;
        color: #006B3F;
    }
    .semester-row {
        padding: 1rem;
        border-bottom: 1px solid #E5E7EB;
        transition: all 0.3s ease;
    }
    .semester-row:hover {
        background: #F9FAFB;
    }
    .semester-row:last-child {
        border-bottom: none;
    }
    .gpa-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        min-width: 80px;
        text-align: center;
    }
    .gpa-excellent { background: #D1FAE5; color: #065F46; }
    .gpa-good { background: #DBEAFE; color: #0C4A6E; }
    .gpa-fair { background: #FEF3C7; color: #78350F; }
    .gpa-poor { background: #FECACA; color: #7F1D1D; }
</style>

<div class="page-header">
    <div class="container">
        <h2><i class="bi bi-graph-up"></i> GPA & Academic Trends</h2>
        <small>Performance analysis and trend visualization</small>
    </div>
</div>

<div class="container pb-5">
    <!-- Current CGPA -->
    <div class="row g-4 mb-5">
        <div class="col-lg-4">
            <div class="gpa-card card p-5 text-center">
                <small class="text-muted text-uppercase mb-2">Overall CGPA</small>
                <div class="gpa-value"><?= number_format($cgpa, 2) ?></div>
                <small class="text-muted d-block mt-2">
                    <?php 
                    if ($cgpa >= 4.0) echo '🌟 1st Class Honours';
                    elseif ($cgpa >= 3.5) echo '⭐ 2nd Class Upper';
                    elseif ($cgpa >= 2.4) echo '✓ 2nd Class Lower';
                    elseif ($cgpa >= 1.5) echo '✓ 3rd Class';
                    elseif ($cgpa >= 1.0) echo '✓ Pass';
                    else echo 'Below Pass';
                    ?>
                </small>
            </div>
        </div>

        <!-- Performance Summary -->
        <div class="col-lg-8">
            <div class="gpa-card card h-100 p-4">
                <h6 class="mb-4 fw-bold">Performance Summary</h6>
                <?php
                    $semesters->data_seek(0);
                    $totalCourses = 0;
                    $totalFailed = 0;
                    $totalCU = 0;
                    
                    while ($sem = $semesters->fetch_assoc()) {
                        $totalCourses += (int)$sem['course_count'];
                        $totalFailed += (int)$sem['fail_count'];
                        $totalCU += (int)$sem['total_cu'];
                    }
                ?>
                <div class="row g-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Total Courses Completed</small>
                        <div class="display-6 text-primary"><?= $totalCourses ?></div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Total Credit Units</small>
                        <div class="display-6 text-success"><?= $totalCU ?></div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Courses Failed</small>
                        <div class="display-6 text-danger"><?= $totalFailed ?></div>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Pass Rate</small>
                        <div class="display-6 text-info">
                            <?= $totalCourses > 0 ? round((($totalCourses - $totalFailed) / $totalCourses) * 100, 1) : 0 ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Semester Breakdown -->
    <h5 class="mb-3"><i class="bi bi-card-list"></i> Semester-by-Semester Breakdown</h5>
    <div class="gpa-card card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Semester</th>
                        <th>Courses</th>
                        <th>Credit Units</th>
                        <th>Failed</th>
                        <th>Pass Rate</th>
                        <th>GPA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $semesters->data_seek(0);
                        while ($sem = $semesters->fetch_assoc()):
                            $gpa = (float)$sem['gpa'];
                            $passRate = (int)$sem['course_count'] > 0 ? 
                                round((((int)$sem['course_count'] - (int)$sem['fail_count']) / (int)$sem['course_count']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><strong><?= h($sem['session_name'] . ' S' . $sem['semester_number']) ?></strong></td>
                        <td><?= (int)$sem['course_count'] ?></td>
                        <td><?= (int)$sem['total_cu'] ?></td>
                        <td><span class="badge bg-danger"><?= (int)$sem['fail_count'] ?></span></td>
                        <td><?= $passRate ?>%</td>
                        <td>
                            <span class="gpa-badge <?php
                                if ($gpa >= 3.5) echo 'gpa-excellent';
                                elseif ($gpa >= 2.5) echo 'gpa-good';
                                elseif ($gpa >= 1.5) echo 'gpa-fair';
                                else echo 'gpa-poor';
                            ?>">
                                <?= number_format($gpa, 2) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- GPA Trend Analysis -->
    <div class="mt-5">
        <h5 class="mb-3"><i class="bi bi-graph-up-arrow"></i> GPA Trend</h5>
        <div class="gpa-card card p-4">
            <?php if (count($gpaTrend) > 1): ?>
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle"></i>
                    Your GPA trend from <?= h($gpaTrend[count($gpaTrend)-1]['label']) ?> to <?= h($gpaTrend[0]['label']) ?>
                </div>
                <div style="overflow-x: auto;">
                    <canvas id="gpaTrendChart"></canvas>
                </div>
            <?php else: ?>
                <p class="text-muted">More semesters needed to display trend analysis.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recommendations -->
    <div class="mt-5">
        <h5 class="mb-3"><i class="bi bi-lightbulb"></i> Performance Insights</h5>
        <div class="alert alert-info">
            <ul class="mb-0">
                <?php if ($cgpa >= 3.5): ?>
                    <li>✓ Excellent academic performance! Keep maintaining high standards.</li>
                <?php elseif ($cgpa >= 2.0): ?>
                    <li>✓ Good performance. Continue with current study habits.</li>
                <?php else: ?>
                    <li>⚠ Your GPA is below 2.0. Consider seeking academic support.</li>
                <?php endif; ?>
                
                <?php if ($totalFailed > 0): ?>
                    <li>ℹ You have <?= $totalFailed ?> failed course(s). These may be required for graduation.</li>
                <?php endif; ?>
                
                <?php if (count($gpaTrend) > 1): 
                    $latestGPA = $gpaTrend[0]['gpa'];
                    $previousGPA = $gpaTrend[1]['gpa'];
                    if ($latestGPA > $previousGPA):
                ?>
                    <li>✓ Upward trend detected! Your GPA has improved.</li>
                <?php elseif ($latestGPA < $previousGPA): ?>
                    <li>⚠ Your GPA declined from last semester. Review study strategies.</li>
                <?php else: ?>
                    <li>ℹ GPA remains stable. Maintain current performance level.</li>
                <?php endif; endif; ?>
            </ul>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-5">
        <a href="transcript.php" class="btn btn-primary">
            <i class="bi bi-file-earmark-pdf"></i> View Transcript
        </a>
        <a href="academic_standing.php" class="btn btn-outline-primary">
            <i class="bi bi-graph-up"></i> Academic Standing
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php if (count($gpaTrend) > 1): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    const gpaTrendData = <?= json_encode(array_reverse($gpaTrend)) ?>;
    const ctx = document.getElementById('gpaTrendChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: gpaTrendData.map(d => d.label),
                datasets: [{
                    label: 'GPA',
                    data: gpaTrendData.map(d => d.gpa),
                    borderColor: '#006B3F',
                    backgroundColor: 'rgba(0, 107, 63, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 6,
                    pointBackgroundColor: '#006B3F',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        ticks: { callback: v => v.toFixed(1) }
                    }
                }
            }
        });
    }
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
