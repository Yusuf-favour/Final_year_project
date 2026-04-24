<?php
error_reporting(E_ALL);
$conn = new mysqli('localhost', 'root', '');
$r = $conn->query('SHOW DATABASES');
echo "Available databases:\n";
while ($row = $r->fetch_row()) {
    echo "  " . $row[0] . "\n";
}
$conn->close();
?>
