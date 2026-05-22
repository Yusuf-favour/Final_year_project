<?php
// Run this script ONCE to auto-create missing student profiles for all student users
require_once __DIR__ . '/includes/config.php';

// Get current session and semester for defaults
$sessionQ = $conn->query("SELECT id FROM academic_sessions WHERE is_current=1 LIMIT 1");
$session = $sessionQ && $sessionQ->num_rows ? $sessionQ->fetch_assoc() : ['id' => 1];
$sessionId = (int)$session['id'];

$programQ = $conn->query("SELECT id, department_id FROM programs LIMIT 1");
$program = $programQ && $programQ->num_rows ? $programQ->fetch_assoc() : ['id' => 1, 'department_id' => 1];
$programId = (int)$program['id'];
$departmentId = (int)$program['department_id'];

// Find all student users without a profile
$sql = "SELECT u.id, u.username FROM users u
        LEFT JOIN students s ON s.user_id = u.id
        WHERE u.role = 'student' AND s.id IS NULL";
$res = $conn->query($sql);
$count = 0;
while ($row = $res->fetch_assoc()) {
    $userId = (int)$row['id'];
    $username = $row['username'];
    // Generate a matric number (simple example)
    $matric = 'SGU/' . date('Y') . '/' . str_pad($userId, 4, '0', STR_PAD_LEFT);
    $admYear = date('Y');
    $level = 100;
    $stmt = $conn->prepare("INSERT INTO students (user_id, matric_no, department_id, program_id, level, admission_year, entry_session_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isiiiii', $userId, $matric, $departmentId, $programId, $level, $admYear, $sessionId);
    if ($stmt->execute()) {
        echo "Created profile for $username (matric: $matric)\n";
        $count++;
    } else {
        echo "Failed for $username: " . $stmt->error . "\n";
    }
    $stmt->close();
}
if ($count === 0) {
    echo "No missing student profiles found.\n";
} else {
    echo "Created $count student profile(s).\n";
}
