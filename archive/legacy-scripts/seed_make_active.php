<?php
require __DIR__ . '/db.php';

if (!isset($conn) || $conn->connect_error) {
    echo "DB connection failed\n";
    exit(1);
}

$conn->set_charset('utf8mb4');

function q1($conn, $sql) {
    $r = $conn->query($sql);
    if (!$r) {
        return null;
    }
    return $r->fetch_assoc();
}

function randDateTime($daysBack = 60) {
    $days = rand(0, max(1, $daysBack));
    $secs = rand(0, 86399);
    $dt = new DateTimeImmutable('now');
    $dt = $dt->sub(new DateInterval('P' . $days . 'D'));
    $dt = $dt->setTime(0, 0, 0)->add(new DateInterval('PT' . $secs . 'S'));
    return $dt->format('Y-m-d H:i:s');
}

$stats = [
    'batches_touched' => 0,
    'results_backdated' => 0,
    'registrations_backdated' => 0,
    'attendance_backdated' => 0,
    'users_last_login_backdated' => 0,
    'audit_inserted' => 0,
];

$currentSem = q1($conn, "SELECT id, semester_number FROM semesters WHERE is_current=1 LIMIT 1");
if (!$currentSem) {
    echo "No current semester configured.\n";
    exit(1);
}
$semId = (int)$currentSem['id'];

$adminId = 1;
$adminRow = q1($conn, "SELECT id FROM users WHERE role='admin' ORDER BY id ASC LIMIT 1");
if ($adminRow) {
    $adminId = (int)$adminRow['id'];
}

$hodId = $adminId;
$hodRow = q1($conn, "SELECT id FROM users WHERE role='hod' ORDER BY id ASC LIMIT 1");
if ($hodRow) {
    $hodId = (int)$hodRow['id'];
}

$lecturers = [];
$lres = $conn->query("SELECT id FROM users WHERE role='lecturer' AND is_active=1");
while ($lres && $row = $lres->fetch_assoc()) {
    $lecturers[] = (int)$row['id'];
}
if (count($lecturers) === 0) {
    $lecturers[] = $adminId;
}

/* ---------- 1) Diversify result batch statuses + timestamps ---------- */
$batches = [];
$bres = $conn->query("SELECT id, course_id, lecturer_id FROM result_batches WHERE semester_id={$semId} ORDER BY id ASC");
while ($bres && $row = $bres->fetch_assoc()) {
    $batches[] = [
        'id' => (int)$row['id'],
        'course_id' => (int)$row['course_id'],
        'lecturer_id' => (int)$row['lecturer_id'],
    ];
}

$statusPattern = ['published','published','published','published','hod_approved','submitted','draft'];
$uBatch = $conn->prepare(
    "UPDATE result_batches
     SET lecturer_id=?, status=?, submitted_at=?, hod_id=?, hod_approved_at=?, published_by=?, published_at=?, updated_at=?
     WHERE id=?"
);

foreach ($batches as $b) {
    $status = $statusPattern[array_rand($statusPattern)];
    $lecturerId = $b['lecturer_id'] > 0 ? $b['lecturer_id'] : $lecturers[array_rand($lecturers)];

    $submittedAt = randDateTime(60);
    $approvedAt = null;
    $publishedAt = null;
    $publishedBy = null;
    $batchHodId = null;

    if ($status === 'submitted') {
        $batchHodId = null;
    } elseif ($status === 'hod_approved' || $status === 'published') {
        $batchHodId = $hodId;
        $approvedAt = randDateTime(45);
        if (strtotime($approvedAt) < strtotime($submittedAt)) {
            $approvedAt = date('Y-m-d H:i:s', strtotime($submittedAt) + rand(3600, 172800));
        }
    }

    if ($status === 'published') {
        $publishedBy = $adminId;
        $publishedAt = randDateTime(30);
        $base = $approvedAt ?: $submittedAt;
        if (strtotime($publishedAt) < strtotime($base)) {
            $publishedAt = date('Y-m-d H:i:s', strtotime($base) + rand(3600, 259200));
        }
    }

    if ($status === 'draft') {
        $submittedAt = null;
        $approvedAt = null;
        $publishedAt = null;
        $publishedBy = null;
        $batchHodId = null;
    }

    $updatedAt = randDateTime(15);

    $uBatch->bind_param(
        'ississssi',
        $lecturerId,
        $status,
        $submittedAt,
        $batchHodId,
        $approvedAt,
        $publishedBy,
        $publishedAt,
        $updatedAt,
        $b['id']
    );
    $uBatch->execute();
    $stats['batches_touched']++;
}
$uBatch->close();

/* ---------- 2) Backdate results + registrations + attendance ---------- */
$conn->query(
    "UPDATE results
     SET created_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND()*60) DAY),
         updated_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND()*20) DAY)
     WHERE semester_id = {$semId}"
);
$stats['results_backdated'] = (int)$conn->affected_rows;

$conn->query(
    "UPDATE course_registrations
     SET created_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND()*90) DAY)
     WHERE semester_id = {$semId}"
);
$stats['registrations_backdated'] = (int)$conn->affected_rows;

