<?php
$dbHost = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: '127.0.0.1');
$dbUser = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
$dbPass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '');
$dbName = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'lascohet_results');
$dbPort = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ? intval(getenv('DB_PORT')) : 3306);

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
?>

