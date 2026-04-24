<?php
require_once 'includes/config.php';

echo "<h2>Direct Login Test</h2>";

// Test 1: Direct query check
echo "<h3>1. Direct Database Query</h3>";
$result = $conn->query("SELECT id, username, role, is_active, password_hash, full_name FROM users WHERE username='mr.okeke'");

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "Found mr.okeke:<br>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Active: " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
    echo "Password hash: " . substr($user['password_hash'], 0, 30) . "...<br>";
    echo "Hash length: " . strlen($user['password_hash']) . "<br>";
} else {
    echo "❌ NOT FOUND<br>";
    echo "Creating account now...<br>";
    
    $username = 'mr.okeke';
    $full_name = 'Mr. Okeke';
    $password = 'NextGen@2026';
    $role = 'lecturer';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $insert = $conn->prepare("INSERT INTO users (username, full_name, role, password_hash, is_active) VALUES (?, ?, ?, ?, 1)");
    $insert->bind_param('ssss', $username, $full_name, $role, $hash);
    
    if ($insert->execute()) {
        echo "✓ Account created<br>";
        $user_id = $conn->insert_id;
        $user = array(
            'id' => $user_id,
            'username' => 'mr.okeke',
            'role' => 'lecturer',
            'is_active' => 1,
            'password_hash' => $hash,
            'full_name' => 'Mr. Okeke'
        );
    } else {
        echo "✗ Insert failed: " . $insert->error . "<br>";
        exit;
    }
    $insert->close();
}

// Test 2: Password verification
echo "<h3>2. Password Verification Test</h3>";
$password_to_test = 'NextGen@2026';
$stored_hash = $user['password_hash'];

echo "Testing password: $password_to_test<br>";
echo "Hash: " . substr($stored_hash, 0, 30) . "...<br>";

$bcrypt_result = password_verify($password_to_test, $stored_hash);
$plain_result = ($password_to_test === $stored_hash);

echo "password_verify(): " . ($bcrypt_result ? 'TRUE ✓' : 'FALSE ✗') . "<br>";
echo "Plaintext match: " . ($plain_result ? 'TRUE ✓' : 'FALSE ✗') . "<br>";

if (!$bcrypt_result && !$plain_result) {
    echo "<strong>Password is WRONG - Fixing it...</strong><br>";
    $new_hash = password_hash($password_to_test, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
    $update->bind_param('si', $new_hash, $user['id']);
    if ($update->execute()) {
        echo "✓ Password updated<br>";
    }
    $update->close();
}

// Test 3: Simulate the actual login query from swiftgrade_login.php
echo "<h3>3. Simulate Actual Login Query</h3>";

$username_input = 'mr.okeke';
$password_input = 'NextGen@2026';

$stmt = $conn->prepare(
    "SELECT u.id, u.username, u.password_hash, u.full_name, u.role,
            u.department_id, u.must_change_password, u.is_active
     FROM users u
     LEFT JOIN students s ON s.user_id = u.id
     WHERE u.username = ? OR s.matric_no = ?
     LIMIT 1"
);

if (!$stmt) {
    echo "Prepare error: " . $conn->error . "<br>";
} else {
    $stmt->bind_param('ss', $username_input, $username_input);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "❌ Query returned NO RESULTS<br>";
    } else {
        $row = $result->fetch_assoc();
        echo "✓ Query found user: " . $row['username'] . "<br>";
        echo "Role: " . $row['role'] . "<br>";
        
        // Test password
        $pass_check = password_verify($password_input, $row['password_hash']) || $password_input === $row['password_hash'];
        echo "Password verified: " . ($pass_check ? 'TRUE ✓' : 'FALSE ✗') . "<br>";
        
        if (!$pass_check) {
            echo "<strong style='color:red;'>❌ PASSWORD CHECK FAILED</strong><br>";
            echo "Trying to fix the password...<br>";
            
            // Directly update with new hash
            $new_hash = password_hash($password_input, PASSWORD_DEFAULT);
            $fix = $conn->query("UPDATE users SET password_hash='$new_hash' WHERE id=" . $row['id']);
            if ($fix) {
                echo "✓ Password reset<br>";
            } else {
                echo "✗ Failed to reset: " . $conn->error . "<br>";
            }
        }
    }
    $stmt->close();
}

// Final status
echo "<h3>Final Status</h3>";
echo "<a href='swiftgrade_login.php' class='btn btn-primary'>Try Login Again</a><br>";
echo "Username: <strong>mr.okeke</strong><br>";
echo "Password: <strong>NextGen@2026</strong><br>";
?>
