<?php
require __DIR__ . '/db.php';

if (!isset($conn) || $conn->connect_error) {
    echo "DB connection failed\n";
    exit(1);
}

$conn->set_charset('utf8mb4');

function pickRandom(array $items) {
    return $items[array_rand($items)];
}

function randomNamePart(array $arr) {
    return $arr[array_rand($arr)];
}

function remarkFromGrade($grade) {
    if ($grade === 'A') return 'Excellent';
    if ($grade === 'B') return 'Very Good';
    if ($grade === 'C') return 'Good';
    if ($grade === 'D') return 'Fair';
    if ($grade === 'E') return 'Pass';
    return 'Fail';
}

function uniqueSample(array $arr, $count) {
    $count = min($count, count($arr));
    if ($count <= 0) return [];
    $keys = array_rand($arr, $count);
    if (!is_array($keys)) {
        return [$arr[$keys]];
    }
    $out = [];
    foreach ($keys as $k) {
        $out[] = $arr[$k];
    }
    return $out;
}

$stats = [
    'lecturers_created' => 0,
    'students_created' => 0,
    'course_assignments_added' => 0,
    'batches_created' => 0,
    'registrations_added' => 0,
    'results_inserted' => 0,
    'results_updated' => 0,
];

$createdLecturers = [];

$institutionId = 1;
$inst = $conn->query("SELECT id FROM institutions ORDER BY id ASC LIMIT 1");
if ($inst && $inst->num_rows > 0) {
    $institutionId = (int)$inst->fetch_assoc()['id'];
}

$currentSemRes = $conn->query("SELECT id, session_id, semester_number FROM semesters WHERE is_current = 1 LIMIT 1");
if (!$currentSemRes || $currentSemRes->num_rows === 0) {
    echo "No current semester configured.\n";
    exit(1);
}
$currentSem = $currentSemRes->fetch_assoc();
$semId = (int)$currentSem['id'];
$sessionId = (int)$currentSem['session_id'];
$semesterNumber = (int)$currentSem['semester_number'];

$adminId = 1;
$adminRes = $conn->query("SELECT id FROM users WHERE role='admin' ORDER BY id ASC LIMIT 1");
if ($adminRes && $adminRes->num_rows > 0) {
    $adminId = (int)$adminRes->fetch_assoc()['id'];
}

$hodId = $adminId;
$hodRes = $conn->query("SELECT id FROM users WHERE role='hod' ORDER BY id ASC LIMIT 1");
if ($hodRes && $hodRes->num_rows > 0) {
    $hodId = (int)$hodRes->fetch_assoc()['id'];
}

$departments = [];
$dres = $conn->query("SELECT id, name FROM departments ORDER BY id ASC");
while ($dres && $row = $dres->fetch_assoc()) {
    $departments[] = $row;
}
if (count($departments) === 0) {
    echo "No departments found.\n";
    exit(1);
}

$programs = [];
$pres = $conn->query("SELECT id, department_id, name FROM programs ORDER BY id ASC");
while ($pres && $row = $pres->fetch_assoc()) {
    $programs[] = $row;
}
if (count($programs) === 0) {
    echo "No programs found.\n";
    exit(1);
}

$courses = [];
$cres = $conn->query("SELECT id, code, title, COALESCE(credit_units, unit, 0) AS credit_units, level, semester, department_id FROM courses WHERE semester = {$semesterNumber} ORDER BY id ASC");
while ($cres && $row = $cres->fetch_assoc()) {
    $courses[] = $row;
}
if (count($courses) < 10) {
    $courses = [];
    $cres2 = $conn->query("SELECT id, code, title, COALESCE(credit_units, unit, 0) AS credit_units, level, semester, department_id FROM courses ORDER BY id ASC");
    while ($cres2 && $row = $cres2->fetch_assoc()) {
        $courses[] = $row;
    }
}
if (count($courses) === 0) {
    echo "No courses found.\n";
    exit(1);
}

$lecturerFirstNames = ['Daniel','Samuel','Grace','Helen','Paul','Esther','John','Ibrahim','Aisha','Moses','Ruth','Victor'];
$lecturerLastNames = ['Okoro','Adebayo','Bello','Nwosu','Ojo','Yusuf','Kalu','Mensah','Adetola','Eze','Onyema','Bamidele'];
$defaultLecturerPassword = 'Lecturer@2026';
$lecturerPasswordHash = password_hash($defaultLecturerPassword, PASSWORD_DEFAULT);

$insertLecturer = $conn->prepare("INSERT INTO users (institution_id, username, password_hash, email, full_name, role, department_id, must_change_password, is_active) VALUES (?, ?, ?, ?, ?, 'lecturer', ?, 0, 1)");

