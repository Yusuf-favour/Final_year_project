<?php
/* ================================================================
   SwiftGrade – Utility Functions
   ================================================================ */

/* ---------- HTML Escape Helper ---------- */
if (!function_exists('h')) {
    /**
     * Escape HTML special characters
     * @param mixed $value
     * @param string $encoding
     * @return string
     */
    function h($value, $encoding = 'UTF-8') {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, $encoding);
    }
}

/* ---------- Current Academic Session ---------- */
if (!function_exists('currentSession')) {
    /**
     * Get current academic session
     * @param mysqli $conn
     * @return string
     */
    function currentSession($conn) {
        if (!$conn) {
            return '2025/2026';
        }
        $r = $conn->query("SELECT session_name FROM academic_sessions WHERE is_current=1 LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) {
            return $row['session_name'];
        }
        return '2025/2026';
    }
}

/* ---------- Current Semester ---------- */
if (!function_exists('currentSemester')) {
    /**
     * Get the active semester with session details.
     * @param mysqli $conn
     * @return array|null
     */
    function currentSemester($conn) {
        if (!$conn) {
            return null;
        }

        $sql = "SELECT sem.id, sem.session_id, sem.semester_number, sem.is_current, a.session_name
                FROM semesters sem
                JOIN academic_sessions a ON a.id = sem.session_id
                WHERE sem.is_current = 1
                ORDER BY a.session_name DESC, sem.semester_number DESC
                LIMIT 1";

        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            return $row;
        }

        $fallback = $conn->query(
            "SELECT sem.id, sem.session_id, sem.semester_number, sem.is_current, a.session_name
             FROM semesters sem
             JOIN academic_sessions a ON a.id = sem.session_id
             WHERE a.is_current = 1
             ORDER BY sem.semester_number ASC
             LIMIT 1"
        );

        if ($fallback && $row = $fallback->fetch_assoc()) {
            return $row;
        }

        return null;
    }
}

/* ---------- Compute Grade & Grade Point (Nigerian System) ---------- */
if (!function_exists('computeGrade')) {
    /**
     * Compute letter grade and grade point from total score
     * @param float $total
     * @return array ['grade' => 'A', 'gp' => 5.0]
     */
    function computeGrade($total, $conn = null) {
        if ($conn) {
            $scale = getGradingScale($conn);
            foreach ($scale as $row) {
                if ($total >= (float)$row['min_score'] && $total <= (float)$row['max_score']) {
                    return [
                        'grade' => $row['grade'],
                        'grade_point' => (float)$row['grade_point'],
                        'remark' => $row['remark'] ?? '',
                        'gp' => (float)$row['grade_point'],
                    ];
                }
            }
        }

        if ($total >= 70) return ['grade' => 'A', 'grade_point' => 5.0, 'remark' => 'Excellent', 'gp' => 5.0];
        if ($total >= 60) return ['grade' => 'B', 'grade_point' => 4.0, 'remark' => 'Very Good', 'gp' => 4.0];
        if ($total >= 50) return ['grade' => 'C', 'grade_point' => 3.0, 'remark' => 'Good', 'gp' => 3.0];
        if ($total >= 45) return ['grade' => 'D', 'grade_point' => 2.0, 'remark' => 'Fair', 'gp' => 2.0];
        if ($total >= 40) return ['grade' => 'E', 'grade_point' => 1.0, 'remark' => 'Pass', 'gp' => 1.0];
        return ['grade' => 'F', 'grade_point' => 0.0, 'remark' => 'Fail', 'gp' => 0.0];
    }
}

/* ---------- Academic Standing ---------- */
if (!function_exists('academicStanding')) {
    /**
     * Get academic standing label from CGPA.
     * @param float $cgpa
     * @return string
     */
    function academicStanding($cgpa) {
        $cgpa = (float)$cgpa;

        if ($cgpa >= 4.50) return 'Excellent';
        if ($cgpa >= 3.50) return 'Very Good';
        if ($cgpa >= 2.50) return 'Good';
        if ($cgpa >= 2.00) return 'Pass';
        return 'Fail';
    }
}

/* ---------- Format Date/Time ---------- */
if (!function_exists('formatDate')) {
    /**
     * Format date for display
     * @param string $dateStr
     * @param string $format
     * @return string
     */
    function formatDate($dateStr, $format = 'Y-m-d') {
        if (!$dateStr || $dateStr === '0000-00-00 00:00:00') {
            return '-';
        }
        try {
            return (new DateTime($dateStr))->format($format);
        } catch (Exception $e) {
            return $dateStr;
        }
    }
}

