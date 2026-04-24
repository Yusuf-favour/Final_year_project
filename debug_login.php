<?php
/**
 * SwiftGrade Login Debug Tool
 * Tests config.php connection + users table + shows exact error
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head>
<title>Login Debug</title>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
</head><body class='container mt-4'>";

echo '<h2>🔍 SwiftGrade Login Debug</h2>';

try {
    echo '<div class="alert alert-info"><strong>1. Testing config.php...</strong></div>';
    require_once __DIR__ . '/includes/config.php';
    echo '<span class="badge bg-success">✅ config.php loaded</span><br>';
    
    echo '<div class="alert alert-info mt-3"><strong>2. Testing DB connection...</strong></div>';
    echo "DB_NAME: <code>" . DB_NAME . "</code><br>";
    
    if (isset($conn) && $conn instanceof mysqli) {
        echo '<span class="badge bg-success">✅ $conn exists</span><br>';
        echo 'Connection error: ' . ($conn->connect_error ?: '<span class="badge bg-success">None</span>') . '<br>';
        
        // Test users table
        echo '<div class="alert alert-info mt-3"><strong>3. Testing users table...</strong></div>';
        $r = $conn->query("SHOW TABLES LIKE 'users'");
        if ($r && $r->num_rows > 0) {
            echo '<span class="badge bg-success ms-2">✅ users table exists</span><br>';
            
            // Show users structure
            $r = $conn->query("DESCRIBE users");
            if ($r) {
                echo '<table class="table table-sm mt-2">';
                echo '<tr><th>Field</th><th>Type</th></tr>';
                while ($row = $r->fetch_assoc()) {
                    $inst = strpos($row['Field'], 'institution_id') !== false ? '<span class="badge bg-warning">HAS institution_id</span>' : '<span class="badge bg-secondary">no institution_id</span>';
                    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']} $inst</td></tr>";
                }
                echo '</table>';
            }
            
            // Count users
            $r = $conn->query("SELECT COUNT(*) as c FROM users");
            $count = $r->fetch_assoc()['c'];
            echo "<strong>Users count:</strong> <span class='badge bg-primary'>$count</span><br>";
            
            $r = $conn->query("SELECT username FROM users LIMIT 5");
            echo '<strong>First users:</strong><ul>';
            while ($row = $r->fetch_assoc()) echo "<li><code>{$row['username']}</code></li>";
            echo '</ul>';
            
        } else {
            echo '<span class="badge bg-danger ms-2">❌ users table MISSING</span><br>';
            echo '<div class="alert alert-warning mt-2">';
            echo 'Run <a href="install_lascohet.php" class="btn btn-success">install_lascohet.php</a> first!';
            echo '</div>';
        }
    } else {
        echo '<span class="badge bg-danger">❌ $conn not set or invalid</span>';
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger"><strong>Exception:</strong> ' . $e->getMessage() . '</div>';
}

echo '<hr><div class="alert alert-success">
    <h6>✅ Debug complete</h6>
    <a href="install_lascohet.php" class="btn btn-success me-2">1. Install DB</a>
    <a href="describe_lascohet.php" class="btn btn-info me-2">2. Verify</a>  
    <a href="swiftgrade_login.php" class="btn btn-primary">3. Login</a>
</div>';

echo '</body></html>';
?>

