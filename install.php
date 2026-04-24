<?php
/* ================================================================
   SwiftGrade – Install / Setup Script
   Run once to create the database, import schema, and seed admin.
   ================================================================ */
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'swiftgrade_results';

$messages = [];
$ok = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* 1. Connect to MySQL (no database selected) */
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        die('MySQL connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');

    /* 2. Create database */
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->select_db($db);
    $messages[] = 'Database created.';

    /* 3. Import schema */
    $schemaFile = __DIR__ . '/database/swiftgrade_schema.sql';
    if (!file_exists($schemaFile)) {
        die('Schema file not found: ' . $schemaFile);
    }

    $sql = file_get_contents($schemaFile);
    if ($conn->multi_query($sql)) {
        do { /* flush results */ } while ($conn->next_result());
    }

    if ($conn->error) {
        $messages[] = 'Schema warning (tables may already exist): ' . $conn->error;
    } else {
        $messages[] = 'Schema imported successfully.';
    }

    /* 4. Create admin user */
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminPass = trim($_POST['admin_pass'] ?? 'Admin@2026');
    $adminName = trim($_POST['admin_name'] ?? 'System Administrator');

    $hash = password_hash($adminPass, PASSWORD_DEFAULT);

    /* Re-open connection (multi_query leaves it in odd state) */
    $conn->close();
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');

    /* Check if admin already exists */
    $chk = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $chk->bind_param('s', $adminUser);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $messages[] = "Admin user '$adminUser' already exists – skipped.";
    } else {
        $ins = $conn->prepare(
            "INSERT INTO users (username, password_hash, full_name, role, is_active)
             VALUES (?, ?, ?, 'admin', 1)"
        );
        $ins->bind_param('sss', $adminUser, $hash, $adminName);
        $ins->execute();
        $messages[] = "Admin user created: <strong>$adminUser</strong>";
    }

    /* 4. Run seed data (reconnect first) */
    $seedFile = __DIR__ . '/database/swiftgrade_seed.sql';
    if (file_exists($seedFile)) {
        $conn = new mysqli($host, $user, $pass, $db);
        $conn->set_charset('utf8mb4');
        $seedSql = file_get_contents($seedFile);
        if ($conn->multi_query($seedSql)) {
            do { /* flush results */ } while ($conn->next_result());
        }
        $conn->close();
        $messages[] = 'Seed data loaded (admin/admin123, demo/demo123).';
    }

    $messages[] = '<strong class="text-success">Installation complete!</strong> Login with <strong>admin/admin123</strong> or <strong>demo/demo123</strong>. <a href="login.php">Log in now</a>.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install – SwiftGrade</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/swiftgrade.css" rel="stylesheet">
</head>
<body class="login-wrapper">
<div class="card login-card shadow-lg">

    <div class="card-header">
        <h5>SwiftGrade University – Installation</h5>
        <small class="text-muted">Student Result Management System</small>
    </div>

    <div class="card-body">

    <?php if (!empty($messages)): ?>
        <div class="alert alert-info">
            <ul class="mb-0">
            <?php foreach ($messages as $m): ?>
                <li><?= $m ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
        <p class="text-muted mb-3">This will create the <code>swiftgrade_results</code> database with SwiftGrade University data.</p>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Admin Username</label>
                <input type="text" name="admin_user" class="form-control" value="admin" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Admin Password</label>
                <input type="password" name="admin_pass" class="form-control" value="Admin@2026" required minlength="6">
            </div>
            <div class="mb-3">
                <label class="form-label">Admin Full Name</label>
                <input type="text" name="admin_name" class="form-control" value="System Administrator" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-gear-fill"></i> Install SwiftGrade
            </button>
        </form>
    <?php endif; ?>

    </div>
</div>
</body>
</html>
