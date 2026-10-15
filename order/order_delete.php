<?php
require_once "../auth.php";
require_once "../php/connect.php";

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header("Location: order_list.php");
    exit;
}

$receipt = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT receipt_id FROM receipt WHERE order_id = $order_id"
));

if ($receipt) {
    echo "<script>
        alert('ไม่สามารถลบได้ เนื่องจากออเดอร์นี้มีการออกใบเสร็จแล้ว');
        window.location = 'order_list.php';
    </script>";
    exit;
}
$order_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT payment_slip FROM orders WHERE order_id = $order_id"));
if (!empty($order_info['payment_slip'])) {
    $file_path = "../uploads/slips/" . $order_info['payment_slip'];
    if (file_exists($file_path)) {
        unlink($file_path); // สั่งลบไฟล์รูปออกจากโฟลเดอร์
    }
}

mysqli_query($conn, "DELETE FROM order_detail WHERE order_id = $order_id");


mysqli_query($conn, "DELETE FROM orders WHERE order_id = $order_id");

header("Location: order_list.php");
exit;
