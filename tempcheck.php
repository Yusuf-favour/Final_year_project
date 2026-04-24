<?php
include 'db.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    echo 'CONN_MISSING';
    exit(1);
}
if ($conn->connect_error) {
    echo 'ERR:'.$conn->connect_error;
} else {
    echo 'OK';
}
?>
