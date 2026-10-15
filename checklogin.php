<?php
session_start();
require_once "php/connect.php";

$username = $_POST['username'];
$password = $_POST['password'];

// แก้ไข Query ให้ดึงข้อมูลมาเช็ค
$sql = "SELECT * FROM `admin` WHERE username = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// ตรวจสอบรหัสผ่าน และตรวจสอบสถานะว่าต้องเป็น ACTIVE เท่านั้น
if ($admin && password_verify($password, $admin['password'])) {
    
    if ($admin['status'] == 'ACTIVE') {
        // ล็อกอินสำเร็จ
        $_SESSION['admin_id']   = $admin['admin_id'];
        $_SESSION['username']   = $admin['username']; // ใช้ username แทน name เพราะเราเอา name ออกไปแล้ว
        $_SESSION['status']     = $admin['status'];

        header("Location: index.php");
        exit;
    } else {
        // กรณีชื่อถูก แต่สถานะเป็น INACTIVE (โดนระงับสิทธิ์)
        $_SESSION['login_error'] = "บัญชีนี้ถูกระงับการใช้งาน";
        header("Location: login.php");
        exit;
    }

} else {
    // กรณีรหัสผ่านผิด หรือไม่พบ Username
    $_SESSION['login_error'] = "Username หรือ Password ไม่ถูกต้อง";
    header("Location: login.php");
    exit;
}
?>