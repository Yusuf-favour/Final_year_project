<?php
require_once __DIR__ . '/includes/config.php';

$username = 'student_demo1';
$password = 'student@2026';
$fullName = 'Demo Student 1';
$email = 'student1@swiftgrade.edu';
$role = 'student';
$deptId = 1; // Computer Science
$programId = 1; // B.Sc Computer Science
$level = 100;
$admissionYear = date('Y');

// Get current session for entry_session_id
$sessQ = $conn->query("SELECT id FROM academic_sessions WHERE is_current=1 LIMIT 1");
$sess = $sessQ && $sessQ->num_rows ? $sessQ->fetch_assoc() : ['id' => 1];
$entrySessionId = (int)$sess['id'];

// Check if user already exists
$check = $conn->prepare("SELECT id FROM users WHERE username=?");
$check->bind_param('s', $username);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo "User already exists.\n";
    exit;
}
$check->close();

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, full_name, role, department_id, must_change_password, is_active) VALUES (?, ?, ?, ?, ?, ?, 1, 1)");
$stmt->bind_param('sssssi', $username, $hash, $email, $fullName, $role, $deptId);
if ($stmt->execute()) {
    $userId = $conn->insert_id;
    $matric = 'SGU/' . date('Y') . '/' . str_pad($userId, 4, '0', STR_PAD_LEFT);
    $sstmt = $conn->prepare("INSERT INTO students (user_id, matric_no, department_id, program_id, level, admission_year, entry_session_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $sstmt->bind_param('isiiiii', $userId, $matric, $deptId, $programId, $level, $admissionYear, $entrySessionId);
    if ($sstmt->execute()) {
        echo "Student created!\nUsername: $username\nPassword: $password\nMatric No: $matric\n";
    } else {
        echo "User created, but failed to create student profile: " . $sstmt->error . "\n";
    }
    $sstmt->close();
} else {
    echo "Failed to create user: " . $stmt->error . "\n";
}
$stmt->close();
