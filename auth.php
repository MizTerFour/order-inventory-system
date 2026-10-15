<?php
session_start();

// 1. ตรวจสอบว่าล็อกอินหรือยัง
if (!isset($_SESSION['admin_id'])) {
    header("Location: /PROJECT/login.php");
    exit;
}

// 2. ตั้งค่าระยะเวลา Timeout (30 นาที = 1800 วินาที)
$timeout_duration = 1800; 


if (isset($_SESSION['last_activity'])) {

    $elapsed_time = time() - $_SESSION['last_activity'];

    if ($elapsed_time > $timeout_duration) {
        // ถ้าเกิน 30 นาที ให้ทำลาย Session และส่งไปหน้า Login
        session_unset();
        session_destroy();
        header("Location: /PROJECT/login.php?error=timeout");
        exit;
    }
}

$_SESSION['last_activity'] = time();
?>