for ($i = 0; $i < 5; $i++) {
    $dep = pickRandom($departments);
    $fullName = randomNamePart($lecturerFirstNames) . ' ' . randomNamePart($lecturerLastNames);
    $username = 'lect_' . strtolower(str_replace(' ', '', $fullName)) . '_' . rand(100, 999);
    $email = $username . '@lascohet.edu.ng';

    $insertLecturer->bind_param('issssi', $institutionId, $username, $lecturerPasswordHash, $email, $fullName, $dep['id']);
    if ($insertLecturer->execute()) {
        $lid = (int)$conn->insert_id;
        $createdLecturers[] = ['id' => $lid, 'username' => $username, 'full_name' => $fullName, 'password' => $defaultLecturerPassword];
        $stats['lecturers_created']++;
    }
}
$insertLecturer->close();

if (count($createdLecturers) < 5) {
    $existingLect = $conn->query("SELECT id, username, full_name FROM users WHERE role='lecturer' ORDER BY id DESC LIMIT 5");
    while ($existingLect && $row = $existingLect->fetch_assoc()) {
        $createdLecturers[] = ['id' => (int)$row['id'], 'username' => $row['username'], 'full_name' => $row['full_name'], 'password' => '(existing)'];
    }
}

$lecturerIds = array_map(function($l) { return (int)$l['id']; }, $createdLecturers);
if (count($lecturerIds) === 0) {
    echo "No lecturer accounts available.\n";
    exit(1);
}

$insertAssign = $conn->prepare("INSERT IGNORE INTO course_assignments (course_id, lecturer_id, semester_id) VALUES (?, ?, ?)");
$courseLecturerMap = [];
foreach ($courses as $c) {
    $lid = pickRandom($lecturerIds);
    $insertAssign->bind_param('iii', $c['id'], $lid, $semId);
    $insertAssign->execute();
    if ($insertAssign->affected_rows > 0) {
        $stats['course_assignments_added']++;
    }
    $courseLecturerMap[(int)$c['id']] = $lid;
}
$insertAssign->close();

$insertBatch = $conn->prepare(
    "INSERT INTO result_batches (course_id, semester_id, lecturer_id, status, submitted_at, hod_id, hod_approved_at, published_by, published_at)
     VALUES (?, ?, ?, 'published', NOW(), ?, NOW(), ?, NOW())
     ON DUPLICATE KEY UPDATE lecturer_id=VALUES(lecturer_id), status='published', submitted_at=NOW(), hod_id=VALUES(hod_id), hod_approved_at=NOW(), published_by=VALUES(published_by), published_at=NOW()"
);

foreach ($courses as $c) {
    $courseId = (int)$c['id'];
    $lid = isset($courseLecturerMap[$courseId]) ? (int)$courseLecturerMap[$courseId] : pickRandom($lecturerIds);
    $insertBatch->bind_param('iiiii', $courseId, $semId, $lid, $hodId, $adminId);
    $insertBatch->execute();
    if ($insertBatch->affected_rows > 0) {
        $stats['batches_created']++;
    }
}
$insertBatch->close();

$batchMap = [];
$bres = $conn->query("SELECT id, course_id, lecturer_id FROM result_batches WHERE semester_id = {$semId}");
while ($bres && $row = $bres->fetch_assoc()) {
    $batchMap[(int)$row['course_id']] = ['batch_id' => (int)$row['id'], 'lecturer_id' => (int)$row['lecturer_id']];
}

$studentFirstNames = ['Amina','Chinedu','Ifeoma','Tunde','Kemi','Sadiq','Joy','Ngozi','Peter','Hauwa','Blessing','Mariam','David','Patience','Emeka','Fatima','Henry','Precious','Rashid','Faith'];
$studentLastNames = ['Adeyemi','Okafor','Balogun','Nwankwo','Usman','Eze','Onah','Afolabi','Yakubu','Ogundele','Akinola','Nnamdi','Bello','Musa','Sule','Ibrahim','Ogunleye','Nwosu','Abdullahi','Okoye'];