/* ---------- Format DateTime ---------- */
if (!function_exists('formatDateTime')) {
    /**
     * Format datetime for display
     * @param string $dateStr
     * @param string $format
     * @return string
     */
    function formatDateTime($dateStr, $format = 'Y-m-d H:i') {
        return formatDate($dateStr, $format);
    }
}

/* ---------- Status Badge ---------- */
if (!function_exists('statusBadge')) {
    /**
     * Generate Bootstrap badge for status
     * @param string $status
     * @return string
     */
    function statusBadge($status) {
        $badges = [
            'draft'         => 'bg-secondary',
            'submitted'     => 'bg-info',
            'hod_approved'  => 'bg-primary',
            'admin_approved' => 'bg-success',
            'published'     => 'bg-success',
            'rejected'      => 'bg-danger',
            'archived'      => 'bg-dark',
            'active'        => 'bg-success',
            'inactive'      => 'bg-secondary',
            'pending'       => 'bg-warning',
            'approved'      => 'bg-success',
        ];
        $class = $badges[strtolower($status)] ?? 'bg-secondary';
        return '<span class="badge ' . h($class) . '">' . h($status) . '</span>';
    }
}

/* ---------- Role Badge ---------- */
if (!function_exists('roleBadge')) {
    /**
     * Generate Bootstrap badge for role
     * @param string $role
     * @return string
     */
    function roleBadge($role) {
        $badges = [
            'student'  => 'bg-primary',
            'lecturer' => 'bg-info',
            'hod'      => 'bg-warning text-dark',
            'admin'    => 'bg-danger',
            'staff'    => 'bg-secondary',
        ];
        $class = $badges[strtolower($role)] ?? 'bg-secondary';
        return '<span class="badge ' . h($class) . '">' . h($role) . '</span>';
    }
}

/* ---------- Sanitize Input ---------- */
if (!function_exists('sanitize')) {
    /**
     * Sanitize user input
     * @param mixed $value
     * @return string
     */
    function sanitize($value) {
        if (is_array($value)) {
            return array_map('sanitize', $value);
        }
        return trim(strip_tags($value));
    }
}

/* ---------- Verify CSRF Token ---------- */
if (!function_exists('generateCSRFToken')) {
    /**
     * Generate CSRF token for form
     * @return string
     */
    function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCSRFToken')) {
    /**
     * Verify CSRF token
     * @param string $token
     * @return bool
     */
    function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
}

/* ---------- CSRF field helper ---------- */
if (!function_exists('csrfField')) {
    /**
     * Render hidden CSRF input.
     * @return string
     */
    function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . h(generateCSRFToken()) . '">';
    }
}

/* ---------- CSRF request verifier ---------- */
if (!function_exists('verifyCSRF')) {
    /**
     * Verify current request CSRF token.
     * @return void
     */
    function verifyCSRF() {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            http_response_code(419);
            exit('Invalid or expired form submission. Please try again.');
        }
    }
}

/* ---------- Pagination Helper ---------- */
if (!function_exists('getPagination')) {
    /**
     * Calculate pagination parameters
     * @param int $total Total records
     * @param int $page Current page
     * @param int $perPage Records per page
     * @return array
     */
    function getPagination($total, $page = 1, $perPage = 20) {
        $page = max(1, intval($page));
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        return [
            'page'       => $page,
            'perPage'    => $perPage,
            'total'      => $total,
            'totalPages' => $totalPages,
            'offset'     => $offset,
            'hasNext'    => $page < $totalPages,
            'hasPrev'    => $page > 1,
        ];
    }
}

/* ---------- Build Pagination Links ---------- */
if (!function_exists('paginationLinks')) {
    /**
     * Generate pagination HTML
     * @param array $pagination
     * @param string $baseURL
     * @return string
     */
    function paginationLinks($pagination, $baseURL) {
        if ($pagination['totalPages'] <= 1) {
            return '';
        }
        
        $html = '<nav><ul class="pagination">';
        
        /* Previous */
        if ($pagination['hasPrev']) {
            $html .= '<li class="page-item"><a class="page-link" href="' . h($baseURL) . '?page=' . ($pagination['page'] - 1) . '">Previous</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
        }
        
        /* Pages */
        for ($i = 1; $i <= $pagination['totalPages']; $i++) {
            $active = $i === $pagination['page'] ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . h($baseURL) . '?page=' . $i . '">' . $i . '</a></li>';
        }
        
        /* Next */
        if ($pagination['hasNext']) {
            $html .= '<li class="page-item"><a class="page-link" href="' . h($baseURL) . '?page=' . ($pagination['page'] + 1) . '">Next</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
        }
        
        $html .= '</ul></nav>';
        return $html;
    }
}

