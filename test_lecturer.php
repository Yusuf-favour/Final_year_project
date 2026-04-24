<?php
require_once 'includes/config.php';

// Check lecturer account
$result = $conn->query("SELECT id, username, role, is_active, full_name FROM users WHERE username='mr.okeke'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "✓ Lecturer found:\n";
    echo "ID: " . $row['id'] . "\n";
    echo "Username: " . $row['username'] . "\n";
    echo "Role: " . $row['role'] . "\n";
    echo "Name: " . $row['full_name'] . "\n";
    echo "Active: " . ($row['is_active'] ? 'Yes' : 'No') . "\n\n";
    
    // Test password
    $test_password = "NextGen@2026";
    $passResult = $conn->query("SELECT password_hash FROM users WHERE id = " . $row['id']);
    if ($passResult) {
        $passRow = $passResult->fetch_assoc();
        $stored_hash = $passRow['password_hash'];
        
        echo "Password hash: " . substr($stored_hash, 0, 20) . "...\n";
        echo "Test password: $test_password\n";
        echo "Bcrypt verify: " . (password_verify($test_password, $stored_hash) ? 'PASS' : 'FAIL') . "\n";
        echo "Plaintext match: " . ($test_password === $stored_hash ? 'PASS' : 'FAIL') . "\n";
    }
} else {
    echo "✗ Lecturer account NOT found\n";
    
    // Show all lecturers
    echo "\nAll lecturer/HOD accounts:\n";
    $all = $conn->query("SELECT id, username, role, full_name FROM users WHERE role IN ('lecturer', 'hod')");
    if ($all && $all->num_rows > 0) {
        while ($row = $all->fetch_assoc()) {
            echo "- " . $row['username'] . " (" . $row['role'] . ") - " . $row['full_name'] . "\n";
        }
    } else {
        echo "No lecturer or HOD accounts found\n";
    }
}
?>
