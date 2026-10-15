<?php
require_once "../auth.php";
require_once "../php/connect.php";

$id = (int)($_GET['id'] ?? 0);

$chk = mysqli_query($conn,
    "SELECT COUNT(*) AS c
     FROM product
     WHERE supplier_id = $id"
);

$row = mysqli_fetch_assoc($chk);

if ($row['c'] > 0) {

    echo "<script>
        alert('ไม่สามารถลบได้ เนื่องจากมีสินค้าใช้โรงงานนี้อยู่');
        window.location='supplier_list.php';
    </script>";
    exit;
}



mysqli_query($conn,
    "DELETE FROM supplier WHERE supplier_id = $id"
);

header("Location: supplier_list.php");
exit;
