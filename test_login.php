<?php
// Quick test of swiftgrade_login
session_start();
require_once 'includes/config.php';

echo "<h2>🔍 Troubleshooting mr.okeke Login</h2>";

// Step 1: Check if account exists
echo "<h3>Step 1: Verify Account Exists</h3>";
$check = $conn->query("SELECT id, username, role, is_active, full_name, password_hash FROM users WHERE username='mr.okeke'");

if ($check && $check->num_rows > 0) {
    $user = $check->fetch_assoc();
    echo "✓ Account found<br>";
    echo "- ID: " . $user['id'] . "<br>";
    echo "- Username: " . $user['username'] . "<br>";
    echo "- Role: " . $user['role'] . "<br>";
    echo "- Name: " . $user['full_name'] . "<br>";
    echo "- Active: " . ($user['is_active'] ? 'YES' : 'NO') . "<br>";
    echo "- Password hash length: " . strlen($user['password_hash']) . "<br>";
    
    // Step 2: Test password
    echo "<h3>Step 2: Test Password</h3>";
    $test_pass = "NextGen@2026";
    $verify_bcrypt = password_verify($test_pass, $user['password_hash']);
    $verify_plain = ($test_pass === $user['password_hash']);
    
    echo "Test password: <strong>$test_pass</strong><br>";
    echo "- Bcrypt verify: " . ($verify_bcrypt ? '✓ PASS' : '✗ FAIL') . "<br>";
    echo "- Plaintext match: " . ($verify_plain ? '✓ PASS' : '✗ FAIL') . "<br>";
    
    if (!$verify_bcrypt && !$verify_plain) {
        echo "<h3>⚠️ Password mismatch - FIXING</h3>";
        $new_hash = password_hash($test_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param('si', $new_hash, $user['id']);
        if ($stmt->execute()) {
            echo "✓ Password reset to: <strong>$test_pass</strong><br>";
        }
        $stmt->close();
    }
    
} else {
    echo "✗ Account NOT found - Creating it now...<br>";
    
    $username = 'mr.okeke';
    $full_name = 'Mr. Okeke';
    $password = 'NextGen@2026';
    $role = 'lecturer';
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, full_name, role, password_hash, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param('ssss', $username, $full_name, $role, $password_hash);
    
    if ($stmt->execute()) {
        echo "✓ Account created successfully<br>";
        echo "- Username: $username<br>";
        echo "- Password: $password<br>";
        echo "- Role: $role<br>";
        $user_id = $conn->insert_id;
    } else {
        echo "✗ Failed to create: " . $stmt->error . "<br>";
        exit;
    }
    $stmt->close();
}

// Step 3: Simulate login
echo "<h3>Step 3: Simulate Login</h3>";
$username = 'mr.okeke';
$password = 'NextGen@2026';

$stmt = $conn->prepare(
    "SELECT u.id, u.username, u.password_hash, u.full_name, u.role,
            u.department_id, u.must_change_password, u.is_active
     FROM users u
     LEFT JOIN students s ON s.user_id = u.id
     WHERE u.username = ? OR s.matric_no = ?
     LIMIT 1"
);
$stmt->bind_param('ss', $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "✓ User found by query<br>";
    echo "- ID: " . $row['id'] . "<br>";
    echo "- Current role: " . $row['role'] . "<br>";
    
    if (password_verify($password, $row['password_hash']) || $password === $row['password_hash']) {
        echo "✓ Password verified<br>";
        if ($row['is_active']) {
            echo "✓ Account is active<br>";
            echo "<h3>✅ Login should work now!</h3>";
        } else {
            echo "✗ Account is INACTIVE - Activating...<br>";
            $conn->query("UPDATE users SET is_active=1 WHERE id=" . $row['id']);
            echo "✓ Account activated<br>";
        }
    } else {
        echo "✗ Password verification failed<br>";
    }
} else {
    echo "✗ User not found by login query<br>";
}
$stmt->close();

echo "<hr>";
echo "<p><a href='staff_login.php' class='btn btn-primary'>Go to Login</a></p>";
?>
