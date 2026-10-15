<?php
require_once "../auth.php";
require_once "../php/connect.php";

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    header("Location: ../order/order_list.php");
    exit;
}


$order = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT *
     FROM orders
     WHERE order_id = $order_id
       AND order_status = 'ชำระแล้ว'"
));

if (!$order) {
    echo "<script>
        alert('ต้องชำระเงินก่อน จึงจะออกใบเสร็จได้');
        window.history.back();
    </script>";
    exit;
}


$receipt = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT receipt_id
     FROM receipt
     WHERE order_id = $order_id
     LIMIT 1"
));

if ($receipt) {
    header("Location: receipt_pdf.php?id=$order_id");
    exit;
}


mysqli_query(
    $conn,
    "INSERT INTO receipt
     (order_id, receipt_date, total_paid, payment_status)
     VALUES
     ($order_id, NOW(), {$order['total_amount']}, 'ชำระแล้ว')"
);


header("Location: receipt_pdf.php?id=$order_id");
exit;
