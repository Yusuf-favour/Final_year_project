<?php
/**
 * Multi-Institution Setup Script
 * Creates central database and institutions table
 */

echo "<h2>Setting up Multi-Institution Support</h2>";

$central = new mysqli('localhost', 'root', '', 'mysql');
if ($central->connect_error) {
    die('Central DB error: ' . $central->connect_error);
}

// Create central database
$sql = "CREATE DATABASE IF NOT EXISTS swiftgrade_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($central->query($sql)) {
    echo "✓ Central database ready<br>";
} else {
    die("✗ Failed to create central DB: " . $central->error);
}

$central->close();

// Now connect to central database
$conn = new mysqli('localhost', 'root', '', 'swiftgrade_central');
$conn->set_charset('utf8mb4');

// Create institutions table
$sql = "CREATE TABLE IF NOT EXISTS institutions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(20) NOT NULL UNIQUE,
    database_name VARCHAR(50) NOT NULL,
    logo_url VARCHAR(255),
    color_primary VARCHAR(7) DEFAULT '#16A34A',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "✓ Institutions table ready<br>";
} else {
    die("✗ Failed to create table: " . $conn->error);
}

// Check if institutions exist
$check = $conn->query("SELECT COUNT(*) as cnt FROM institutions");
$row = $check->fetch_assoc();

if ($row['cnt'] == 0) {
    // Insert default institutions
    $institutions = [
        ['LASCOHET', 'LASCOHET', 'lascohet_results', '#16A34A'],
        ['UNIDEL', 'UNIDEL', 'unidel_schema', '#2563EB'],
    ];

    $stmt = $conn->prepare("INSERT INTO institutions (name, code, database_name, color_primary) VALUES (?, ?, ?, ?)");
    
    foreach ($institutions as $inst) {
        $stmt->bind_param('ssss', $inst[0], $inst[1], $inst[2], $inst[3]);
        if ($stmt->execute()) {
            echo "✓ Added institution: {$inst[0]}<br>";
        } else {
            echo "✗ Failed to add {$inst[0]}: " . $stmt->error . "<br>";
        }
    }
    $stmt->close();
} else {
    echo "✓ Institutions already exist (" . $row['cnt'] . " found)<br>";
}

echo "<h3>Current Institutions:</h3>";
$result = $conn->query("SELECT id, name, code, database_name FROM institutions WHERE is_active = 1");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Code</th><th>Database</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['code']}</td><td>{$row['database_name']}</td></tr>";
}
echo "</table>";

$conn->close();
?>
