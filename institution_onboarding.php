<?php
/**
 * Institution Onboarding Wizard
 * Step-by-step guide to add a new university to SwiftGrade
 */
session_start();

// Security check
if (!isset($_GET['setup_key']) || $_GET['setup_key'] !== 'onboard_new_inst_2026') {
    // Allow if admin is logged in
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        die('Unauthorized. This page is for onboarding new institutions.');
    }
}

$msg = '';
$step = (int)($_GET['step'] ?? 1);

// Central connection
$central = new mysqli('localhost', 'root', '', 'swiftgrade_central');
$central->set_charset('utf8mb4');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $institutionName = trim($_POST['inst_name'] ?? '');
    $institutionCode = trim($_POST['inst_code'] ?? '');
    $databaseName = trim($_POST['db_name'] ?? '');
    $sourceDatabase = $_POST['source_db'] ?? 'lascohet_results';

    if ($institutionName && $institutionCode && $databaseName) {
        // Step 1: Create database
        if ($step == 1) {
            $systemConn = new mysqli('localhost', 'root', '');
            
            $sql = "CREATE DATABASE IF NOT EXISTS $databaseName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if ($systemConn->query($sql)) {
                $msg = "✓ Database '$databaseName' created successfully. Proceed to Step 2.";
                $step = 2;
            } else {
                $msg = "✗ Error creating database: " . $systemConn->error;
            }
            $systemConn->close();
        }

        // Step 2: Subscribe to central system
        if ($step == 2) {
            $color = $_POST['color'] ?? '#16A34A';
            
            $stmt = $central->prepare(
                "INSERT INTO institutions (name, code, database_name, color_primary) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param('ssss', $institutionName, $institutionCode, $databaseName, $color);

            if ($stmt->execute()) {
                $msg = "✓ Institution registered! Now copy demo data.";
                $step = 3;
            } else {
                $msg = "✗ Error registering institution: " . $stmt->error;
            }
            $stmt->close();
        }

        // Step 3: Copy schema
        if ($step == 3) {
            $sourceConn = new mysqli('localhost', 'root', '', $sourceDatabase);
            $targetConn = new mysqli('localhost', 'root', '', $databaseName);

            // Get all table names from source
            $tables = [];
            $result = $sourceConn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }

            $allSuccess = true;
            foreach ($tables as $table) {
                // Get CREATE TABLE statement
                $createStmt = $sourceConn->query("SHOW CREATE TABLE $table")->fetch_assoc();
                $create = $createStmt['Create Table'];
                
                // Create table in target
                if (!$targetConn->query($create)) {
                    $msg .= "Error creating table $table: " . $targetConn->error . "<br>";
                    $allSuccess = false;
                }

                // Copy data
                if ($allSuccess) {
                    $targetConn->query("INSERT INTO $table SELECT * FROM $sourceConn->database_name.$table");
                }
            }

            if ($allSuccess) {
                $msg = "✓ Schema and data copied successfully! Institution is ready.";
                $step = 4;
            } else {
                $msg = "⚠ Partial success. Check error messages above.";
            }

            $sourceConn->close();
            $targetConn->close();
        }
    } else {
        $msg = "✗ Please fill in all required fields.";
    }
}

