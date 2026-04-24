<?php
require_once __DIR__ . '/includes/config.php';
$res = $conn->query("SELECT id, username, role, institution_id FROM users");
while ($row = $res->fetch_assoc()) {
    echo $row['id'] . ': ' . $row['username'] . ' (' . $row['role'] . ') - institution_id: ' . $row['institution_id'] . "\n";
}
