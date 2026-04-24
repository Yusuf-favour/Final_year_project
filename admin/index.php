<?php
/* ================================================================
   ADMIN – Dashboard
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

$r = $conn->query("SELECT COUNT(*) AS c FROM result_batches WHERE status='hod_approved'");
$stats['approved_batches'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) AS c FROM result_batches WHERE status='published'");
$stats['published_batches'] = $r->fetch_assoc()['c'];

/* recent audit */
$audit = $conn->query(
    "SELECT a.*, u.full_name
     FROM audit_trail a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC LIMIT 10"
);

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern-dashboard.css">

<div class="page-header">
    <div class="container d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <h2><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
            <small>Welcome, <?= h(currentFullName()) ?> · Session <?= h(currentSession($conn)) ?></small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="users.php" class="btn btn-white"><i class="bi bi-people-fill me-1"></i> Manage Users</a>
            <a href="publish.php" class="btn btn-sg-outline"><i class="bi bi-megaphone me-1"></i> Publish Queue</a>
        </div>
    </div>
</div>

<div class="container pb-4">
    <div class="row g-4 mb-5">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value"><?= $stats['students'] ?></div>
                        <div class="stat-label">Students</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-mortarboard"></i></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value"><?= $stats['staff'] ?></div>
                        <div class="stat-label">Lecturers/HODs</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value"><?= $stats['courses'] ?></div>
                        <div class="stat-label">Courses</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-book"></i></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-value"><?= $stats['departments'] ?></div>
                        <div class="stat-label">Departments</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-building"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-card card mb-5">
        <div class="card-header">
            <i class="bi bi-arrow-repeat me-2"></i>Approval Workflow
        </div>
        <div class="card-body">
            <div class="row text-center g-4">
                <div class="col-md-3">
                    <div class="soft-badge mb-2"><i class="bi bi-file-earmark"></i> Draft</div>
                    <div class="stat-value"><?= $stats['draft_batches'] ?></div>
                    <small class="text-muted">Awaiting submission</small>
                </div>
                <div class="col-md-3">
                    <div class="soft-badge mb-2"><i class="bi bi-send-check"></i> Submitted</div>
                    <div class="stat-value"><?= $stats['submitted_batches'] ?></div>
                    <small class="text-muted">Awaiting HOD review</small>
                </div>
                <div class="col-md-3">
                    <div class="soft-badge mb-2"><i class="bi bi-check-circle"></i> Approved</div>
                    <div class="stat-value"><?= $stats['approved_batches'] ?></div>
                    <small class="text-muted">Ready for publication</small>
                </div>
                <div class="col-md-3">
                    <div class="soft-badge mb-2"><i class="bi bi-megaphone"></i> Published</div>
                    <div class="stat-value"><?= $stats['published_batches'] ?></div>
                    <small class="text-muted">Visible to students</small>
                </div>
            </div>
            <?php if ($stats['approved_batches'] > 0): ?>
            <div class="mt-3 text-center">
                <a href="publish.php" class="btn btn-lsc"><i class="bi bi-check2-square me-1"></i>Publish Approved Results</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="dashboard-card card h-100">
                <div class="card-header">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </div>
                <div class="card-body d-grid gap-3">
                    <a href="users.php" class="action-tile"><div class="label">Users</div><div class="value"><i class="bi bi-people me-2"></i>Manage Accounts</div></a>
                    <a href="courses.php" class="action-tile"><div class="label">Courses</div><div class="value"><i class="bi bi-book me-2"></i>Manage Courses</div></a>
                    <a href="departments.php" class="action-tile"><div class="label">Structure</div><div class="value"><i class="bi bi-building me-2"></i>Departments and Programs</div></a>
                    <a href="sessions.php" class="action-tile"><div class="label">Calendar</div><div class="value"><i class="bi bi-calendar3 me-2"></i>Sessions and Semesters</div></a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="dashboard-card card h-100">
                <div class="card-header">
                    <i class="bi bi-journal-text me-2"></i>Recent Activity
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive" style="max-height: 340px; overflow-y: auto;">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>User</th><th>Action</th><th>Time</th></tr></thead>
                            <tbody>
                            <?php while ($a = $audit->fetch_assoc()): ?>
                                <tr>
                                    <td><?= h($a['full_name'] ?? 'System') ?></td>
                                    <td><?= h($a['action']) ?></td>
                                    <td class="text-muted"><?= h(formatDateTime($a['created_at'], 'd M Y H:i')) ?></td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
