<?php
/**
 * Institution Management Panel
 * For administrators to add/edit institutions that can use SwiftGrade
 */
session_start();

// Check if user is admin (basic check)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // For setup purpose, allow access without login
    if (!isset($_GET['setup_key']) || $_GET['setup_key'] !== 'admin_setup_2026') {
        die('Unauthorized. Only administrators can access this page.');
    }
}

$centralConn = new mysqli('localhost', 'root', '', 'swiftgrade_central');
$centralConn->set_charset('utf8mb4');

$msg = '';
$institutions = [];

// Fetch all institutions
$result = $centralConn->query("SELECT * FROM institutions ORDER BY name");
$institutions = $result->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $name = trim($_POST['name']);
        $code = trim($_POST['code']);
        $db_name = trim($_POST['database_name']);
        $color = $_POST['color'] ?? '#16A34A';

        if ($name && $code && $db_name) {
            $stmt = $centralConn->prepare(
                "INSERT INTO institutions (name, code, database_name, color_primary) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param('ssss', $name, $code, $db_name, $color);

            if ($stmt->execute()) {
                $msg = "✓ Institution '$name' added successfully!";
                // Refresh list
                $result = $centralConn->query("SELECT * FROM institutions ORDER BY name");
                $institutions = $result->fetch_all(MYSQLI_ASSOC);
            } else {
                $msg = "✗ Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $msg = "✗ Please fill in all required fields.";
        }
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $stmt = $centralConn->prepare("UPDATE institutions SET is_active = !is_active WHERE id = ?");
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $msg = "✓ Institution status toggled.";
            $result = $centralConn->query("SELECT * FROM institutions ORDER BY name");
            $institutions = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $centralConn->prepare("DELETE FROM institutions WHERE id = ?");
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            $msg = "✓ Institution deleted.";
            $result = $centralConn->query("SELECT * FROM institutions ORDER BY name");
            $institutions = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }
}

$centralConn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Institution Management – SwiftGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #16A34A;
            --primary-light: #DCFCE7;
            --gray-100: #F3F4F6;
            --gray-500: #6B7280;
        }
        body { background: var(--gray-100); }
        .container { max-width: 1000px; margin-top: 30px; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 8px; }
        .btn-sg { background: var(--primary); border-color: var(--primary); color: white; }
        .btn-sg:hover { background: #15803d; }
        .inst-badge { display: inline-block; padding: 4px 8px; background: var(--primary-light); color: var(--primary); border-radius: 4px; font-size: 0.85rem; }
        .inst-table td { vertical-align: middle; }
        table { font-size: 0.95rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="bi bi-building"></i> Institution Management</h1>
            <p class="text-muted">Add and manage institutions that use SwiftGrade</p>
        </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= strpos($msg, '✓') === 0 ? 'success' : 'danger' ?> mb-4">
        <?= $msg ?>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card p-4">
                <h5 class="mb-4"><i class="bi bi-plus-circle"></i> Add New Institution</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="add">

                    <div class="mb-3">
                        <label class="form-label">Institution Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. LASCOHET, UNIDEL" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Institution Code *</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. LASCOHET, UNIDEL" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Database Name *</label>
                        <input type="text" name="database_name" class="form-control" placeholder="e.g. lascohet_results, unidel_results" required>
                        <small class="text-muted">Must be an existing MySQL database on this server</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Primary Color</label>
                        <div class="input-group">
                            <input type="color" name="color" class="form-control" style="width: 50px" value="#16A34A" title="Choose color">
                            <input type="text" class="form-control" value="#16A34A" disabled>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-sg w-100">
                        <i class="bi bi-plus"></i> Add Institution
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card p-4">
                <h5 class="mb-3"><i class="bi bi-info-circle"></i> How It Works</h5>
                <ul style="font-size: 0.95rem; line-height: 1.8;">
                    <li><strong>Add Institution:</strong> Each institution needs its own MySQL database with the SwiftGrade schema</li>
                    <li><strong>Schema:</strong> Create a new database and run the <code>database/lascohet_schema.sql</code> file</li>
                    <li><strong>Seed Data:</strong> Run <code>database/seed.php</code> to populate demo accounts</li>
                    <li><strong>Users:</strong> Each institution's users log in with their matric/staff ID</li>
                    <li><strong>Multi-Tenant:</strong> This system supports unlimited institutions using one SwiftGrade installation</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card p-4">
        <h5 class="mb-4"><i class="bi bi-buildings"></i> Registered Institutions</h5>

        <?php if (empty($institutions)): ?>
        <p class="text-muted">No institutions yet. Add one using the form above.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover inst-table">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Database</th>
                        <th>Color</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($institutions as $inst): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($inst['name']) ?></strong>
                        </td>
                        <td>
                            <span class="inst-badge"><?= htmlspecialchars($inst['code']) ?></span>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($inst['database_name']) ?></code>
                        </td>
                        <td>
                            <div style="width: 24px; height: 24px; background: <?= htmlspecialchars($inst['color_primary']) ?>; border-radius: 4px; display: inline-block;"></div>
                            <?= htmlspecialchars($inst['color_primary']) ?>
                        </td>
                        <td>
                            <?php if ($inst['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $inst['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Toggle Status">
                                    <i class="bi bi-toggle-on"></i>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this institution?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $inst['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="mt-4 text-center pb-4">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Home
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
