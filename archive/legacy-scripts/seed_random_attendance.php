<?php
require __DIR__ . '/db.php';

if (!isset($conn) || $conn->connect_error) {
    echo "DB connection failed\n";
    exit(1);
}

$conn->set_charset('utf8mb4');

$currentSemRes = $conn->query("SELECT id, semester_number FROM semesters WHERE is_current = 1 LIMIT 1");
if (!$currentSemRes || $currentSemRes->num_rows === 0) {
    echo "No current semester found.\n";
    exit(1);
}
$currentSem = $currentSemRes->fetch_assoc();
$semId = (int)$currentSem['id'];

$courseMap = [];
$cres = $conn->query(
    "SELECT rb.course_id, rb.lecturer_id
     FROM result_batches rb
     WHERE rb.semester_id = {$semId}"
);
while ($cres && $row = $cres->fetch_assoc()) {
    $courseMap[(int)$row['course_id']] = (int)$row['lecturer_id'];
}

if (count($courseMap) === 0) {
    $ares = $conn->query(
        "SELECT ca.course_id, ca.lecturer_id
         FROM course_assignments ca
         WHERE ca.semester_id = {$semId}"
    );
    while ($ares && $row = $ares->fetch_assoc()) {
        $courseMap[(int)$row['course_id']] = (int)$row['lecturer_id'];
    }
}

if (count($courseMap) === 0) {
    echo "No course-to-lecturer mapping found for current semester.\n";
    exit(1);
}

$registrations = [];
$rres = $conn->query(
    "SELECT cr.student_id, cr.course_id
     FROM course_registrations cr
     WHERE cr.semester_id = {$semId}"
);
while ($rres && $row = $rres->fetch_assoc()) {
    $cid = (int)$row['course_id'];
    if (!isset($courseMap[$cid])) {
        continue;
    }
    $registrations[] = [
        'student_id' => (int)$row['student_id'],
        'course_id' => $cid,
        'lecturer_id' => (int)$courseMap[$cid],
    ];
}

if (count($registrations) === 0) {
    echo "No course registrations found for attendance seeding.\n";
    exit(1);
}

$days = [1, 2, 3, 4, 5];
$today = new DateTimeImmutable('today');
$classDates = [];

for ($i = 1; $i <= 8; $i++) {
    $base = $today->modify('-' . ($i * 7) . ' days');
    $offset = $days[array_rand($days)];
    $date = $base->modify('+' . $offset . ' days')->format('Y-m-d');
    $classDates[$date] = true;
}

$classDates = array_keys($classDates);
sort($classDates);

$insert = $conn->prepare(
    "INSERT INTO attendance (course_id, student_id, semester_id, attendance_date, is_present, lecturer_id)
     VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE is_present = VALUES(is_present), lecturer_id = VALUES(lecturer_id), updated_at = NOW()"
);

$inserted = 0;
$updated = 0;
$presentCount = 0;
$absentCount = 0;

foreach ($registrations as $reg) {
    foreach ($classDates as $classDate) {
        $present = (mt_rand(1, 100) <= mt_rand(68, 92)) ? 1 : 0;
        $insert->bind_param(
            'iiisii',
            $reg['course_id'],
            $reg['student_id'],
            $semId,
            $classDate,
            $present,
            $reg['lecturer_id']
        );
        $insert->execute();

        if ($insert->affected_rows === 1) {
            $inserted++;
        } elseif ($insert->affected_rows === 2) {
            $updated++;
        }

        if ($present === 1) {
            $presentCount++;
        } else {
            $absentCount++;
        }
    }
}

$insert->close();

$totals = $conn->query(
    "SELECT
        COUNT(*) AS total_rows,
        COUNT(DISTINCT attendance_date) AS total_dates,
        COUNT(DISTINCT course_id) AS total_courses,
        SUM(is_present=1) AS present_rows,
        SUM(is_present=0) AS absent_rows
     FROM attendance
     WHERE semester_id = {$semId}"
)->fetch_assoc();

echo "ATTENDANCE SEED COMPLETED\n";
echo "Semester ID: {$semId}\n";
echo "Registration rows processed: " . count($registrations) . "\n";
echo "Class dates generated: " . count($classDates) . "\n";
echo "Attendance inserted: {$inserted}\n";
echo "Attendance updated: {$updated}\n";
echo "Present marks generated this run: {$presentCount}\n";
echo "Absent marks generated this run: {$absentCount}\n";
echo "---\n";
echo "Current semester attendance totals\n";
echo "Total rows: " . (int)$totals['total_rows'] . "\n";
echo "Unique dates: " . (int)$totals['total_dates'] . "\n";
echo "Courses covered: " . (int)$totals['total_courses'] . "\n";
echo "Present rows: " . (int)$totals['present_rows'] . "\n";
echo "Absent rows: " . (int)$totals['absent_rows'] . "\n";
