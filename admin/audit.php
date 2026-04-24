<?php
/* ================================================================
   ADMIN – Audit Trail Viewer
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');
$pageTitle = 'Audit Trail';

/* Filters */
$filterAction = $_GET['action_filter'] ?? '';
$filterUser   = $_GET['user_filter'] ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 50;
$offset       = ($page - 1) * $perPage;

$where = [];
$params = [];
$types  = '';

if ($filterAction) {
    $where[] = 'a.action LIKE ?';
    $params[] = "%$filterAction%";
    $types .= 's';
}
if ($filterUser) {
    $where[] = '(u.full_name LIKE ? OR u.username LIKE ?)';
    $params[] = "%$filterUser%";
    $params[] = "%$filterUser%";
    $types .= 'ss';
}

$sql = "SELECT a.*, u.full_name, u.username
        FROM audit_trail a
        LEFT JOIN users u ON u.id = a.user_id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= " ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result();

/* Count total */
$countSql = "SELECT COUNT(*) AS c FROM audit_trail a LEFT JOIN users u ON u.id = a.user_id";
if ($where) $countSql .= ' WHERE ' . implode(' AND ', $where);
$stmtC = $conn->prepare($countSql);
if ($params) $stmtC->bind_param($types, ...$params);
$stmtC->execute();
$total = $stmtC->get_result()->fetch_assoc()['c'];
$totalPages = ceil($total / $perPage);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
<div class="container">
    <h2><i class="bi bi-journal-text"></i> Audit Trail</h2>
    <small>Complete log of all system actions for accountability</small>
</div>
</div>

<div class="container mb-4">

<!-- Filters -->
<div class="card shadow-sm p-3 mb-3">
<form class="row g-2 align-items-end">
    <div class="col-md-4">
        <label class="form-label">Action</label>
        <input type="text" name="action_filter" class="form-control" value="<?= h($filterAction) ?>" placeholder="e.g. LOGIN, CREATE_USER">
    </div>
    <div class="col-md-4">
        <label class="form-label">User</label>
        <input type="text" name="user_filter" class="form-control" value="<?= h($filterUser) ?>" placeholder="Name or username">
    </div>
    <div class="col-md-4">
        <button class="btn btn-lsc me-2">Filter</button>
        <a href="audit.php" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>
</div>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-sm table-hover mb-0">
<thead><tr><th>#</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th><th>Timestamp</th></tr></thead>
<tbody>
<?php while ($l = $logs->fetch_assoc()): ?>
<tr>
    <td><?= $l['id'] ?></td>
    <td><?= h($l['full_name'] ?? $l['username'] ?? 'System') ?></td>
    <td><span class="badge bg-dark"><?= h($l['action']) ?></span></td>
    <td><?= h(($l['entity_type'] ?? '') . ($l['entity_id'] ? '#'.$l['entity_id'] : '')) ?></td>
    <td><small><?= h($l['details'] ?? '') ?></small></td>
    <td><code><?= h($l['ip_address']) ?></code></td>
    <td class="text-muted" style="font-size:.8rem"><?= h($l['created_at']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
<ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <li class="page-item <?= $i===$page?'active':'' ?>">
        <a class="page-link" href="?page=<?= $i ?>&action_filter=<?= urlencode($filterAction) ?>&user_filter=<?= urlencode($filterUser) ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
</ul>
</nav>
<?php endif; ?>

<p class="text-muted text-center mt-2">Showing page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> total entries)</p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
