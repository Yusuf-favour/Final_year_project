<?php
/* ================================================================
    SwiftGrade – Result Processing System
   ================================================================ */

/* ---------- secure session ---------- */
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly',  1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode',  1);
    session_start();
}

/* ---------- error display ---------- */
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ---------- local overrides ---------- */
if (file_exists(__DIR__ . '/config.local.php')) {
    include __DIR__ . '/config.local.php';
}

/* ---------- database ---------- */
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') ?: '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'lascohet_results');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', getenv('DB_PORT') ? intval(getenv('DB_PORT')) : 3306);
}

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    /* Don't die() — let the caller handle it gracefully */
    error_log('SwiftGrade DB error: ' . $conn->connect_error);
    $conn = null;
} else {
    $conn->set_charset('utf8mb4');
}

/* ---------- app constants ---------- */
define('APP_NAME',  'SwiftGrade University – Result Processing System');
define('APP_SHORT', 'SGU SwiftGrade');
define('BASE_URL',  '/Student-Management-System');

/* ---------- grading helpers (cached) ---------- */
function getGradingScale($conn) {
    static $scale = null;
    if ($scale === null) {
        $scale = [];
        $r = $conn->query("SELECT * FROM grading_scale ORDER BY min_score DESC");
        while ($row = $r->fetch_assoc()) $scale[] = $row;
    }
    return $scale;
}
