<?php
$mysqli = new mysqli('localhost','root','','lascohet_results');
if ($mysqli->connect_error) {
    die('DB ERROR: ' . $mysqli->connect_error);
}
$res = $mysqli->query('SELECT * FROM academic_sessions');
echo "<pre>";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
