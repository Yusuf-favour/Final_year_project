<?php
/* ================================================================
   ADMIN – Course Management (CRUD)
   ================================================================ */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$pageTitle = 'Course Management';
$message = '';
$error = '';

/* ===== HANDLE FORM SUBMISSIONS ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* CREATE COURSE */
    if ($action === 'create') {
        $code = strtoupper(trim($_POST['code']));
        $title = trim($_POST['title']);
        $credit_units = (int)$_POST['credit_units'];
        $department_id = (int)$_POST['department_id'];
        $level = (int)$_POST['level'];
        $semester = (int)$_POST['semester'];

        if (empty($code) || empty($title) || $credit_units <= 0) {
            $error = '⚠️ All fields are required!';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO courses (code, title, credit_units, department_id, level, semester)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('ssiiii', $code, $title, $credit_units, $department_id, $level, $semester);
            
            if ($stmt->execute()) {
                $message = '✅ Course created successfully!';
            } else {
                $error = '❌ Error: ' . $stmt->error;
            }
        }
    }

    /* UPDATE COURSE */
    elseif ($action === 'update') {
        $course_id = (int)$_POST['course_id'];
        $title = trim($_POST['title']);
        $credit_units = (int)$_POST['credit_units'];
        $level = (int)$_POST['level'];

        $stmt = $conn->prepare(
            "UPDATE courses SET title = ?, credit_units = ?, level = ? WHERE id = ?"
        );
        $stmt->bind_param('siii', $title, $credit_units, $level, $course_id);
        
        if ($stmt->execute()) {
            $message = '✅ Course updated successfully!';
        } else {
            $error = '❌ Error: ' . $stmt->error;
        }
    }

    /* DELETE COURSE */
    elseif ($action === 'delete') {
        $course_id = (int)$_POST['course_id'];
        
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param('i', $course_id);
        
        if ($stmt->execute()) {
            $message = '✅ Course deleted successfully!';
        } else {
            $error = '❌ Error: ' . $stmt->error;
        }
    }
}

/* Get all courses */
$courses = $conn->query(
    "SELECT c.*, d.name as dept_name, d.code as dept_code
     FROM courses c
     LEFT JOIN departments d ON d.id = c.department_id
     ORDER BY d.name, c.code"
);

/* Get departments for dropdown */
$departments = $conn->query("SELECT id, name, code FROM departments ORDER BY name");

include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/modern-dashboard.css">

<div class="page-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-book-fill"></i> Course Management</h2>
                <small>Create, edit, and manage courses</small>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                <i class="bi bi-plus-circle"></i> Add New Course
            </button>
        </div>
    </div>
</div>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- COURSES TABLE -->
    <div class="card mb-5">
        <div class="card-header">
            <i class="bi bi-list"></i> All Courses (<?= $courses->num_rows ?>)
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Credit Units</th>
                            <th>Level</th>
                            <th>Semester</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <code style="font-weight: bold; color: var(--primary);">
                                    <?= h($course['code']) ?>
                                </code>
                            </td>
                            <td>
                                <strong><?= h($course['title']) ?></strong>
                            </td>
                            <td>
                                <span class="badge" style="background: #E0E7FF; color: #4338CA;">
                                    <?= h($course['dept_code'] ?? 'N/A') ?>
                                </span>
                                <small><?= h($course['dept_name'] ?? '-') ?></small>
                            </td>
                            <td>
                                <strong style="font-size: 1.1rem;"><?= $course['credit_units'] ?></strong>
                                <small class="text-muted"> CU</small>
                            </td>
                            <td>
                                <span class="badge" style="background: #FEF3C7; color: #92400E;">
                                    Level <?= $course['level'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge" style="background: #D1FAE5; color: #065F46;">
                                    <?= $course['semester'] == 1 ? '1st' : '2nd' ?> Sem
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" 
                                            data-bs-target="#editCourseModal" 
                                            onclick="editCourse(<?= htmlspecialchars(json_encode($course)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteCourse(<?= $course['id'] ?>, '<?= h($course['code']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mb-4"></div>
</div>

<!-- ADD COURSE MODAL -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Course Code</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g., CSC101" required>
                        <small class="text-muted">Auto-converted to uppercase</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Course Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Introduction to Computing" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Credit Units</label>
                        <input type="number" name="credit_units" class="form-control" min="1" max="6" value="3" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">-- Select Department --</option>
                            <?php 
                            $departments->data_seek(0);
                            while ($dept = $departments->fetch_assoc()): 
                            ?>
                                <option value="<?= $dept['id'] ?>"><?= h($dept['name']) ?> (<?= h($dept['code']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Level</label>
                        <select name="level" class="form-select" required>
                            <option value="100">100 (First Year)</option>
                            <option value="200">200 (Second Year)</option>
                            <option value="300">300 (Third Year)</option>
                            <option value="400">400 (Fourth Year)</option>
                            <option value="500">500 (Fifth Year)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select" required>
                            <option value="1">1st Semester</option>
                            <option value="2">2nd Semester</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus"></i> Create Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT COURSE MODAL -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="course_id" id="edit_course_id">
                    
                    <div class="alert alert-info mb-3">
                        <small><i class="bi bi-exclamation-circle"></i> Course Code cannot be changed</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Course Code</label>
                        <input type="text" id="edit_course_code" class="form-control" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Course Title</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Credit Units</label>
                        <input type="number" name="credit_units" id="edit_credit_units" class="form-control" min="1" max="6" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Level</label>
                        <select name="level" id="edit_level" class="form-select" required>
                            <option value="100">100 (First Year)</option>
                            <option value="200">200 (Second Year)</option>
                            <option value="300">300 (Third Year)</option>
                            <option value="400">400 (Fourth Year)</option>
                            <option value="500">500 (Fifth Year)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Update Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editCourse(course) {
    document.getElementById('edit_course_id').value = course.id;
    document.getElementById('edit_course_code').value = course.code;
    document.getElementById('edit_title').value = course.title;
    document.getElementById('edit_credit_units').value = course.credit_units;
    document.getElementById('edit_level').value = course.level;
}

function deleteCourse(courseId, courseCode) {
    if (confirm('Are you sure you want to delete course ' + courseCode + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="course_id" value="${courseId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
