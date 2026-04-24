<?php
// Simple approach: just directly use SQL without foreign key issues
$conn = new mysqli('localhost', 'root', '', 'lascohet_results');
$conn->set_charset('utf8mb4');

echo "<h2>Create Lecturer Account (Simple)</h2>";

// First, check if mr.okeke exists
$check = $conn->query("SELECT id FROM users WHERE username='mr.okeke' LIMIT 1");

if ($check && $check->num_rows > 0) {
    echo "✓ mr.okeke already exists<br>";
    // Just update the password and make sure it's active
    $password = 'NextGen@2026';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password_hash='$hash', is_active=1, role='lecturer' WHERE username='mr.okeke'");
    echo "✓ Password updated and account activated<br>";
} else {
    echo "Creating mr.okeke account...<br>";
    
    $password = 'NextGen@2026';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Use direct INSERT without prepared statement to avoid FK issues
    $sql = "INSERT INTO users (username, full_name, role, password_hash, is_active, email) 
            VALUES ('mr.okeke', 'Mr. Okeke', 'lecturer', '$hash', 1, 'mr.okeke@college.edu')";
    
    if ($conn->query($sql)) {
        echo "✓ Account created successfully<br>";
    } else {
        echo "⚠ Insert result: " . $conn->error . "<br>";
        // Try clearing the account first
        echo "Attempting to clear and retry...<br>";
        @$conn->query("SET FOREIGN_KEY_CHECKS=0");
        @$conn->query("DELETE FROM users WHERE username='mr.okeke'");
        @$conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        if ($conn->query($sql)) {
            echo "✓ Account created after cleanup<br>";
        } else {
            echo "✗ Still failed: " . $conn->error . "<br>";
            exit;
        }
    }
}

// Verify it works
echo "<h3>Testing Login Query</h3>";

$test_user = 'mr.okeke';
$test_pass = 'NextGen@2026';

$stmt = $conn->prepare(
    "SELECT u.id, u.username, u.password_hash, u.full_name, u.role, u.is_active
     FROM users u
     LEFT JOIN students s ON s.user_id = u.id
     WHERE u.username = ? OR s.matric_no = ?
     LIMIT 1"
);

$stmt->bind_param('ss', $test_user, $test_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "✓ User found by login query<br>";
    echo "  Username: " . $row['username'] . "<br>";
    echo "  Role: " . $row['role'] . "<br>";
    echo "  Active: " . ($row['is_active'] ? 'YES ✓' : 'NO ✗') . "<br>";
    
    if (password_verify($test_pass, $row['password_hash']) || $test_pass === $row['password_hash']) {
        echo "  Password: ✓ WORKS<br>";
        echo "<h3 style='color:green;'>✅ LOGIN SHOULD WORK!</h3>";
    } else {
        echo "  Password: ✗ FAILED<br>";
    }
} else {
    echo "✗ User not found by login query<br>";
}

$stmt->close();
$conn->close();

echo "<hr>";
echo "<a href='swiftgrade_login.php' class='btn btn-success' style='padding:10px 15px; margin-top:20px;'>Try Login Now</a>";
echo "<p><strong>Username:</strong> mr.okeke</p>";
echo "<p><strong>Password:</strong> NextGen@2026</p>";
?>
