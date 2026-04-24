<?php
include 'db.php';
$result = $conn->query("DESCRIBE users;");
echo "Users table structure:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ') ' . ($row['Key'] == 'PRI' ? 'PRIMARY' : '') . "\n";
}
echo "\nStudents table:\n";
$result = $conn->query("DESCRIBE students;");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' (' . $row['Type'] . ")\n";
    }
} else {
    echo "No students table.\n";
}
?>

