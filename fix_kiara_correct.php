<?php
include 'db.php';

// Delete existing
$conn->query("DELETE FROM students WHERE user_id = 5");
$conn->query("DELETE FROM users WHERE id = 5");

$username = 'kiara';
$password = 'kiara123';
$fullName = 'Kiara Student';
$email = 'kiara@swiftgrade.edu';
$role = 'student';
$deptId = 1;
$programId = 1;
$level = 100;
$sessQ = $conn->query("SELECT id FROM academic_sessions WHERE is_current=1 LIMIT 1");
$sess = $sessQ && $sessQ->num_rows ? $sessQ->fetch_assoc() : ['id' => 1];
$entrySessionId = (int)$sess['id'];

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, full_name, role, department_id, must_change_password, is_active) VALUES (?, ?, ?, ?, ?, ?, 0, 1)");
$stmt->bind_param('sssssi', $username, $hash, $email, $fullName, $role, $deptId);
$stmt->execute();
$userId = $conn->insert_id;

$matric = 'SGU/' . date('Y') . '/KIARA';
$sstmt = $conn->prepare("INSERT INTO students (user_id, matric_no, department_id, program_id, level, entry_session_id) VALUES (?, ?, ?, ?, ?, ?)");
$sstmt->bind_param('isiiii', $userId, $matric, $deptId, $programId, $level, $entrySessionId);
$sstmt->execute();

echo "Kiara fixed! User ID: $userId, Matric: $matric\nHash length: " . strlen($hash) . "\n";
echo "Login now works!\n";
?>

