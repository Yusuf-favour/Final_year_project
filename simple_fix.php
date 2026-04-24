<?php
// Ultra simple test - no dependencies
$conn = new mysqli('localhost', 'root', '', 'lascohet_results');
if ($conn->connect_error) die('DB Connection Error: ' . $conn->connect_error);

echo "<h2>Direct Database Test</h2>";

// Delete and recreate
$conn->query("DELETE FROM users WHERE username='mr.okeke'");

$password = 'NextGen@2026';
$hash = password_hash($password, PASSWORD_DEFAULT);

$insert = "INSERT INTO users (username, full_name, role, password_hash, is_active) 
           VALUES ('mr.okeke', 'Mr. Okeke', 'lecturer', '$hash', 1)";

if ($conn->query($insert)) {
    echo "✅ Account created<br>";
    
    // Verify
    $check = $conn->query("SELECT id, username, role, password_hash FROM users WHERE username='mr.okeke'");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        echo "✓ Verified in database<br>";
        echo "ID: " . $row['id'] . "<br>";
        echo "Username: " . $row['username'] . "<br>";
        echo "Role: " . $row['role'] . "<br>";
        
        // Test password
        if (password_verify($password, $row['password_hash'])) {
            echo "✓ Password verification works<br>";
            echo "<h3>✅ NOW TRY LOGIN</h3>";
            echo "<a href='swiftgrade_login.php'>Go to swiftgrade_login.php</a><br>";
            echo "Username: mr.okeke<br>";
            echo "Password: NextGen@2026";
        } else {
            echo "✗ Password verification failed";
        }
    }
} else {
    echo "❌ Insert error: " . $conn->error;
}
$conn->close();
?>