/* ---------- Log Audit Trail ---------- */
if (!function_exists('logAudit')) {
    /**
     * Log action to audit trail
     * @param mysqli $conn
     * @param string $action
     * @param string $entityType
     * @param int $entityId
     * @param string $details
     * @param int $userId
     * @return bool
     */
    function logAudit($conn, $action, $entityType, $entityId, $details = '', $userId = null) {
        if (!$conn) {
            return false;
        }
        
        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $stmt = $conn->prepare(
            "INSERT INTO audit_trail (user_id, action, entity_type, entity_id, details, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('isisss', $userId, $action, $entityType, $entityId, $details, $ip);
        return $stmt->execute();
    }
}

/* ---------- Get Grading Scale ---------- */
if (!function_exists('getGradingScale')) {
    /**
     * Get grading scale for institution
     * @param mysqli $conn
     * @return array
     */
    function getGradingScale($conn) {
        if (!$conn) {
            return [];
        }
        
        $result = $conn->query("
            SELECT grade, grade_point, min_score, max_score
            FROM grading_scale
            ORDER BY grade_point DESC
        ");
        
        $scales = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $scales[] = $row;
            }
        }
        
        return $scales;
    }
}

/* ---------- Format Currency ---------- */
if (!function_exists('formatCurrency')) {
    /**
     * Format currency for display
     * @param float $amount
     * @param string $currency
     * @return string
     */
    function formatCurrency($amount, $currency = '₦') {
        return $currency . ' ' . number_format($amount, 2);
    }
}

/* ---------- Get Flash Message ---------- */
if (!function_exists('getFlash')) {
    /**
     * Get and clear flash message
     * @param string $key
     * @return string
     */
    function getFlash($key = 'message') {
        $msg = $_SESSION[$key] ?? '';
        unset($_SESSION[$key]);
        return $msg;
    }
}

