<?php
/* ================================================================
   LASCOHET Database Verification Tool
   Run after install_lascohet.php to confirm tables/users exist
   ================================================================ */

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'lascohet_results';

echo "<!DOCTYPE html>
<html><head>
    <title>LASCOHET DB Verification</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>body{padding:20px;background:#f8f9fa}</style>
</head><body class='container'>";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

echo "<h2><i class='bi bi-database-check me-2'></i>LASCOHET Database Status</h2>
    <div class='alert alert-info'>
        <strong>Database:</strong> <code>$db</code> 
        <span class='badge bg-success ms-2'>Connected ✓</span>
    </div>";

// 1. Check critical tables
$critical = ['users', 'departments', 'programs', 'students', 'courses'];
echo "<h4>Critical Tables:</h4><div class='row'>";
foreach ($critical as $t) {
    $r = $conn->query("SHOW TABLES LIKE '$t'");
    $status = $r->num_rows > 0 ? 'bg-success' : 'bg-danger';
    $icon = $r->num_rows > 0 ? 'check-circle-fill' : 'x-circle-fill';
    echo "<div class='col-md-3 mb-2'>
            <span class='badge $status fs-6'>
                <i class='bi bi-$icon me-1'></i>$t
            </span>
          </div>";
}
echo "</div><hr>";

// 2. Users summary
echo "<h4>Users Table:</h4>";
$r = $conn->query("DESCRIBE users");
if ($r) {
    echo "<div class='table-responsive'><table class='table table-sm table-striped'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $r->fetch_assoc()) {
        echo "<tr>
                <td>{$row['Field']}</td>
                <td>{$row['Type']}</td>
                <td>" . ($row['Null'] === 'NO' ? 'NO' : 'YES') . "</td>
                <td>{$row['Key']}</td>
                <td>" . ($row['Default'] ?? 'NULL') . "</td>
              </tr>";
    }
    echo "</table></div>";
} else {
    echo "<div class='alert alert-danger'>users table missing!</div>";
}

// 3. Sample users
echo "<h5>Sample Users (first 10):</h5>";
$r = $conn->query("SELECT id, username, full_name, role, is_active FROM users ORDER BY id LIMIT 10");
if ($r && $r->num_rows > 0) {
    echo "<div class='table-responsive'><table class='table table-sm table-hover'>";
    echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Active</th></tr>";
    while ($row = $r->fetch_assoc()) {
        $active = $row['is_active'] ? '<span class=\"badge bg-success\">Yes</span>' : '<span class=\"badge bg-danger\">No</span>';
        echo "<tr>
                <td>{$row['id']}</td>
                <td><code>{$row['username']}</code></td>
                <td>{$row['full_name']}</td>
                <td><span class='badge bg-primary'>{$row['role']}</span></td>
                <td>$active</td>
              </tr>";
    }
    echo "</table></div>";
    echo "<p><strong>Login credentials:</strong></p>
          <ul>
            <li><strong>Admin:</strong> <code>admin</code> / <code>Admin@2026</code></li>
            <li><strong>Lecturer:</strong> <code>mr.okeke</code> / <code>Lascohet@2026</code></li>
          </ul>";
} else {
    echo "<div class='alert alert-warning'>No users found. Run install_lascohet.php seed.</div>";
}

// 4. Quick stats
echo "<h5>Database Stats:</h5><div class='row'>";
$stats = [
    'users' => "SELECT COUNT(*) as c FROM users",
    'departments' => "SELECT COUNT(*) as c FROM departments", 
    'programs' => "SELECT COUNT(*) as c FROM programs",
    'students' => "SELECT COUNT(*) as c FROM students",
    'courses' => "SELECT COUNT(*) as c FROM courses"
];
foreach ($stats as $table => $query) {
    $r = $conn->query($query);
    $count = $r ? $r->fetch_assoc()['c'] : 0;
    echo "<div class='col-md-3'>
            <div class='card text-center'>
                <div class='card-body'>
                    <h3 class='text-primary'>$count</h3>
                    <small>$table</small>
                </div>
            </div>
          </div>";
}
echo "</div>";

echo "<hr>
    <div class='alert alert-success'>
        <h6>✅ All set! Test login:</h6>
        <a href='swiftgrade_login.php' class='btn btn-success me-2'>Test SwiftGrade Login</a>
        <a href='index.php' class='btn btn-secondary'>🏠 Home</a>
    </div>";

$conn->close();
echo "</body></html>";
?>

