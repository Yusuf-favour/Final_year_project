<?php
require_once 'includes/config.php';

$username = 'mr.okeke';
$password = 'NextGen@2026';

// Delete if exists
$conn->query("DELETE FROM users WHERE username='$username'");

// Create fresh
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, full_name, role, password_hash, is_active) VALUES (?, ?, ?, ?, 1)");
$stmt->bind_param('ssss', $u, $n, $r, $h);
$u = $username;
$n = 'Mr. Okeke';
$r = 'lecturer';
$h = $hash;

if ($stmt->execute()) {
    echo "✅ Account reset successfully<br>";
    echo "Username: $username<br>";
    echo "Password: $password<br>";
    echo "Role: lecturer<br><br>";
    echo "<a href='swiftgrade_login.php'>Try login now</a>";
} else {
    echo "❌ Error: " . $stmt->error;
}
$stmt->close();
?>
