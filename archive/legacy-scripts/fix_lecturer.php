<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Lecturer Login Fix</h2>";

try {
    require_once 'includes/config.php';
    
    echo "<p>✓ Config loaded</p>";
    
    // Step 1: Delete old account
    echo "<h3>Step 1: Cleaning up old account</h3>";
    $username = 'mr.okeke';
    $delete_result = $conn->query("DELETE FROM users WHERE username='$username'");
    if ($delete_result) {
        echo "<p>✓ Old account deleted (if existed)</p>";
    } else {
        echo "<p>⚠ Delete query: " . $conn->error . "</p>";
    }
    
    // Step 2: Create new account
    echo "<h3>Step 2: Creating new account</h3>";
    
    $password = 'NextGen@2026';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "<p>Password: $password</p>";
    echo "<p>Hash created: " . substr($hash, 0, 30) . "...</p>";
    
    $stmt = $conn->prepare("INSERT INTO users (username, full_name, role, password_hash, is_active) VALUES (?, ?, ?, ?, 1)");
    
    if (!$stmt) {
        echo "<p style='color:red;'>❌ Prepare failed: " . $conn->error . "</p>";
        exit;
    }
    
    $stmt->bind_param('ssss', $u, $n, $r, $h);
    $u = 'mr.okeke';
    $n = 'Mr. Okeke';
    $r = 'lecturer';
    $h = $hash;
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        echo "<p style='color:green;'>✓ Account created successfully!</p>";
        echo "<ul>
            <li>ID: $new_id</li>
            <li>Username: mr.okeke</li>
            <li>Full Name: Mr. Okeke</li>
            <li>Role: lecturer</li>
            <li>Password: $password</li>
        </ul>";
    } else {
        echo "<p style='color:red;'>❌ Execute failed: " . $stmt->error . "</p>";
        exit;
    }
    $stmt->close();
    
    // Step 3: Verify account
    echo "<h3>Step 3: Verifying account</h3>";
    $verify = $conn->query("SELECT id, username, role, is_active FROM users WHERE username='mr.okeke'");
    if ($verify && $verify->num_rows > 0) {
        $v = $verify->fetch_assoc();
        echo "<p style='color:green;'>✓ Account verified in database</p>";
        echo "<ul>
            <li>ID: " . $v['id'] . "</li>
            <li>Username: " . $v['username'] . "</li>
            <li>Role: " . $v['role'] . "</li>
            <li>Active: " . ($v['is_active'] ? 'Yes' : 'No') . "</li>
        </ul>";
    } else {
        echo "<p style='color:red;'>❌ Could not verify account</p>";
    }
    
    echo "<h3>✅ Done!</h3>";
    echo "<p>Now try logging in:</p>";
    echo "<a href='swiftgrade_login.php' class='btn btn-success' style='padding:10px 20px; margin:10px 0;'>Go to Login</a>";
    echo "<p style='margin-top:20px;'><strong>Credentials:</strong></p>";
    echo "<ul>
        <li>Username: <code>mr.okeke</code></li>
        <li>Password: <code>NextGen@2026</code></li>
    </ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
