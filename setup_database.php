<?php
/* ================================================================
   ONE-SHOT DATABASE SETUP — creates everything from scratch
   Run once:  http://localhost/Student-Management-System/setup_database.php
   ================================================================ */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>
<title>Database Setup</title>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
</head><body class='p-4'>";
echo "<div class='container'><h2>SwiftGrade — Database Setup</h2><hr>";

$ok = 0; $fail = 0;

function msg($text, $type = 'success') {
    global $ok, $fail;
    if ($type === 'success') $ok++; else $fail++;
    $icon = $type === 'success' ? '✅' : '❌';
    echo "<div class='alert alert-$type py-1 px-3 mb-1'>$icon $text</div>";
}

// ── 1. Connect and create database ──────────────────────────
$conn = new mysqli('localhost', 'root', '');
if ($conn->connect_error) { die("Cannot connect to MySQL: " . $conn->connect_error); }
$conn->set_charset('utf8mb4');

if ($conn->query("CREATE DATABASE IF NOT EXISTS `lascohet_results` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    msg("Database <b>lascohet_results</b> ready");
} else {
    msg("Create DB failed: " . $conn->error, 'danger');
}
$conn->select_db('lascohet_results');

// ── 2. Create tables (order matters for FKs) ────────────────
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$tables = [
    'institutions' => "CREATE TABLE IF NOT EXISTS `institutions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(150) NOT NULL,
        `short_name` VARCHAR(50) NOT NULL,
        `institution_type` VARCHAR(50) DEFAULT 'Federal University',
        `email` VARCHAR(150) DEFAULT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `website` VARCHAR(255) DEFAULT NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'departments' => "CREATE TABLE IF NOT EXISTS `departments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(20) NOT NULL UNIQUE,
        `name` VARCHAR(150) NOT NULL,
        `faculty` VARCHAR(150) NOT NULL DEFAULT 'Lagos State College of Health Technology',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'users' => "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `institution_id` INT DEFAULT 1,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `email` VARCHAR(150) DEFAULT NULL,
        `full_name` VARCHAR(200) NOT NULL,
        `role` ENUM('admin','lecturer','hod','student') NOT NULL DEFAULT 'student',
        `department_id` INT DEFAULT NULL,
        `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `last_login` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'programs' => "CREATE TABLE IF NOT EXISTS `programs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `department_id` INT NOT NULL,
        `code` VARCHAR(20) NOT NULL UNIQUE,
        `name` VARCHAR(200) NOT NULL,
        `duration_years` INT NOT NULL DEFAULT 2,
        `degree_type` VARCHAR(50) NOT NULL DEFAULT 'ND',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'academic_sessions' => "CREATE TABLE IF NOT EXISTS `academic_sessions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `session_name` VARCHAR(20) NOT NULL UNIQUE,
        `is_current` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'semesters' => "CREATE TABLE IF NOT EXISTS `semesters` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `session_id` INT NOT NULL,
        `semester_number` TINYINT NOT NULL,
        `is_current` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`session_id`) REFERENCES `academic_sessions`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `session_semester` (`session_id`, `semester_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'students' => "CREATE TABLE IF NOT EXISTS `students` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `matric_no` VARCHAR(50) NOT NULL UNIQUE,
        `department_id` INT DEFAULT NULL,
        `program_id` INT DEFAULT NULL,
        `level` INT NOT NULL DEFAULT 100,
        `admission_year` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'courses' => "CREATE TABLE IF NOT EXISTS `courses` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(20) NOT NULL UNIQUE,
        `title` VARCHAR(200) NOT NULL,
        `credit_units` INT NOT NULL DEFAULT 2,
        `department_id` INT DEFAULT NULL,
        `program_id` INT DEFAULT NULL,
        `level` INT NOT NULL DEFAULT 100,
        `semester` TINYINT NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'course_assignments' => "CREATE TABLE IF NOT EXISTS `course_assignments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `course_id` INT NOT NULL,
        `lecturer_id` INT NOT NULL,
        `semester_id` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`lecturer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`semester_id`) REFERENCES `semesters`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `course_lecturer_sem` (`course_id`, `lecturer_id`, `semester_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'course_registrations' => "CREATE TABLE IF NOT EXISTS `course_registrations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT NOT NULL,
        `course_id` INT NOT NULL,
        `semester_id` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`semester_id`) REFERENCES `semesters`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `student_course_sem` (`student_id`, `course_id`, `semester_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'result_batches' => "CREATE TABLE IF NOT EXISTS `result_batches` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `course_id` INT NOT NULL,
        `semester_id` INT NOT NULL,
        `lecturer_id` INT NOT NULL,
        `status` ENUM('draft','submitted','hod_approved','published','rejected') NOT NULL DEFAULT 'draft',
        `rejection_reason` TEXT DEFAULT NULL,
        `submitted_at` DATETIME DEFAULT NULL,
        `hod_id` INT DEFAULT NULL,
        `hod_approved_at` DATETIME DEFAULT NULL,
        `published_by` INT DEFAULT NULL,
        `published_at` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`),
        FOREIGN KEY (`semester_id`) REFERENCES `semesters`(`id`),
        FOREIGN KEY (`lecturer_id`) REFERENCES `users`(`id`),
        UNIQUE KEY `course_sem_batch` (`course_id`, `semester_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'results' => "CREATE TABLE IF NOT EXISTS `results` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `batch_id` INT NOT NULL,
        `student_id` INT NOT NULL,
        `course_id` INT NOT NULL,
        `semester_id` INT NOT NULL,
        `ca_score` DECIMAL(5,2) DEFAULT 0,
        `exam_score` DECIMAL(5,2) DEFAULT 0,
        `total_score` DECIMAL(5,2) DEFAULT 0,
        `grade` VARCHAR(2) DEFAULT NULL,
        `grade_point` DECIMAL(3,1) DEFAULT NULL,
        `remark` VARCHAR(50) DEFAULT NULL,
        `entered_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`batch_id`) REFERENCES `result_batches`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`student_id`) REFERENCES `students`(`id`),
        FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`),
        FOREIGN KEY (`semester_id`) REFERENCES `semesters`(`id`),
        UNIQUE KEY `student_course_sem_result` (`student_id`, `course_id`, `semester_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'grading_scale' => "CREATE TABLE IF NOT EXISTS `grading_scale` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `min_score` DECIMAL(5,2) NOT NULL,
        `max_score` DECIMAL(5,2) NOT NULL,
        `grade` VARCHAR(2) NOT NULL,
        `grade_point` DECIMAL(3,1) NOT NULL,
        `remark` VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'audit_trail' => "CREATE TABLE IF NOT EXISTS `audit_trail` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT DEFAULT NULL,
        `action` VARCHAR(100) NOT NULL,
        `entity_type` VARCHAR(50) DEFAULT NULL,
        `entity_id` INT DEFAULT NULL,
        `details` TEXT DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        msg("Table <b>$name</b> ready");
    } else {
        msg("Table $name: " . $conn->error, 'danger');
    }
}
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// ── 3. Seed institutions ────────────────────────────────────
echo "<h4 class='mt-3'>Institutions</h4>";
$institutions = [
    ['LASCOHET', 'Lagos State College of Health Technology', 'LASCOHET', 'State University', 'www.lascohet.edu.ng'],
    ['UNIBADAN', 'University of Ibadan', 'UNIBADAN', 'Federal University', 'www.ui.edu.ng'],
    ['ABU', 'Ahmadu Bello University', 'ABU', 'Federal University', 'www.abu.edu.ng'],
    ['OAU', 'Obafemi Awolowo University', 'OAU', 'Federal University', 'www.oauife.edu.ng'],
    ['LASU', 'Lagos State University', 'LASU', 'State University', 'www.lasu.edu.ng'],
    ['YABATECH', 'Yaba College of Technology', 'YABATECH', 'Polytechnic', 'www.yabatech.edu.ng'],
    ['FUTA', 'Federal University of Technology Akure', 'FUTA', 'Federal University', 'www.futa.edu.ng'],
    ['FCELAGS', 'Federal College of Education Lagos', 'FCELAGS', 'College of Education', 'www.fcelags.edu.ng'],
];
foreach ($institutions as $i) {
    $stmt = $conn->prepare("INSERT IGNORE INTO institutions (code, name, short_name, institution_type, website) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssss', $i[0], $i[1], $i[2], $i[3], $i[4]);
    $stmt->execute();
    $stmt->close();
}
msg("Institutions seeded (" . count($institutions) . " entries)");

// ── 4. Seed departments ─────────────────────────────────────
echo "<h4 class='mt-3'>Departments</h4>";
$departments = [
    ['CHE','Community Health Extension'],
    ['DHT','Dental Health Technology'],
    ['EHT','Environmental Health Technology'],
    ['HIM','Health Information Management'],
    ['MLT','Medical Laboratory Technology'],
    ['PHT','Pharmacy Technician'],
    ['PHN','Public Health Nursing'],
];
foreach ($departments as $d) {
    $conn->query("INSERT IGNORE INTO departments (code, name) VALUES ('{$d[0]}', '{$d[1]}')");
}
msg("Departments seeded");

// ── 5. Seed programs ────────────────────────────────────────
$conn->query("INSERT IGNORE INTO programs (department_id,code,name,duration_years,degree_type) VALUES
(1,'ND-CHE','National Diploma in Community Health Extension',2,'ND'),
(2,'ND-DHT','National Diploma in Dental Health Technology',2,'ND'),
(3,'ND-EHT','National Diploma in Environmental Health Technology',2,'ND'),
(4,'ND-HIM','National Diploma in Health Information Management',2,'ND'),
(5,'ND-MLT','National Diploma in Medical Laboratory Technology',2,'ND'),
(6,'ND-PHT','National Diploma in Pharmacy Technician',2,'ND'),
(7,'ND-PHN','National Diploma in Public Health Nursing',2,'ND')");
msg("Programs seeded");

// ── 6. Seed academic session & semesters ─────────────────────
$conn->query("INSERT IGNORE INTO academic_sessions (session_name, is_current) VALUES ('2025/2026', 1)");
$conn->query("INSERT IGNORE INTO semesters (session_id, semester_number, is_current) VALUES (1,1,1),(1,2,0)");
msg("Academic session 2025/2026 ready");

// ── 7. Seed grading scale ───────────────────────────────────
$conn->query("DELETE FROM grading_scale");
$conn->query("INSERT INTO grading_scale (min_score,max_score,grade,grade_point,remark) VALUES
(70,100,'A',5.0,'Excellent'),
(60,69.99,'B',4.0,'Very Good'),
(50,59.99,'C',3.0,'Good'),
(45,49.99,'D',2.0,'Fair'),
(40,44.99,'E',1.0,'Pass'),
(0,39.99,'F',0.0,'Fail')");
msg("Grading scale ready");

// ── 8. Create/fix demo accounts ─────────────────────────────
echo "<h4 class='mt-3'>Demo Accounts</h4>";

// Get LASCOHET institution id
$r = $conn->query("SELECT id FROM institutions WHERE code='LASCOHET' LIMIT 1");
$lascohet_id = $r ? ($r->fetch_assoc()['id'] ?? 1) : 1;

$accounts = [
    ['admin',        'Admin@2026',    'System Administrator',          'admin',    $lascohet_id],
    ['mr.okeke',     'Lascohet@2026', 'Mr. Okeke (Lecturer)',          'lecturer', $lascohet_id],
    ['demo_student', 'demo123',       'Demo Student',                  'student',  $lascohet_id],
];

foreach ($accounts as $a) {
    $hash = password_hash($a[1], PASSWORD_DEFAULT);
    // Delete and recreate to ensure clean state
    $conn->query("DELETE FROM students WHERE user_id IN (SELECT id FROM users WHERE username='{$a[0]}')");
    $conn->query("DELETE FROM users WHERE username='{$a[0]}'");
    $stmt = $conn->prepare("INSERT INTO users (institution_id, username, password_hash, full_name, role, is_active, must_change_password) VALUES (?,?,?,?,?,1,0)");
    $stmt->bind_param('issss', $a[4], $a[0], $hash, $a[3], $a[4]);
    // Oops, role is $a[4] should be $a[4] for inst but we need to fix params
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO users (institution_id, username, password_hash, full_name, role, is_active, must_change_password) VALUES (?, ?, ?, ?, ?, 1, 0)");
    $inst = $a[4];
    $uname = $a[0];
    $pw = $hash;
    $fname = $a[2];
    $role = $a[3];
    $stmt->bind_param('issss', $inst, $uname, $pw, $fname, $role);
    $stmt->execute();
    $uid = $conn->insert_id;
    $stmt->close();

    if ($a[3] === 'student' && $uid > 0) {
        $stmt = $conn->prepare("INSERT INTO students (user_id, matric_no, department_id, program_id, level, admission_year) VALUES (?, 'LSC/ND/SLT/22/001', 1, 1, 100, 2022)");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $stmt->close();
    }

    msg("<b>{$a[0]}</b> / <b>{$a[1]}</b> (role: {$a[3]})");
}

// ── 9. Verify everything works ──────────────────────────────
echo "<h4 class='mt-3'>Verification</h4>";

// Test the exact login query
$sql = "SELECT u.id, u.username, u.password_hash, u.full_name, u.role,
        u.department_id, u.must_change_password, u.is_active
     FROM users u LEFT JOIN students s ON s.user_id = u.id
     WHERE (u.username = ? OR s.matric_no = ?) AND u.institution_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    msg("Login query BROKEN: " . $conn->error, 'danger');
} else {
    $u = 'admin';
    $stmt->bind_param('ssi', $u, $u, $lascohet_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row && password_verify('Admin@2026', $row['password_hash'])) {
        msg("Admin login verified — query + password both work");
    } else {
        msg("Admin login verification failed", 'danger');
    }
    $stmt->close();
}

$total_users = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$total_inst  = $conn->query("SELECT COUNT(*) c FROM institutions")->fetch_assoc()['c'];
msg("Total users: $total_users | Total institutions: $total_inst");

echo "<hr>";
echo "<h3>" . ($fail === 0 ? "✅ ALL DONE — Login should work now!" : "⚠️ Some steps failed, check above") . "</h3>";
echo "<div class='d-flex gap-2 my-3'>
  <a href='swiftgrade_login.php' class='btn btn-success btn-lg'>Go to Login Page</a>
  <a href='index.php' class='btn btn-outline-primary btn-lg'>Home</a>
</div>";

echo "<div class='card mt-3'><div class='card-body'>
<h5>Demo Accounts:</h5>
<table class='table table-sm mb-0'>
<tr><th>Role</th><th>Username</th><th>Password</th></tr>
<tr><td>Admin</td><td><code>admin</code></td><td><code>Admin@2026</code></td></tr>
<tr><td>Lecturer</td><td><code>mr.okeke</code></td><td><code>Lascohet@2026</code></td></tr>
<tr><td>Student</td><td><code>demo_student</code></td><td><code>demo123</code></td></tr>
</table>
<p class='mt-2 mb-0 text-muted'>Select <b>State University → LASCOHET</b> on the login page.</p>
</div></div>";

echo "</div></body></html>";
$conn->close();
