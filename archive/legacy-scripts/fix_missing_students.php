<?php
// Fix missing student profiles for all users with role='student' and no profile
require_once __DIR__ . '/includes/config.php';

// List of students to fix (username, full_name, department_code, program_code, matric_no)
$students = [
    ['adesanya.john',   'Adesanya John Olusegun', 'CHE', 'ND-CHE', 'LAS/CHE/2025/001'],
    ['bello.fatima',    'Bello Fatima Aisha',     'CHE', 'ND-CHE', 'LAS/CHE/2025/002'],
    ['chukwuma.grace',  'Chukwuma Grace Nkechi',  'MLT', 'ND-MLT', 'LAS/MLT/2025/001'],
    ['danladi.musa',    'Danladi Musa Abdullahi', 'MLT', 'ND-MLT', 'LAS/MLT/2025/002'],
    ['eze.blessing',    'Eze Blessing Obioma',    'PHT', 'ND-PHT', 'LAS/PHT/2025/001'],
];

$level = 100;
$admission_year = 2025;

foreach ($students as [$username, $full_name, $deptCode, $programCode, $matric_no]) {
    $deptStmt = $conn->prepare("SELECT id FROM departments WHERE code=? LIMIT 1");
    $deptStmt->bind_param('s', $deptCode);
    $deptStmt->execute();
    $deptStmt->bind_result($dept_id);
    $deptFound = $deptStmt->fetch();
    $deptStmt->close();

    $programStmt = $conn->prepare("SELECT id FROM programs WHERE code=? LIMIT 1");
    $programStmt->bind_param('s', $programCode);
    $programStmt->execute();
    $programStmt->bind_result($prog_id);
    $programFound = $programStmt->fetch();
    $programStmt->close();

    if (!$deptFound || !$programFound) {
        echo "Skipped $username: missing department/program mapping ($deptCode / $programCode)\n";
        continue;
    }

    // Get user id
    $q = $conn->prepare("SELECT id FROM users WHERE username=? AND role='student'");
    $q->bind_param('s', $username);
    $q->execute();
    $q->bind_result($user_id);
    if ($q->fetch() && $user_id) {
        // Check if student profile exists
        $q->close();
        $q2 = $conn->prepare("SELECT id FROM students WHERE user_id=?");
        $q2->bind_param('i', $user_id);
        $q2->execute();
        $q2->store_result();
        if ($q2->num_rows == 0) {
            $q2->close();
            $stmt = $conn->prepare("INSERT INTO students (user_id, matric_no, department_id, program_id, level, admission_year) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('isiiii', $user_id, $matric_no, $dept_id, $prog_id, $level, $admission_year);
            if ($stmt->execute()) {
                echo "Fixed: $username ($matric_no)\n";
            } else {
                echo "Error for $username: " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "Already exists: $username\n";
            $q2->close();
        }
    } else {
        echo "User not found: $username\n";
        $q->close();
    }
}

echo "Done.\n";
