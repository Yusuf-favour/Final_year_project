<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'lascohet_results', 3307);
if ($conn->connect_error) {
    echo 'CONNECT_ERR:' . $conn->connect_error;
} else {
    echo 'CONNECT_OK';
}
?>