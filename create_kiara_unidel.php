<?php
include 'db.php';

$username = 'kiara';
$password = 'kiara123';
$fullName = 'Kiara Student';
$email = 'kiara@unidel.edu';
$role = 'student';
$deptId = 1; // Assume exists
$programId = 1;
$level = 100;
$admissionYear = date('Y');

// Get current session
$sessQ = $conn->query("SELECT id FROM academic_sessions WHERE is_current=1 LIMIT 1");
$sess = $sessQ && $sessQ->num_rows ? $sessQ->fetch_assoc() : ['id' => 1];
$entrySessionId = (int)$sess['id'];

// Check exists
$check = $conn->prepare("SELECT id FROM users WHERE username=?");
$check->bind_param('s', $username);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo "User kiara already exists in unidel_sarms.\n";
    exit;
}
$check->close();

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, full_name, role, department_id, must_change_password, is_active) VALUES (?, ?, ?, ?, ?, ?, 0, 1)");
$stmt->bind_param('sssssi', $username, $hash, $email, $fullName, $role, $deptId);
if ($stmt->execute()) {
    $userId = $conn->insert_id;
    $matric = 'SGU/' . date('Y') . '/1001';
    $sstmt = $conn->prepare("INSERT INTO students (user_id, matric_no, department_id, program_id, level, admission_year, entry_session_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $sstmt->bind_param('isiiiii', $userId, $matric, $deptId, $programId, $level, $admissionYear, $entrySessionId);
    if ($sstmt->execute()) {
        echo "Kiara created in unidel_sarms!\n";
        echo "Username: $username\nPassword: $password\nMatric: $matric\n";
    } else {
        echo "User created but students insert failed: " . $sstmt->error . "\n";
    }
    $sstmt->close();
} else {
    echo "User insert failed: " . $stmt->error . "\n";
}
$stmt->close();
?>

