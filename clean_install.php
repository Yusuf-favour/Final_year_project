<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'swiftgrade_results';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die('Conn failed');

$conn->query("DROP DATABASE IF EXISTS `$db`");
$conn->query("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$conn->select_db($db);

$schema = file_get_contents('database/swiftgrade_schema.sql');
$conn->multi_query($schema);
do { $conn->next_result(); } while ($conn->more_results());

$seed = file_get_contents('database/swiftgrade_seed.sql');
$conn->multi_query($seed);
do { $conn->next_result(); } while ($conn->more_results());

echo "Clean install complete! DB: $db with schema + seed.
Admin: admin / Admin@2026
Student demo ready.
Delete this file.";
?>

