<?php
/* ================================================================
   LASCOHET Database Installer
   Creates lascohet_results database + full schema + seed data
   Run ONCE after plan approval
   ================================================================ */
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'lascohet_results';

$messages = [];
$ok = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {

    // 1. Connect to MySQL (no database)
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        die('MySQL connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');

    // 2. Create/select database
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->select_db($db);
    $messages[] = "<strong>✓ Database '$db' ready.</strong>";

    // 3. Load and execute lascohet_schema.sql
    $schemaFile = __DIR__ . '/database/lascohet_schema.sql';
    if (!file_exists($schemaFile)) {
        die('❌ Schema missing: ' . $schemaFile);
    }
    $sql = file_get_contents($schemaFile);
    $conn->multi_query($sql);
    $hasError = false;
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
        if ($conn->errno) {
            $messages[] = 'Schema error: ' . $conn->error . ' (continuing...)';
            $hasError = true;
            $conn->next_result();
        }
    } while ($conn->more_results());
    if (!$hasError) {
        $messages[] = '✓ Schema imported successfully';
    }

    // 4. Load and execute lascohet_seed.sql
    $seedFile = __DIR__ . '/database/lascohet_seed.sql';
    // Reconnect for seed (clean state)
    $conn->close();
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');
    
    if (file_exists($seedFile)) {
        $seedSql = file_get_contents($seedFile);
        $conn->multi_query($seedSql);
        $hasError = false;
        do {
            if ($result = $conn->store_result()) $result->free();
            if ($conn->errno) {
                $messages[] = 'Seed error: ' . $conn->error . ' (continuing...)';
                $hasError = true;
                $conn->next_result();
            }
        } while ($conn->more_results());
        if (!$hasError) {
            $messages[] = '✓ Seed data loaded: admin, mr.okeke, students';
        }
    } else {
        $messages[] = '⚠ Seed file missing: ' . $seedFile;
    }

    // 5. Verify critical tables
    $tables = ['users', 'departments', 'programs', 'students'];
    foreach ($tables as $table) {
        $r = $conn->query("SHOW TABLES LIKE '$table'");
        if ($r->num_rows > 0) {
            $messages[] = "✓ Table '$table' exists";
        } else {
            $messages[] = "❌ Table '$table' MISSING";
            $ok = false;
        }
    }

    // 6. Verify admin/mr.okeke users
    $r = $conn->query("SELECT username FROM users WHERE username IN ('admin', 'mr.okeke')");
    if ($r->num_rows === 2) {
        $messages[] = '<strong class="text-success">✅ Login ready:</strong><br>';
        $messages[] = '&nbsp;&nbsp;• Admin: <code>admin</code> / <code>Admin@2026</code>';
        $messages[] = '&nbsp;&nbsp;• Lecturer: <code>mr.okeke</code> / <code>Lascohet@2026</code>';
    } else {
        $messages[] = '⚠ Check users manually';
    }

    $conn->close();
    $messages[] = '<hr><strong>Installation complete!</strong> <a href=\"swiftgrade_login.php\" class="btn btn-success">Test Login</a> | <a href=\"describe_lascohet.php\" class="btn btn-info">Verify Tables</a>';
}

// Security: Delete after successful run
if (isset($_GET['delete'])) {
    unlink(__FILE__);
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>LASCOHET Database Install</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/swiftgrade.css" rel="stylesheet">
<style>body{background:#f8f9fa}</style>
</head>
<body class="py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-database-fill-gear me-2"></i>LASCOHET Database Setup</h4>
                    <small>lascohet_results schema + seed data</small>
                </div>
                <div class="card-body p-4">
                    
                    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                        <div class="alert alert-success">
                            <h5><i class="bi bi-check-circle-fill me-2"></i>Results:</h5>
                            <?php foreach ($messages as $m): ?>
                                <div><?= $m ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-4">
                            <a href="?delete=1" class="btn btn-danger me-2" onclick="return confirm('Delete installer?')">🗑️ Delete Installer</a>
                            <a href="index.php" class="btn btn-secondary">🏠 Home</a>
                        </div>
                    <?php else: ?>
                        
                        <div class="alert alert-warning">
                            <strong>This will:</strong>
                            <ul class="mb-0 mt-2">
                                <li>✅ Create tables in <code>lascohet_results</code></li>
                                <li>✅ Load LASCOHET departments/programs/courses</li>
                                <li>✅ Create admin + mr.okeke accounts</li>
                                <li>⚠️ <em>Skip existing tables/users</em></li>
                            </ul>
                        </div>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ready to install?</label>
                                <input type="hidden" name="confirm" value="1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirm" required>
                                    <label class="form-check-label" for="confirm">
                                        I confirm this fixes the missing <code>users</code> table error
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100 btn-lg">
                                <i class="bi bi-rocket-takeoff-fill me-2"></i>🚀 Install LASCOHET Database
                            </button>
                        </form>
                        
                        <hr class="my-4">
                        <div class="text-center text-muted small">
                            <p>Existing files used:</p>
                            <code>database/lascohet_schema.sql</code><br>
                            <code>database/lascohet_seed.sql</code>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

