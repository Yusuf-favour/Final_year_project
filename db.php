<?php
ini_set('display_errors', 0);

$dbHost = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: '127.0.0.1');
$dbUser = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
$dbPass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '');
$dbName = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'lascohet_results');
$dbPort = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ? intval(getenv('DB_PORT')) : 3306);

$conn = new mysqli(
    $dbHost,
    $dbUser,
    $dbPass,
    $dbName,
    $dbPort
);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

/* Helper: get current academic session */
function getCurrentSession($conn) {
    $r = $conn->query("SELECT session_name FROM academic_sessions WHERE is_current=1 LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) return $row['session_name'];
    return '2025/2026';
}

/* Helper: compute letter grade & grade point (Nigerian system) */
function computeGrade($total) {
    if ($total >= 70) return ['A', 5.0];
    if ($total >= 60) return ['B', 4.0];
    if ($total >= 50) return ['C', 3.0];
    if ($total >= 45) return ['D', 2.0];
    if ($total >= 40) return ['E', 1.0];
    return ['F', 0.0];
}
?>
