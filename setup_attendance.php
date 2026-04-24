<?php
/* ================================================================
   Database Setup – Ensure Attendance Table Exists
   ================================================================ */
require_once __DIR__ . '/includes/config.php';

$output = [];

try {
    /* Check if attendance table exists */
    $result = $conn->query("SHOW TABLES LIKE 'attendance'");
    
    if ($result && $result->num_rows > 0) {
        $output[] = '✓ Attendance table already exists.';
    } else {
        $output[] = '→ Creating attendance table...';
        
        /* Create attendance table with proper schema */
        $sql = "
        CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            student_id INT NOT NULL,
            semester_id INT NOT NULL,
            attendance_date DATE NOT NULL,
            is_present TINYINT(1) NOT NULL DEFAULT 0,
            lecturer_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_attendance (course_id, student_id, attendance_date),
            CONSTRAINT fk_attendance_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            CONSTRAINT fk_attendance_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
            CONSTRAINT fk_attendance_lecturer FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_attendance_date (attendance_date),
            INDEX idx_attendance_course_semester (course_id, semester_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        if ($conn->query($sql)) {
            $output[] = '✓ Attendance table created successfully.';
        } else {
            throw new Exception("Error creating attendance table: " . $conn->error);
        }
    }

    /* Display results */
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Database Setup</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body style='background: #f5f5f5; padding: 2rem;'>
        <div class='container' style='max-width: 600px;'>
            <div class='card'>
                <div class='card-header bg-success text-white'>
                    <h5 class='mb-0'><i class='bi bi-check-circle'></i> Database Setup Complete</h5>
                </div>
                <div class='card-body'>
                    <ul class='list-unstyled'>
    ";
    
    foreach ($output as $msg) {
        echo "<li style='padding: 0.5rem; margin-bottom: 0.5rem;'>";
        if (strpos($msg, '✓') === 0) {
            echo "<span style='color: #28a745;'>" . htmlspecialchars($msg) . "</span>";
        } elseif (strpos($msg, '→') === 0) {
            echo "<span style='color: #0275d8;'>" . htmlspecialchars($msg) . "</span>";
        } else {
            echo htmlspecialchars($msg);
        }
        echo "</li>";
    }
    
    echo "
                    </ul>
                    <p class='mt-3 mb-0 text-muted'>
                        All database tables are now properly configured. You can safely use the attendance tracking features.
                    </p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";

} catch (Exception $e) {
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Database Setup Error</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body style='background: #f5f5f5; padding: 2rem;'>
        <div class='container' style='max-width: 600px;'>
            <div class='alert alert-danger'>
                <h5><i class='bi bi-exclamation-circle'></i> Setup Error</h5>
                <p>" . htmlspecialchars($e->getMessage()) . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>
