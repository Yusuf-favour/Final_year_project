<?php
/* ================================================================
   ADMIN – Modernized Dashboard with CRUD
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$pageTitle = 'Admin Dashboard';

/* ---------- stats ---------- */
$stats = [];

$r = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='student' AND is_active=1");
$stats['students'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role IN ('lecturer','hod') AND is_active=1");
$stats['staff'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) AS c FROM courses");
$stats['courses'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) AS c FROM departments");
$stats['departments'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) AS c FROM result_batches WHERE status='draft'");
$stats['draft_batches'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) AS c FROM result_batches WHERE status='submitted'");
$stats['submitted_batches'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) AS c FROM result_batches WHERE status='approved'");
$stats['approved_batches'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) AS c FROM result_batches WHERE status='published'");
$stats['published_batches'] = $r->fetch_assoc()['c'];

/* Recent activity */
$audit = $conn->query(
    "SELECT a.*, u.full_name
     FROM audit_trail a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC LIMIT 8"
);

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern-dashboard.css">

<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
                <small>Welcome <?= h(currentFullName()) ?> | Session: <?= h(getCurrentSession($conn)) ?></small>
            </div>
            <div>
                <a href="users.php" class="btn btn-primary">
                    <i class="bi bi-people-fill"></i> Manage Users
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- KEY METRICS -->
    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value"><?= $stats['students'] ?></div>
                        <div class="stat-label">Active Students</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-mortarboard"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value"><?= $stats['staff'] ?></div>
                        <div class="stat-label">Lecturers/HODs</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value"><?= $stats['courses'] ?></div>
                        <div class="stat-label">Courses</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-book"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value"><?= $stats['departments'] ?></div>
                        <div class="stat-label">Departments</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-building"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- WORKFLOW PIPELINE -->
    <div class="row g-3 mb-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-shuffle"></i> Result Approval Pipeline
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="mb-2">
                                <div style="font-size: 2.5rem; color: #6B7280;">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                            </div>
                            <h5 style="color: var(--dark); margin-bottom: 0.5rem;"><?= $stats['draft_batches'] ?></h5>
                            <p class="text-muted mb-0" style="font-size: 0.9rem;">Draft</p>
                        </div>
                        <div class="col-md-3">
                            <div style="font-size: 2rem; color: #D1D5DB; align-self: center;">→</div>
                            <div style="font-size: 2.5rem; color: #F59E0B;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h5 style="color: var(--dark); margin-bottom: 0.5rem;"><?= $stats['submitted_batches'] ?></h5>
                            <p class="text-muted mb-0" style="font-size: 0.9rem;">Submitted</p>
                        </div>
                        <div class="col-md-3">
                            <div style="font-size: 2rem; color: #D1D5DB;">→</div>
                            <div style="font-size: 2.5rem; color: #3B82F6;">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h5 style="color: var(--dark); margin-bottom: 0.5rem;"><?= $stats['approved_batches'] ?></h5>
                            <p class="text-muted mb-0" style="font-size: 0.9rem;">Approved</p>
                        </div>
                        <div class="col-md-3">
                            <div style="font-size: 2rem; color: #D1D5DB;">→</div>
                            <div style="font-size: 2.5rem; color: #10B981;">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                            <h5 style="color: var(--dark); margin-bottom: 0.5rem;"><?= $stats['published_batches'] ?></h5>
                            <p class="text-muted mb-0" style="font-size: 0.9rem;">Published</p>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="publish.php" class="btn btn-success">
                            <i class="bi bi-check2-square"></i> Publish Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="row g-3 mb-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-lightning"></i> Quick Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="users.php" class="btn btn-outline-primary text-start">
                            <i class="bi bi-people"></i> Manage Users
                        </a>
                        <a href="courses.php" class="btn btn-outline-primary text-start">
                            <i class="bi bi-book"></i> Manage Courses
                        </a>
                        <a href="departments.php" class="btn btn-outline-primary text-start">
                            <i class="bi bi-building"></i> Manage Departments
                        </a>
                        <a href="programs.php" class="btn btn-outline-primary text-start">
                            <i class="bi bi-graduation-cap"></i> Manage Programs
                        </a>
                        <a href="sessions.php" class="btn btn-outline-primary text-start">
                            <i class="bi bi-calendar"></i> Manage Sessions
                        </a>
                        <a href="audit.php" class="btn btn-outline-primary text-start">
                            <i class="bi bi-journal-text"></i> Audit Trail
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-activity"></i> Recent Activity
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <div class="list-group list-group-flush">
                        <?php while ($a = $audit->fetch_assoc()): ?>
                            <div class="list-group-item px-0 py-2 border-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1" style="font-size: 0.9rem;">
                                            <i class="bi bi-circle-fill" style="font-size: 0.4rem; color: var(--primary);"></i>
                                            <?= h($a['action']) ?>
                                        </h6>
                                        <small class="text-muted"><?= h($a['full_name'] ?? 'System') ?></small>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        <?= timeAgo($a['created_at']) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4"></div>
</div>

<script>
function timeAgo(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return minutes + 'm ago';
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return hours + 'h ago';
    const days = Math.floor(hours / 24);
    if (days < 7) return days + 'd ago';
    return date.toLocaleDateString();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
