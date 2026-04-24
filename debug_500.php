<?php
/* Temporary diagnostic – delete after use */
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>SwiftGrade Login Diagnostic</h2>";
echo "<pre>";

// Step 1: Check PHP version
echo "PHP Version: " . PHP_VERSION . "\n";

// Step 2: Test DB connection
echo "\n--- DATABASE CONNECTION ---\n";
try {
    $conn = new mysqli('localhost', 'root', '', 'lascohet_results');
    if ($conn->connect_error) {
        echo "FAIL: " . $conn->connect_error . "\n";
    } else {
        echo "OK: Connected to lascohet_results\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}

// Step 3: Check if required tables exist
echo "\n--- TABLES ---\n";
$tables = ['users', 'students', 'institutions', 'departments', 'audit_trail'];
foreach ($tables as $t) {
    $r = $conn->query("SHOW TABLES LIKE '$t'");
    echo "$t: " . ($r && $r->num_rows ? "EXISTS" : "MISSING") . "\n";
}

// Step 4: Check users table columns
echo "\n--- USERS TABLE COLUMNS ---\n";
$r = $conn->query("SHOW COLUMNS FROM users");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        echo "  " . $row['Field'] . " (" . $row['Type'] . ")" . ($row['Null'] === 'YES' ? ' NULL' : ' NOT NULL') . "\n";
    }
} else {
    echo "ERROR: Cannot read users columns: " . $conn->error . "\n";
}

// Step 5: Check if students table has user_id
echo "\n--- STUDENTS TABLE COLUMNS ---\n";
$r = $conn->query("SHOW COLUMNS FROM students");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "ERROR: " . $conn->error . "\n";
}

// Step 6: Check institutions table
echo "\n--- INSTITUTIONS TABLE ---\n";
$r = $conn->query("SELECT id, name, short_name, institution_type FROM institutions LIMIT 10");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        echo "  ID={$row['id']} {$row['short_name']} ({$row['institution_type']})\n";
    }
} else {
    echo "ERROR: " . $conn->error . "\n";
}

// Step 7: Check demo accounts
echo "\n--- DEMO ACCOUNTS ---\n";
$r = $conn->query("SELECT id, username, role, is_active, institution_id, LEFT(password_hash, 7) AS hash_start FROM users WHERE username IN ('admin','mr.okeke','demo_student')");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        echo "  {$row['username']}: role={$row['role']}, active={$row['is_active']}, inst_id={$row['institution_id']}, hash_starts={$row['hash_start']}\n";
    }
    if ($r->num_rows == 0) echo "  NO DEMO ACCOUNTS FOUND!\n";
} else {
    echo "ERROR: " . $conn->error . "\n";
}

// Step 8: Test the EXACT login query from swiftgrade_login.php
echo "\n--- TEST LOGIN QUERY (admin) ---\n";
$sql = "SELECT u.id, u.username, u.password_hash, u.full_name, u.role,
        u.department_id, u.must_change_password, u.is_active
     FROM users u LEFT JOIN students s ON s.user_id = u.id
     WHERE (u.username = ? OR s.matric_no = ?) AND u.institution_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "PREPARE FAILED: " . $conn->error . "\n";
    echo "THIS IS YOUR 500 ERROR CAUSE!\n";
} else {
    $u = 'admin'; $inst = 1;
    $stmt->bind_param('ssi', $u, $u, $inst);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        echo "User found: {$row['username']} (role={$row['role']}, active={$row['is_active']})\n";
        echo "Password hash starts with: " . substr($row['password_hash'], 0, 10) . "\n";
        echo "Bcrypt verify 'Admin@2026': " . (password_verify('Admin@2026', $row['password_hash']) ? 'PASS' : 'FAIL') . "\n";
    } else {
        echo "NO USER FOUND for admin with institution_id=1\n";
    }
    $stmt->close();
}

// Step 9: Test includes
echo "\n--- TEST INCLUDES ---\n";
try {
    require_once __DIR__ . '/includes/config.php';
    echo "config.php: OK (BASE_URL=" . BASE_URL . ")\n";
} catch (Throwable $e) {
    echo "config.php FAILED: " . $e->getMessage() . " at line " . $e->getLine() . "\n";
}

try {
    require_once __DIR__ . '/includes/auth.php';
    echo "auth.php: OK\n";
} catch (Throwable $e) {
    echo "auth.php FAILED: " . $e->getMessage() . " at line " . $e->getLine() . "\n";
}

try {
    require_once __DIR__ . '/includes/audit.php';
    echo "audit.php: OK\n";
} catch (Throwable $e) {
    echo "audit.php FAILED: " . $e->getMessage() . " at line " . $e->getLine() . "\n";
}

echo "\n--- DONE ---\n";
echo "</pre>";
$conn->close();
?>