$insertStudentUser = $conn->prepare("INSERT INTO users (institution_id, username, password_hash, email, full_name, role, department_id, must_change_password, is_active) VALUES (?, ?, ?, ?, ?, 'student', ?, 0, 1)");
$insertStudentProfile = $conn->prepare("INSERT INTO students (user_id, matric_no, department_id, program_id, level, admission_year, entry_session_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

$studentPassword = 'Student@2026';
$studentPasswordHash = password_hash($studentPassword, PASSWORD_DEFAULT);
$newStudents = [];

for ($i = 0; $i < 200; $i++) {
    $prog = pickRandom($programs);
    $deptId = (int)$prog['department_id'];
    $level = pickRandom([100, 200, 300, 400]);
    $admissionYear = (int)date('Y') - (int)($level / 100) + 1;

    $fullName = randomNamePart($studentFirstNames) . ' ' . randomNamePart($studentLastNames) . ' ' . chr(rand(65, 90));
    $username = 'stu_' . strtolower(str_replace(' ', '', $fullName)) . '_' . rand(1000, 9999);
    $matricNo = 'LAS/SM/' . date('y') . '/' . rand(100000, 999999);
    $email = $username . '@student.lascohet.edu.ng';

    $insertStudentUser->bind_param('issssi', $institutionId, $username, $studentPasswordHash, $email, $fullName, $deptId);
    if (!$insertStudentUser->execute()) {
        continue;
    }

    $userId = (int)$conn->insert_id;
    $insertStudentProfile->bind_param('isiiiii', $userId, $matricNo, $deptId, $prog['id'], $level, $admissionYear, $sessionId);
    if (!$insertStudentProfile->execute()) {
        continue;
    }

    $studentId = (int)$conn->insert_id;
    $newStudents[] = ['student_id' => $studentId, 'level' => $level, 'username' => $username, 'full_name' => $fullName, 'matric_no' => $matricNo];
    $stats['students_created']++;
}

$insertStudentUser->close();
$insertStudentProfile->close();

$insertReg = $conn->prepare("INSERT IGNORE INTO course_registrations (student_id, course_id, semester_id) VALUES (?, ?, ?)");
$insertResult = $conn->prepare(
    "INSERT INTO results (batch_id, student_id, course_id, semester_id, ca_score, exam_score, total_score, grade, grade_point, remark, entered_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE ca_score=VALUES(ca_score), exam_score=VALUES(exam_score), total_score=VALUES(total_score), grade=VALUES(grade), grade_point=VALUES(grade_point), remark=VALUES(remark), entered_by=VALUES(entered_by), updated_at=NOW()"
);

foreach ($newStudents as $s) {
    $candidateCourses = array_values(array_filter($courses, function($c) use ($s, $semesterNumber) {
        if ((int)$c['semester'] !== (int)$semesterNumber) {
            return false;
        }
        return (int)$c['level'] <= (int)$s['level'];
    }));

    if (count($candidateCourses) < 5) {
        $candidateCourses = $courses;
    }

    $courseCount = rand(5, min(8, count($candidateCourses)));
    $selected = uniqueSample($candidateCourses, $courseCount);

    foreach ($selected as $c) {
        $courseId = (int)$c['id'];

        $insertReg->bind_param('iii', $s['student_id'], $courseId, $semId);
        $insertReg->execute();
        if ($insertReg->affected_rows > 0) {
            $stats['registrations_added']++;
        }

        if (!isset($batchMap[$courseId])) {
            continue;
        }

        $batchId = (int)$batchMap[$courseId]['batch_id'];
        $enteredBy = (int)$batchMap[$courseId]['lecturer_id'];

        $ca = (float)rand(80, 400) / 10.0;
        if ($ca > 40) {
            $ca = 40;
        }
        $exam = (float)rand(100, 600) / 10.0;
        if ($exam > 60) {
            $exam = 60;
        }
        $total = round($ca + $exam, 2);

        $gradeData = computeGrade($total);
        $grade = $gradeData[0];
        $gp = (float)$gradeData[1];
        $remark = remarkFromGrade($grade);

        $insertResult->bind_param('iiiidddsdsi', $batchId, $s['student_id'], $courseId, $semId, $ca, $exam, $total, $grade, $gp, $remark, $enteredBy);
        $insertResult->execute();
        if ($insertResult->affected_rows === 1) {
            $stats['results_inserted']++;
        } elseif ($insertResult->affected_rows === 2) {
            $stats['results_updated']++;
        }
    }
}

$insertReg->close();
$insertResult->close();

$publishedBatches = 0;
$pubRes = $conn->query("SELECT COUNT(*) AS c FROM result_batches WHERE semester_id = {$semId} AND status='published'");
if ($pubRes && $pubRes->num_rows > 0) {
    $publishedBatches = (int)$pubRes->fetch_assoc()['c'];
}

echo "SEED COMPLETED\n";
echo "Current Semester ID: {$semId} (Semester {$semesterNumber})\n";
echo "Lecturers created: {$stats['lecturers_created']}\n";
echo "Students created: {$stats['students_created']}\n";
echo "Course assignments added: {$stats['course_assignments_added']}\n";
echo "Batches created/updated: {$stats['batches_created']}\n";
echo "Registrations added: {$stats['registrations_added']}\n";
echo "Results inserted: {$stats['results_inserted']}\n";
echo "Results updated: {$stats['results_updated']}\n";
echo "Published batches now: {$publishedBatches}\n";

echo "\nNEW LECTURER LOGINS\n";
foreach ($createdLecturers as $l) {
    echo "- {$l['username']} | {$l['full_name']} | password: {$l['password']}\n";
}

echo "\nSample student login (default password for newly added): {$studentPassword}\n";
$sampleStudents = array_slice($newStudents, 0, 10);
foreach ($sampleStudents as $s) {
    echo "- {$s['username']} | {$s['matric_no']} | {$s['full_name']}\n";
}
