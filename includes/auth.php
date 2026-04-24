<?php
/* ================================================================
   SwiftGrade – Auth Helpers
   ================================================================ */

/* ---------- BASE_URL constant (if not defined elsewhere) ---------- */
if (!defined('BASE_URL')) {
    /* Determine base URL dynamically */
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    
    /* Remove trailing slash if present */
    $scriptDir = rtrim($scriptDir, '/');
    
    /* If we're in a subdirectory like /Student-Management-System, use that */
    /* Otherwise, use just the protocol and host */
    if ($scriptDir && $scriptDir !== '/' && $scriptDir !== '') {
        define('BASE_URL', $protocol . $host . $scriptDir);
    } else {
        define('BASE_URL', $protocol . $host);
    }
}

/* ---------- Role-based dashboard redirect ---------- */
if (!function_exists('dashboardURL')) {
    function dashboardURL() {
        $role = $_SESSION['role'] ?? 'student';
        
        $base = BASE_URL;
        
        /* Role-based dashboard mapping */
        $dashboards = [
            'admin'    => $base . '/admin/index.php',
            'lecturer' => $base . '/lecturer/index.php',
            'hod'      => $base . '/hod/index.php',
            'student'  => $base . '/student/index.php',
            'staff'    => $base . '/staff_dashboard.php',  // legacy fallback
        ];
        
        /* Return role-specific dashboard or default to student */
        return $dashboards[$role] ?? $dashboards['student'];
    }
}

/* ---------- Check if user is authenticated ---------- */
if (!function_exists('isAuthenticated')) {
    function isAuthenticated() {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['role']) && 
               !empty($_SESSION['user_id']);
    }
}

/* ---------- Check user role ---------- */
if (!function_exists('hasRole')) {
    function hasRole($requiredRole) {
        if (!isAuthenticated()) {
            return false;
        }

        if (is_array($requiredRole)) {
            return in_array($_SESSION['role'], $requiredRole);
        }

        return $_SESSION['role'] === $requiredRole;
    }
}

/* ---------- Require authentication (redirect to login if not) ---------- */
if (!function_exists('requireAuth')) {
    function requireAuth($allowedRoles = null) {
        if (!isAuthenticated()) {
            header('Location: ' . BASE_URL . '/swiftgrade_login.php');
            exit();
        }
        
        if ($allowedRoles !== null && !hasRole($allowedRoles)) {
            header('Location: ' . BASE_URL . '/swiftgrade_login.php');
            exit();
        }
    }
}

/* ---------- Require specific role ---------- */
if (!function_exists('requireRole')) {
    function requireRole(...$roles) {
        if (count($roles) === 1 && is_array($roles[0])) {
            $roles = $roles[0];
        }

        if (empty($roles)) {
            $roles = [$_SESSION['role'] ?? 'guest'];
        }

        requireAuth($roles);
    }
}

/* ---------- Require login alias ---------- */
if (!function_exists('requireLogin')) {
    function requireLogin() {
        requireAuth();
    }
}

/* ---------- Get user full name ---------- */
if (!function_exists('getUserName')) {
    function getUserName() {
        return $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
    }
}

/* ---------- Get current user role ---------- */
if (!function_exists('getUserRole')) {
    function getUserRole() {
        return $_SESSION['role'] ?? 'guest';
    }
}

/* ---------- Get current user ID ---------- */
if (!function_exists('getUserId')) {
    function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}

/* ---------- Current user id alias ---------- */
if (!function_exists('currentUserId')) {
    function currentUserId() {
        return getUserId();
    }
}

/* ---------- Get current institution ID ---------- */
if (!function_exists('getInstitutionId')) {
    function getInstitutionId() {
        return $_SESSION['institution_id'] ?? null;
    }
}

/* ---------- Check if password change is required ---------- */
if (!function_exists('mustChangePassword')) {
    function mustChangePassword() {
        return (bool)($_SESSION['must_change_password'] ?? false);
    }
}

?>
