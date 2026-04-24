<?php
$conn = new mysqli("127.0.0.1", "root", "", "lascohet_results", 3307);
echo $conn->connect_error ? "ERR:" . $conn->connect_error : "OK";
?>
