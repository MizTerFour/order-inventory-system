<?php
require_once "../auth.php";
require_once "../php/connect.php";

$id = (int)($_GET['id'] ?? 0);

// เช็คก่อนว่ามี order ใช้สินค้านี้ไหม
$chk = mysqli_query($conn,
    "SELECT COUNT(*) AS c
     FROM order_detail
     WHERE product_id = $id"
);

$row = mysqli_fetch_assoc($chk);

if ($row['c'] > 0) {

    echo "<script>
        alert('ไม่สามารถลบสินค้าได้ เนื่องจากมีรายการสั่งซื้อที่ใช้สินค้านี้อยู่');
        window.location='product_list.php';
    </script>";
    exit;
}

// ถ้าไม่มีใครใช้ → ลบได้
mysqli_query($conn,
    "DELETE FROM product WHERE product_id = $id"
);

header("Location: product_list.php");
exit;