$central->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Institution Onboarding – SwiftGrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #16A34A;
            --primary-light: #DCFCE7;
        }
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; padding: 30px 0; }
        .wizard-container { max-width: 700px; margin: 0 auto; }
        .card { border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 12px; }
        .step-header { background: var(--primary); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .progress-circle { width: 40px; height: 40px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: var(--primary); }
        .step-label { font-size: 0.85rem; color: #666; }
        .btn-sg { background: var(--primary); border-color: var(--primary); }
        .btn-sg:hover { background: #15803d; }
    </style>
</head>
<body>

<div class="wizard-container">
    <div class="card">
        <div class="step-header">
            <h4><i class="bi bi-rocket-takeoff"></i> Add New Institution</h4>
            <p class="mb-0 small">Step <?= $step ?> of 4</p>
        </div>

        <div class="card-body p-5">
            <?php if ($msg): ?>
            <div class="alert alert-<?= strpos($msg, '✓') === 0 ? 'success' : (strpos($msg, '⚠') === 0 ? 'warning' : 'danger') ?> mb-4">
                <?= $msg ?>
            </div>
            <?php endif; ?>

            <!-- STEP 1: Institution Info -->
            <?php if ($step == 1): ?>
            <h5 class="mb-4">Basic Information</h5>
            <form method="POST">
                <input type="hidden" name="step" value="1">

                <div class="mb-3">
                    <label class="form-label"><strong>Institution Name</strong></label>
                    <input type="text" name="inst_name" class="form-control" placeholder="e.g. Lagos State College of Technology" required>
                    <small class="text-muted">The full name of the institution</small>
                </div>

                <div class="mb-3">
                    <label class="form-label"><strong>Institution Code</strong></label>
                    <input type="text" name="inst_code" class="form-control" placeholder="e.g. LASCOHET" required>
                    <small class="text-muted">Short code (uppercase, no spaces)</small>
                </div>

                <div class="mb-3">
                    <label class="form-label"><strong>Database Name</strong></label>
                    <input type="text" name="db_name" class="form-control" placeholder="e.g. lascohet_results" required>
                    <small class="text-muted">Will be created if it doesn't exist</small>
                </div>

                <div class="mb-4">
                    <label class="form-label"><strong>Theme Color</strong></label>
                    <div class="input-group">
                        <input type="color" name="color" class="form-control" style="width: 60px" value="#16A34A">
                        <input type="text" class="form-control" value="#16A34A" disabled>
                    </div>
                </div>

                <button type="submit" class="btn btn-sg w-100 py-2">
                    <i class="bi bi-chevron-right"></i> Create Database & Continue
                </button>
            </form>
            <?php endif; ?>

            <!-- STEP 2: Registration -->
            <?php if ($step == 2): ?>
            <h5 class="mb-4">Register Institution</h5>
            <form method="POST">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="inst_name" value="<?= htmlspecialchars($_POST['inst_name']) ?>">
                <input type="hidden" name="inst_code" value="<?= htmlspecialchars($_POST['inst_code']) ?>">
                <input type="hidden" name="db_name" value="<?= htmlspecialchars($_POST['db_name']) ?>">
                <input type="hidden" name="color" value="<?= htmlspecialchars($_POST['color'] ?? '#16A34A') ?>">

                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Your institution is about to be registered in the SwiftGrade system. This will:
                    <ul class="mb-0 mt-2">
                        <li>Add it to the login dropdown</li>
                        <li>Enable multi-institution support</li>
                        <li>Allow users to select this institution when logging in</li>
                    </ul>
                </div>

                <div class="mb-3 p-3" style="background: #f0f0f0; border-radius: 6px;">
                    <strong>Institution Details:</strong><br>
                    Name: <?= htmlspecialchars($_POST['inst_name']) ?><br>
                    Code: <?= htmlspecialchars($_POST['inst_code']) ?><br>
                    Database: <?= htmlspecialchars($_POST['db_name']) ?>
                </div>

                <button type="submit" class="btn btn-sg w-100 py-2">
                    <i class="bi bi-check-lg"></i> Register Institution
                </button>
            </form>
            <?php endif; ?>

            <!-- STEP 3: Copy Data -->
            <?php if ($step == 3): ?>
            <h5 class="mb-4">Initialize Database</h5>
            <form method="POST">
                <input type="hidden" name="step" value="3">
                <input type="hidden" name="inst_name" value="<?= htmlspecialchars($_POST['inst_name']) ?>">
                <input type="hidden" name="inst_code" value="<?= htmlspecialchars($_POST['inst_code']) ?>">
                <input type="hidden" name="db_name" value="<?= htmlspecialchars($_POST['db_name']) ?>">

                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    This step will copy the database schema and demo data from a template database.
                </div>

                <div class="mb-3">
                    <label class="form-label"><strong>Copy From (Template Database)</strong></label>
                    <select name="source_db" class="form-select">
                        <option value="lascohet_results">LASCOHET Results (Default)</option>
                        <option value="unidel_schema">UNIDEL Schema</option>
                    </select>
                    <small class="text-muted">Select a database to use as template</small>
                </div>

                <button type="submit" class="btn btn-sg w-100 py-2">
                    <i class="bi bi-download"></i> Copy Schema & Data
                </button>
            </form>
            <?php endif; ?>

            <!-- STEP 4: Complete -->
            <?php if ($step == 4): ?>
            <h5 class="mb-4"><i class="bi bi-check-circle" style="color: #16A34A;"></i> Setup Complete!</h5>

            <div class="alert alert-success">
                <strong><?= htmlspecialchars($_POST['inst_name']) ?></strong> has been successfully added to SwiftGrade!
            </div>

            <div class="p-3 mb-4" style="background: #f9f9f9; border-radius: 6px;">
                <strong>What's Next?</strong>
                <ul class="mb-0 mt-2">
                    <li>✓ Database created</li>
                    <li>✓ Institution registered</li>
                    <li>✓ Schema and data loaded</li>
                    <li><strong>Go to the login page and test it!</strong></li>
                </ul>
            </div>

            <div class="mb-3 p-3" style="background: #e8f5e9; border-left: 4px solid #16A34A;">
                <strong>Login Details:</strong><br>
                <small>
                    <strong>Admin:</strong> admin / Admin@2026<br>
                    <strong>Lecturer:</strong> mr.okeke / NextGen@2026<br>
                    <strong>Student:</strong> adesanya.john / adesanya123
                </small>
            </div>

            <div class="d-flex gap-2">
                <a href="swiftgrade_login.php" class="btn btn-sg flex-grow-1">
                    <i class="bi bi-box-arrow-right"></i> Go to Login Page
                </a>
                <a href="manage_institutions.php" class="btn btn-outline-secondary flex-grow-1">
                    <i class="bi bi-building"></i> Manage Institutions
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4 text-center">
        <p style="color: white; font-size: 0.9rem;">
            <i class="bi bi-shield-exclamation"></i> This is a secure setup process
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