/* ---------- Set Flash Message ---------- */
if (!function_exists('setFlash')) {
    /**
     * Set flash message
     * @param string $message
     * @param string $type
     * @return void
     */
    function setFlash($message, $type = 'info') {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
}

/* ---------- Escape JavaScript String ---------- */
if (!function_exists('js')) {
    /**
     * Escape for JavaScript
     * @param string $value
     * @return string
     */
    function js($value) {
        return json_encode($value);
    }
}

/* ---------- Get current user's full name ---------- */
if (!function_exists('currentFullName')) {
    /**
     * Get current authenticated user's full name
     * @return string
     */
    function currentFullName() {
        return $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
    }
}

/* ---------- Get current academic session helper ---------- */
if (!function_exists('getCurrentSession')) {
    /**
     * Get current academic session (alias for currentSession)
     * @param mysqli $conn
     * @return string
     */
    function getCurrentSession($conn) {
        return currentSession($conn);
    }
}

/* ---------- Current user id alias ---------- */
if (!function_exists('currentUserId')) {
    /**
     * Get current authenticated user id.
     * @return int|null
     */
    function currentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}

/* ---------- Next semester resolver ---------- */
if (!function_exists('nextSemesterId')) {
    /**
     * Resolve the semester id immediately after a given semester.
     * @param mysqli $conn
     * @param int $semesterId
     * @return int|null
     */
    function nextSemesterId($conn, $semesterId) {
        $semesterId = (int)$semesterId;
        if (!$conn || $semesterId <= 0) {
            return null;
        }

        $stmt = $conn->prepare(
            "SELECT s2.id
             FROM semesters s1
             JOIN semesters s2
               ON s2.session_id = s1.session_id
              AND s2.semester_number = s1.semester_number + 1
             WHERE s1.id = ?
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $semesterId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!empty($row['id'])) {
            return (int)$row['id'];
        }

        $stmt = $conn->prepare(
            "SELECT s2.id
             FROM semesters s1
             JOIN academic_sessions a1 ON a1.id = s1.session_id
             JOIN academic_sessions a2 ON a2.id > a1.id
             JOIN semesters s2 ON s2.session_id = a2.id AND s2.semester_number = 1
             WHERE s1.id = ?
             ORDER BY a2.id ASC
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $semesterId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return !empty($row['id']) ? (int)$row['id'] : null;
    }
}

/* ---------- Auto register outstanding carry-overs ---------- */
if (!function_exists('autoRegisterOutstandingCourses')) {
    /**
     * Register all outstanding failed courses into a target semester.
     * Only published results are considered.
     * @param mysqli $conn
     * @param int $studentId
     * @param int $targetSemesterId
     * @return int
     */
    function autoRegisterOutstandingCourses($conn, $studentId, $targetSemesterId) {
        $studentId = (int)$studentId;
        $targetSemesterId = (int)$targetSemesterId;

        if (!$conn || $studentId <= 0 || $targetSemesterId <= 0) {
            return 0;
        }

        $sql = "SELECT DISTINCT fr.course_id
                FROM results fr
                JOIN result_batches fb ON fb.id = fr.batch_id AND fb.status = 'published'
                JOIN semesters fs ON fs.id = fr.semester_id
                JOIN semesters ts ON ts.id = ?
                WHERE fr.student_id = ?
                  AND fr.grade = 'F'
                  AND (
                        fs.session_id < ts.session_id
                        OR (fs.session_id = ts.session_id AND fs.semester_number < ts.semester_number)
                      )
                  AND NOT EXISTS (
                        SELECT 1
                        FROM results pr
                        JOIN result_batches pb ON pb.id = pr.batch_id AND pb.status = 'published'
                        JOIN semesters ps ON ps.id = pr.semester_id
                        WHERE pr.student_id = fr.student_id
                          AND pr.course_id = fr.course_id
                          AND pr.grade IS NOT NULL
                          AND pr.grade <> 'F'
                          AND (
                                ps.session_id < ts.session_id
                                OR (ps.session_id = ts.session_id AND ps.semester_number <= ts.semester_number)
                              )
                  )";

        $failedStmt = $conn->prepare($sql);
        if (!$failedStmt) {
            return 0;
        }

        $failedStmt->bind_param('ii', $targetSemesterId, $studentId);
        $failedStmt->execute();
        $failedRes = $failedStmt->get_result();

        $checkStmt = $conn->prepare(
            "SELECT id
             FROM course_registrations
             WHERE student_id = ? AND course_id = ? AND semester_id = ?
             LIMIT 1"
        );
        $insertStmt = $conn->prepare(
            "INSERT INTO course_registrations (student_id, course_id, semester_id)
             VALUES (?, ?, ?)"
        );

        if (!$checkStmt || !$insertStmt) {
            $failedStmt->close();
            return 0;
        }

        $inserted = 0;
        while ($row = $failedRes->fetch_assoc()) {
            $courseId = (int)$row['course_id'];

            $checkStmt->bind_param('iii', $studentId, $courseId, $targetSemesterId);
            $checkStmt->execute();
            $exists = $checkStmt->get_result();

            if ($exists->num_rows === 0) {
                $insertStmt->bind_param('iii', $studentId, $courseId, $targetSemesterId);
                if ($insertStmt->execute()) {
                    $inserted++;
                }
            }
        }

        $checkStmt->close();
        $insertStmt->close();
        $failedStmt->close();

        return $inserted;
    }
}

/* ---------- Carry over failed courses from a published batch ---------- */
if (!function_exists('carryOverFailedCoursesForBatch')) {
    /**
     * Move failed courses in a batch to each student's next semester registration.
     * @param mysqli $conn
     * @param int $batchId
     * @return int
     */
    function carryOverFailedCoursesForBatch($conn, $batchId) {
        $batchId = (int)$batchId;
        if (!$conn || $batchId <= 0) {
            return 0;
        }

        $stmt = $conn->prepare(
            "SELECT student_id, course_id, semester_id
             FROM results
             WHERE batch_id = ? AND grade = 'F'"
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('i', $batchId);
        $stmt->execute();
        $failed = $stmt->get_result();

        $checkStmt = $conn->prepare(
            "SELECT id FROM course_registrations
             WHERE student_id = ? AND course_id = ? AND semester_id = ?
             LIMIT 1"
        );
        $insertStmt = $conn->prepare(
            "INSERT INTO course_registrations (student_id, course_id, semester_id)
             VALUES (?, ?, ?)"
        );

        if (!$checkStmt || !$insertStmt) {
            $stmt->close();
            return 0;
        }

        $moved = 0;
        while ($row = $failed->fetch_assoc()) {
            $studentId = (int)$row['student_id'];
            $courseId = (int)$row['course_id'];
            $currentSemId = (int)$row['semester_id'];
            $nextSemId = nextSemesterId($conn, $currentSemId);

            if (!$nextSemId) {
                continue;
            }

            $checkStmt->bind_param('iii', $studentId, $courseId, $nextSemId);
            $checkStmt->execute();
            $exists = $checkStmt->get_result();

            if ($exists->num_rows === 0) {
                $insertStmt->bind_param('iii', $studentId, $courseId, $nextSemId);
                if ($insertStmt->execute()) {
                    $moved++;
                }
            }
        }

        $checkStmt->close();
        $insertStmt->close();
        $stmt->close();

        return $moved;
    }
}

?>
