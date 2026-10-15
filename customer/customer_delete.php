<?php
require_once "../auth.php";
require_once "../php/connect.php";

$id = (int)($_GET['id'] ?? 0);


// 1. ตรวจสอบว่าลูกค้าคนนี้มีรายการสั่งซื้อในระบบหรือไม่

$chk = mysqli_query($conn, 
    "SELECT COUNT(*) AS c 
     FROM orders 
     WHERE customer_id = $id"
);

$row = mysqli_fetch_assoc($chk);

// หากมีรายการ (orders) ค้างอยู่ จะไม่ยอมให้ลบ
if ($row['c'] > 0) {
    echo "<script>
        alert('ไม่สามารถลบได้ เนื่องจากลูกค้าท่านนี้มีประวัติการสั่งซื้ออยู่ในระบบ');
        window.location='customer_list.php';
    </script>";
    exit;
}


// หากไม่มีข้อมูลสั่งซื้อค้างอยู่ จึงทำการลบ

$result = mysqli_query($conn, 
    "DELETE FROM customer WHERE customer_id = $id"
);

if ($result) {
    header("Location: customer_list.php");
} else {

    echo "<script>
        alert('เกิดข้อผิดพลาดในการลบข้อมูล');
        window.location='customer_list.php';
    </script>";
}
exit;