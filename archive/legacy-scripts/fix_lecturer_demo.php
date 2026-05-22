<?php
$mysqli = new mysqli('localhost','root','','lascohet_results');
if ($mysqli->connect_error) die('DB ERROR: ' . $mysqli->connect_error);

// Get current semester
$sem = $mysqli->query("SELECT id FROM semesters WHERE is_current=1 LIMIT 1")->fetch_assoc();
if (!$sem) die('No current semester.');
$semester_id = $sem['id'];

// Get lecturer user id
$lect = $mysqli->query("SELECT id FROM users WHERE username='mr.okeke' LIMIT 1")->fetch_assoc();
if (!$lect) die('No lecturer user.');
$lecturer_id = $lect['id'];

// Get or create department
$dept = $mysqli->query("SELECT id FROM departments LIMIT 1")->fetch_assoc();
if (!$dept) {
  $mysqli->query("INSERT INTO departments (code, name) VALUES ('SCI', 'Science')");
  $dept_id = $mysqli->insert_id;
} else {
  $dept_id = $dept['id'];
}

// Get or create course
$course = $mysqli->query("SELECT id FROM courses LIMIT 1")->fetch_assoc();
if (!$course) {
  $mysqli->query("INSERT INTO courses (code, title, unit, department_id) VALUES ('BIO101', 'Intro to Biology', 3, $dept_id)");
  $course_id = $mysqli->insert_id;
} else {
  $course_id = $course['id'];
}

// Create result_batch for this lecturer, course, and semester
$batch = $mysqli->query("SELECT id FROM result_batches WHERE course_id=$course_id AND semester_id=$semester_id AND lecturer_id=$lecturer_id")->fetch_assoc();
if (!$batch) {
  $mysqli->query("INSERT INTO result_batches (course_id, semester_id, lecturer_id, status) VALUES ($course_id, $semester_id, $lecturer_id, 'draft')");
}
echo "Demo course and batch assigned to lecturer!";
