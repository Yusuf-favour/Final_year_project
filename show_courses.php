<?php
$mysqli = new mysqli('localhost','root','','lascohet_results');
if ($mysqli->connect_error) {
    die('DB ERROR: ' . $mysqli->connect_error);
}
$res = $mysqli->query('SELECT * FROM courses');
echo "<pre>COURSES\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "</pre>\n";
$res2 = $mysqli->query('SELECT * FROM result_batches');
echo "<pre>RESULT_BATCHES\n";
while($row = $res2->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
