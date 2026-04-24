<?php
require_once __DIR__ . '/includes/config.php';

// List of missing students to add
$students = [
    ['adesanya.john',   'Adesanya John Olusegun', 'CHE', 'ND-CHE', 'LAS/CHE/2025/001'],
    ['bello.fatima',    'Bello Fatima Aisha',     'CHE', 'ND-CHE', 'LAS/CHE/2025/002'],
    ['chukwuma.grace',  'Chukwuma Grace Nkechi',  'MLT', 'ND-MLT', 'LAS/MLT/2025/001'],
    ['danladi.musa',    'Danladi Musa Abdullahi', 'MLT', 'ND-MLT', 'LAS/MLT/2025/002'],
    ['eze.blessing',    'Eze Blessing Obioma',    'PHT', 'ND-PHT', 'LAS/PHT/2025/001'],
];

$level = 100;
$admission_year = 2025;
$password = 'Lascohet@2026';
$hash = password_hash($password, PASSWORD_DEFAULT);

foreach ($students as [$username, $full_name, $deptCode, $programCode, $matric_no]) {
    $deptStmt = $conn->prepare("SELECT id FROM departments WHERE code = ? LIMIT 1");
    $deptStmt->bind_param('s', $deptCode);
    $deptStmt->execute();
    $deptStmt->bind_result($dept_id);
    $deptFound = $deptStmt->fetch();
    $deptStmt->close();

    $progStmt = $conn->prepare("SELECT id FROM programs WHERE code = ? LIMIT 1");
    $progStmt->bind_param('s', $programCode);
    $progStmt->execute();
    $progStmt->bind_result($prog_id);
    $progFound = $progStmt->fetch();
    $progStmt->close();

    if (!$deptFound || !$progFound) {
        echo "Skipped $username: missing department/program mapping ($deptCode / $programCode)\n";
        continue;
    }

    // Check if user exists
    $q = $conn->prepare("SELECT id FROM users WHERE username=?");
    $q->bind_param('s', $username);
    $q->execute();
    $q->store_result();
    if ($q->num_rows == 0) {
        $q->close();
        // Insert user
        $must_change_password = 0;
        $stmt = $conn->prepare("INSERT INTO users (institution_id, username, password_hash, full_name, role, department_id, must_change_password, is_active) VALUES (1, ?, ?, ?, 'student', ?, ?, 1)");
        $stmt->bind_param('sssii', $username, $hash, $full_name, $dept_id, $must_change_password);
        if ($stmt->execute()) {
            $user_id = $conn->insert_id;
            echo "User created: $username\n";
        } else {
            echo "User insert error for $username: " . $stmt->error . "\n";
            continue;
        }
        $stmt->close();
    } else {
        $q->bind_result($user_id);
        $q->fetch();
        $q->close();
        echo "User exists: $username\n";
    }
    // Check if student profile exists
    $q2 = $conn->prepare("SELECT id FROM students WHERE user_id=?");
    $q2->bind_param('i', $user_id);
    $q2->execute();
    $q2->store_result();
    if ($q2->num_rows == 0) {
        $q2->close();
        $stmt2 = $conn->prepare("INSERT INTO students (user_id, matric_no, department_id, program_id, level, admission_year, entry_session_id) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt2->bind_param('isiiii', $user_id, $matric_no, $dept_id, $prog_id, $level, $admission_year);
        if ($stmt2->execute()) {
            echo "Student profile created: $username\n";
        } else {
            echo "Student insert error for $username: " . $stmt2->error . "\n";
        }
        $stmt2->close();
    } else {
        echo "Student profile exists: $username\n";
        $q2->close();
    }
}
echo "Done.\n";
