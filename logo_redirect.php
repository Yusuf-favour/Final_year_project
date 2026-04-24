<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

if($_SESSION['role'] === 'admin'){
    header("Location: admin/index.php");
}elseif($_SESSION['role'] === 'lecturer'){
    header("Location: lecturer/index.php");
}elseif($_SESSION['role'] === 'hod'){
    header("Location: hod/index.php");
}elseif($_SESSION['role'] === 'staff'){
    header("Location: staff_dashboard.php");
}else{
    header("Location: student/index.php");
}

exit();
?>
