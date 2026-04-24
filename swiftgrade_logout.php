<?php
/* ================================================================
   SwiftGrade – Secure Logout
   ================================================================ */
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/audit.php';

if (isset($_SESSION['user_id'])) {
    try {
        logAudit($conn, 'LOGOUT', 'user', (int)$_SESSION['user_id'], 'User logged out');
    } catch (Throwable $e) {
        // Do not block logout if audit logging fails.
    }
}

// Unset all session variables
$_SESSION = array();

// If session uses cookies, remove the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to correct login page
header('Location: ' . BASE_URL . '/swiftgrade_login.php');
exit();