$conn->query(
    "UPDATE attendance
     SET created_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND()*45) DAY),
         updated_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND()*10) DAY)
     WHERE semester_id = {$semId}"
);
$stats['attendance_backdated'] = (int)$conn->affected_rows;

/* ---------- 3) Backdate user last_login for realism ---------- */
$conn->query(
    "UPDATE users
     SET last_login = DATE_SUB(NOW(), INTERVAL FLOOR(RAND()*20) DAY)
     WHERE role IN ('admin','hod','lecturer','student') AND is_active=1"
);
$stats['users_last_login_backdated'] = (int)$conn->affected_rows;

/* ---------- 4) Generate audit trail events ---------- */
$users = [];
$ures = $conn->query("SELECT id, role FROM users WHERE is_active=1 AND role IN ('admin','hod','lecturer','student')");
while ($ures && $u = $ures->fetch_assoc()) {
    $users[] = ['id' => (int)$u['id'], 'role' => $u['role']];
}

$actionsByRole = [
    'admin' => ['LOGIN','VIEW_DASHBOARD','PUBLISH_RESULTS','MANAGE_USERS','VIEW_REPORTS'],
    'hod' => ['LOGIN','VIEW_DASHBOARD','REVIEW_BATCH','APPROVE_BATCH','REJECT_BATCH'],
    'lecturer' => ['LOGIN','VIEW_DASHBOARD','SAVE_MARKS','SUBMIT_BATCH','TAKE_ATTENDANCE'],
    'student' => ['LOGIN','VIEW_DASHBOARD','VIEW_RESULTS','REGISTER_COURSE','VIEW_TRANSCRIPT'],
];

$insertAudit = $conn->prepare(
    "INSERT INTO audit_trail (user_id, action, entity_type, entity_id, details, ip_address, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);

$eventTarget = 1500;
$batchIds = array_map(function($b){ return (int)$b['id']; }, $batches);

for ($i = 0; $i < $eventTarget; $i++) {
    if (count($users) === 0) {
        break;
    }
    $u = $users[array_rand($users)];
    $role = $u['role'];
    $action = $actionsByRole[$role][array_rand($actionsByRole[$role])];

    $entityType = 'dashboard';
    $entityId = 0;
    if (in_array($action, ['PUBLISH_RESULTS','REVIEW_BATCH','APPROVE_BATCH','REJECT_BATCH','SAVE_MARKS','SUBMIT_BATCH'], true) && count($batchIds) > 0) {
        $entityType = 'result_batch';
        $entityId = $batchIds[array_rand($batchIds)];
    } elseif ($action === 'TAKE_ATTENDANCE') {
        $entityType = 'attendance';
        $entityId = rand(1, 5000);
    } elseif (in_array($action, ['REGISTER_COURSE','VIEW_RESULTS','VIEW_TRANSCRIPT'], true)) {
        $entityType = 'student_portal';
        $entityId = rand(1, 2000);
    }

    $details = $action . ' activity generated for engagement simulation';
    $ip = '102.' . rand(10, 250) . '.' . rand(10, 250) . '.' . rand(10, 250);
    $createdAt = randDateTime(60);

    $insertAudit->bind_param('ississs', $u['id'], $action, $entityType, $entityId, $details, $ip, $createdAt);
    $insertAudit->execute();
    if ($insertAudit->affected_rows > 0) {
        $stats['audit_inserted']++;
    }
}
$insertAudit->close();

/* ---------- 5) Output summary ---------- */
$statusSummary = [];
$sres = $conn->query("SELECT status, COUNT(*) AS c FROM result_batches WHERE semester_id={$semId} GROUP BY status");
while ($sres && $r = $sres->fetch_assoc()) {
    $statusSummary[$r['status']] = (int)$r['c'];
}

$auditRecent = q1($conn, "SELECT COUNT(*) AS c FROM audit_trail WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)");
$auditTotal = q1($conn, "SELECT COUNT(*) AS c FROM audit_trail");

echo "MAKE ACTIVE COMPLETED\n";
echo "Semester ID: {$semId}\n";
echo "Batches touched: {$stats['batches_touched']}\n";
echo "Results backdated: {$stats['results_backdated']}\n";
echo "Registrations backdated: {$stats['registrations_backdated']}\n";
echo "Attendance backdated: {$stats['attendance_backdated']}\n";
echo "Users last_login backdated: {$stats['users_last_login_backdated']}\n";
echo "Audit events inserted: {$stats['audit_inserted']}\n";
echo "--- Batch status distribution ---\n";
foreach (['draft','submitted','hod_approved','published','rejected'] as $st) {
    $val = isset($statusSummary[$st]) ? $statusSummary[$st] : 0;
    echo $st . ': ' . $val . "\n";
}
echo "--- Audit trail ---\n";
echo "Last 60 days: " . (int)$auditRecent['c'] . "\n";
echo "Total rows: " . (int)$auditTotal['c'] . "\n";
