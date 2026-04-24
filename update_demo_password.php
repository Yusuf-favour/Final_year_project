<?php
/* Update demo student password */
$conn = new mysqli('localhost', 'root', '', 'lascohet_results');
$conn->set_charset('utf8mb4');

$newPassword = 'adesanya123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$sql = "UPDATE users SET password_hash = ? WHERE username = 'adesanya.john'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $hash);

if ($stmt->execute()) {
    echo "✓ Password updated for adesanya.john<br>";
    echo "New password: <strong>adesanya123</strong>";
} else {
    echo "✗ Error: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
