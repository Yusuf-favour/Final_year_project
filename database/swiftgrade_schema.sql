-- ================================================================
-- SwiftGrade University – Student Result Processing System
-- Clean Schema (compatible with current PHP modules)
-- ================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  full_name VARCHAR(200) NOT NULL,
  role ENUM('admin','lecturer','hod','student') NOT NULL DEFAULT 'student',
  department_id INT DEFAULT NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  faculty VARCHAR(150) NOT NULL DEFAULT 'SwiftGrade University',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE users
  ADD CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS programs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_id INT NOT NULL,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  duration_years INT NOT NULL DEFAULT 4,
  degree_type VARCHAR(50) NOT NULL DEFAULT 'BSc',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_programs_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS academic_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_name VARCHAR(20) NOT NULL UNIQUE,
  is_current TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS semesters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  semester_number TINYINT NOT NULL,
  is_current TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_session_semester (session_id, semester_number),
  CONSTRAINT fk_semesters_session FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  title VARCHAR(200) NOT NULL,
  unit INT NOT NULL DEFAULT 2,
  department_id INT DEFAULT NULL,
  level INT DEFAULT 100,
  semester_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_courses_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  CONSTRAINT fk_courses_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  matric_no VARCHAR(50) NOT NULL UNIQUE,
  department_id INT NOT NULL,
  program_id INT NOT NULL,
  level INT NOT NULL DEFAULT 100,
  entry_session_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_students_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
  CONSTRAINT fk_students_program FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE RESTRICT,
  CONSTRAINT fk_students_entry_session FOREIGN KEY (entry_session_id) REFERENCES academic_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS course_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_id INT NOT NULL,
  semester_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_course_sem (student_id, course_id, semester_id),
  CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_reg_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  CONSTRAINT fk_reg_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS result_batches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  semester_id INT NOT NULL,
  lecturer_id INT NOT NULL,
  hod_id INT DEFAULT NULL,
  published_by INT DEFAULT NULL,
  status ENUM('draft','submitted','approved','published','rejected') NOT NULL DEFAULT 'draft',
  submitted_at DATETIME DEFAULT NULL,
  approved_at DATETIME DEFAULT NULL,
  published_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_batch_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  CONSTRAINT fk_batch_semester FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
  CONSTRAINT fk_batch_lecturer FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_batch_hod FOREIGN KEY (hod_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_batch_published_by FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  batch_id INT NOT NULL,
  student_id INT NOT NULL,
  score DECIMAL(5,2) NOT NULL DEFAULT 0,
  grade VARCHAR(2) DEFAULT NULL,
  grade_point DECIMAL(3,2) DEFAULT NULL,
  remark VARCHAR(30) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_result_batch_student (batch_id, student_id),
  CONSTRAINT fk_results_batch FOREIGN KEY (batch_id) REFERENCES result_batches(id) ON DELETE CASCADE,
  CONSTRAINT fk_results_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS grading_scale (
  id INT AUTO_INCREMENT PRIMARY KEY,
  min_score DECIMAL(5,2) NOT NULL,
  max_score DECIMAL(5,2) NOT NULL,
  grade VARCHAR(2) NOT NULL,
  grade_point DECIMAL(3,2) NOT NULL,
  remark VARCHAR(30) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE IF NOT EXISTS audit_trail (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50) DEFAULT NULL,
  entity_id INT DEFAULT NULL,
  details TEXT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
