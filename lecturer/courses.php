<?php
/* ================================================================
   LECTURER – My Courses  (list all assigned courses)
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('lecturer','hod');
$pageTitle = 'My Courses';

$courses = $conn->query(
    "SELECT ca.id AS assignment_id, c.id AS course_id, c.code, c.title, c.credit_units,
            c.level, c.semester AS course_sem,
            d.name AS dept_name,
            a.session_name, s.semester_number,
            rb.status AS batch_status
     FROM course_assignments ca
     JOIN courses c ON c.id = ca.course_id
     JOIN departments d ON d.id = c.department_id
     JOIN semesters s ON s.id = ca.semester_id
     JOIN academic_sessions a ON a.id = s.session_id
     LEFT JOIN result_batches rb ON rb.course_id = c.id AND rb.semester_id = ca.semester_id
     WHERE ca.lecturer_id = {$_SESSION['user_id']}
     ORDER BY a.session_name DESC, s.semester_number, c.code"
);

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
<div class="container"><h2><i class="bi bi-book"></i> My Courses</h2></div>
</div>

<div class="container mb-4">
<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead><tr><th>Code</th><th>Title</th><th>CU</th><th>Level</th><th>Department</th><th>Session</th><th>Sem</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php while ($c = $courses->fetch_assoc()): ?>
<tr>
    <td><strong><?= h($c['code']) ?></strong></td>
    <td><?= h($c['title']) ?></td>
    <td><?= $c['credit_units'] ?></td>
    <td><?= $c['level'] ?></td>
    <td><?= h($c['dept_name']) ?></td>
    <td><?= h($c['session_name']) ?></td>
    <td><?= $c['semester_number'] ?></td>
    <td><?= $c['batch_status'] ? statusBadge($c['batch_status']) : '<span class="badge bg-light text-dark">—</span>' ?></td>
    <td><a href="upload.php?course_id=<?= $c['course_id'] ?>" class="btn btn-sm btn-lsc"><i class="bi bi-pencil"></i></a></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
