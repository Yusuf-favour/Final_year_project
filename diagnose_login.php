<?php
// Comprehensive mr.okeke login diagnostic and fixer
session_start();
require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Login Diagnostic</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .card { margin-bottom: 20px; }
        .pass { color: #28a745; font-weight: bold; }
        .fail { color: #dc3545; font-weight: bold; }
        .section { margin: 20px 0; padding: 15px; background: white; border-radius: 5px; }
    </style>
</head>
<body>
<div class='container' style='max-width: 800px;'>
<h1>🔧 Login Diagnostic for mr.okeke</h1>
<hr>";

// ============ STEP 1: Account Check ============
echo "<div class='section'>
<h3>Step 1: Verifying Account</h3>";

$check = $conn->query("SELECT id, username, role, is_active, full_name, password_hash FROM users WHERE username='mr.okeke'");

if (!$check) {
    echo "<p class='fail'>✗ Database query error: " . $conn->error . "</p>";
    exit;
}

if ($check->num_rows === 0) {
    echo "<p class='fail'>✗ Account 'mr.okeke' does NOT exist</p>";
    echo "<p>Creating account...</p>";
    
    $password = 'NextGen@2026';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, full_name, role, password_hash, is_active) VALUES (?, ?, ?, ?, 1)");
    if (!$stmt) {
        echo "<p class='fail'>✗ Prepare failed: " . $conn->error . "</p>";
        exit;
    }
    
    $stmt->bind_param('ssss', $usr, $name, $role, $hash);
    $usr = 'mr.okeke';
    $name = 'Mr. Okeke';
    $role = 'lecturer';
    
    if ($stmt->execute()) {
        echo "<p class='pass'>✓ Account created successfully</p>";
        $user_id = $conn->insert_id;
        $user = array(
            'id' => $user_id,
            'username' => 'mr.okeke',
            'role' => 'lecturer',
            'is_active' => 1,
            'full_name' => 'Mr. Okeke',
            'password_hash' => $hash
        );
    } else {
        echo "<p class='fail'>✗ Failed to create: " . $stmt->error . "</p>";
        exit;
    }
    $stmt->close();
} else {
    $user = $check->fetch_assoc();
    echo "<p class='pass'>✓ Account found</p>";
    echo "<ul>
        <li>ID: " . $user['id'] . "</li>
        <li>Username: " . $user['username'] . "</li>
        <li>Role: " . $user['role'] . "</li>
        <li>Full Name: " . $user['full_name'] . "</li>
        <li>Active: " . ($user['is_active'] ? 'YES' : 'NO') . "</li>
    </ul>";
}
echo "</div>";

// ============ STEP 2: Password Check ============
echo "<div class='section'>
<h3>Step 2: Verifying Password</h3>";

$test_password = 'NextGen@2026';
$hash = $user['password_hash'];

$bcrypt_match = password_verify($test_password, $hash);
$plain_match = ($test_password === $hash);

echo "<p>Test password: <strong>$test_password</strong></p>";
echo "<ul>
    <li>Bcrypt check: " . ($bcrypt_match ? "<span class='pass'>✓ PASS</span>" : "<span class='fail'>✗ FAIL</span>") . "</li>
    <li>Plaintext check: " . ($plain_match ? "<span class='pass'>✓ PASS</span>" : "<span class='fail'>✗ FAIL</span>") . "</li>
</ul>";

if (!$bcrypt_match && !$plain_match) {
    echo "<p>Password incorrect - fixing...</p>";
    $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
    if ($conn->query("UPDATE users SET password_hash='$new_hash' WHERE id=" . $user['id'])) {
        echo "<p class='pass'>✓ Password reset successfully</p>";
    } else {
        echo "<p class='fail'>✗ Failed to reset: " . $conn->error . "</p>";
    }
}
echo "</div>";

// ============ STEP 3: Active Check ============
echo "<div class='section'>
<h3>Step 3: Verifying Account Status</h3>";

if ($user['is_active']) {
    echo "<p class='pass'>✓ Account is ACTIVE</p>";
} else {
    echo "<p class='fail'>✗ Account is INACTIVE - Activating...</p>";
    if ($conn->query("UPDATE users SET is_active=1 WHERE id=" . $user['id'])) {
        echo "<p class='pass'>✓ Account activated</p>";
    } else {
        echo "<p class='fail'>✗ Failed to activate: " . $conn->error . "</p>";
    }
}
echo "</div>";

// ============ STEP 4: Simulate Login ============
echo "<div class='section'>
<h3>Step 4: Simulating Login</h3>";

$dbConn = new mysqli('localhost', 'root', '', 'lascohet_results');
if ($dbConn->connect_error) {
    echo "<p class='fail'>✗ Connection failed: " . $dbConn->connect_error . "</p>";
    exit;
}
$dbConn->set_charset('utf8mb4');

$stmt = $dbConn->prepare(
    "SELECT u.id, u.username, u.password_hash, u.full_name, u.role,
            u.department_id, u.must_change_password, u.is_active
     FROM users u
     LEFT JOIN students s ON s.user_id = u.id
     WHERE u.username = ? OR s.matric_no = ?
     LIMIT 1"
);

if (!$stmt) {
    echo "<p class='fail'>✗ Prepare failed: " . $dbConn->error . "</p>";
    exit;
}

$stmt->bind_param('ss', $username, $username);
$username = 'mr.okeke';
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p class='pass'>✓ User found by login query</p>";
    $row = $result->fetch_assoc();
    
    if (password_verify('NextGen@2026', $row['password_hash']) || 'NextGen@2026' === $row['password_hash']) {
        echo "<p class='pass'>✓ Password verified correctly</p>";
        
        if ($row['is_active']) {
            echo "<p class='pass'>✓ Account is active</p>";
            echo "<p class='pass'><strong>✅ LOGIN SHOULD WORK NOW!</strong></p>";
        } else {
            echo "<p class='fail'>✗ Account is inactive</p>";
        }
    } else {
        echo "<p class='fail'>✗ Password verification failed</p>";
    }
} else {
    echo "<p class='fail'>✗ User not found by login query</p>";
}
$stmt->close();
$dbConn->close();

echo "</div>";

// ============ FINAL INSTRUCTIONS ============
echo "<div class='section alert alert-success'>
<h3>✅ Next Steps</h3>
<p>Try logging in with:</p>
<ul>
    <li><strong>URL:</strong> <a href='staff_login.php'>staff_login.php</a></li>
    <li><strong>Username:</strong> mr.okeke</li>
    <li><strong>Password:</strong> NextGen@2026</li>
</ul>
<p>Or try the universal login: <a href='swiftgrade_login.php'>swiftgrade_login.php</a></p>
</div>";

echo "</div>
</body>
</html>";
?>
