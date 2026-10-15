<?php
require_once "../auth.php";
require_once "../php/connect.php";

$p_id = $_GET['product_id'] ?? '';
$s_date = $_GET['start_date'] ?? '';
$e_date = $_GET['end_date'] ?? '';


$sql = "SELECT o.order_id, o.order_date, c.customer_name, od.quantity, od.price, (od.quantity * od.price) as subtotal
        FROM order_detail od
        JOIN orders o ON od.order_id = o.order_id
        JOIN customer c ON o.customer_id = c.customer_id
        WHERE od.product_id = '$p_id' 
        AND o.order_status = 'ชำระแล้ว'
        AND o.order_date BETWEEN '$s_date' AND '$e_date'
        ORDER BY o.order_date DESC";

$res = mysqli_query($conn, $sql);

echo '<table style="width:100%; border-collapse:collapse; margin-top:15px;">
        <thead style="background:#f8fafc;">
            <tr>
                <th style="padding:10px; border-bottom:2px solid #e2e8f0; text-align:left;">ออเดอร์</th>
                <th style="padding:10px; border-bottom:2px solid #e2e8f0; text-align:left;">วันที่</th>
                <th style="padding:10px; border-bottom:2px solid #e2e8f0; text-align:left;">ลูกค้า</th>
                <th style="padding:10px; border-bottom:2px solid #e2e8f0; text-align:center;">จำนวน</th>
                <th style="padding:10px; border-bottom:2px solid #e2e8f0; text-align:right;">ยอดรวม</th>
            </tr>
        </thead><tbody>';

if (mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        echo "<tr style='border-bottom:1px solid #f1f5f9;'>
                <td style='padding:10px;'>#{$row['order_id']}</td>
                <td style='padding:10px;'>".date('d/m/Y', strtotime($row['order_date']))."</td>
                <td style='padding:10px;'>".htmlspecialchars($row['customer_name'])."</td>
                <td style='padding:10px; text-align:center;'>".number_format($row['quantity'])."</td>
                <td style='padding:10px; text-align:right;'>".number_format($row['subtotal'], 2)."</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='5' style='text-align:center; padding:20px;'>ไม่พบข้อมูล</td></tr>";
}
echo '</tbody></table>';
?>