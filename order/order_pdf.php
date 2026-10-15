<?php
date_default_timezone_set('Asia/Bangkok');
require_once "../auth.php";
require_once "../php/connect.php";
require_once "../vendor/autoload.php";

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

$order_id = (int)($_GET['id'] ?? 0);

$order = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT o.*, c.customer_name
     FROM orders o
     JOIN customer c ON o.customer_id = c.customer_id
     WHERE o.order_id = $order_id"
));

if (!$order) {
    echo "<script>
        alert('ไม่พบคำสั่งซื้อ');
        window.location = 'order_list.php';
    </script>";
    exit;
}

$items = mysqli_query(
    $conn,
    "SELECT 
        od.quantity,
        od.price,
        p.product_name,
        p.unit
     FROM order_detail od
     JOIN product p ON od.product_id = p.product_id
     WHERE od.order_id = $order_id"
);

/* =========================
   ตั้งค่า mPDF + ฟอนต์ไทย
========================= */
$defaultConfig = (new ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'fontDir' => array_merge($fontDirs, [
        __DIR__ . '/../font',
    ]),
    'fontdata' => $fontData + [
        'sarabun' => [
            'R' => 'THSarabunNew.ttf',
        ],
    ],
    'default_font' => 'sarabun'
]);

/* =========================
   HTML ใบสั่งซื้อ
========================= */
$html = '
<style>
body {
    font-size: 14px;
    line-height: 1.6;
}

.header {
    text-align: center;
    margin-bottom: 20px;
}
.logo {
    display: block;
    margin: 0 auto 8px auto;
    width: 200px; /* กลับมาเป็น 200px ตามเดิม */
}

.header h2 {
    margin: 0;
    font-size: 22px;
}

.section {
    margin-bottom: 15px;
}

.section-title {
    font-weight: bold;
    margin-bottom: 6px;
    padding-bottom: 4px;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table td {
    padding: 4px 6px;
    vertical-align: top;
}

table.items {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

table.items th,
table.items td {
    border: 1px solid #000;
    padding: 6px;
    text-align: center;
}

table.items th {
    background: #f2f2f2;
    font-weight: bold;
}

.text-right {
    text-align: right;
}

.status-unpaid {
    color: red;
    font-weight: bold;
}

/* ส่วนลายเซ็นฝั่งขวา */
.footer-container {
    width: 100%;
    margin-top: 30px;
}
.signature-box {
    width: 45%;
    float: right;
    text-align: center;
}
</style>

<div class="header">
    <img src="../logo.jpg" class="logo">
    <h2>ใบสั่งซื้อ</h2>
</div>

<div class="section">
    <table class="info-table">
        <tr>
            <td width="20%"><b>เลขที่ออเดอร์</b></td>
            <td width="30%">: ' . $order['order_id'] . '</td>
            <td width="20%"><b>วันที่</b></td>
            <td width="30%">: ' . date('d/m/Y', strtotime($order['order_date'])) . '</td>
        </tr>
        <tr>
            <td><b>ลูกค้า</b></td>
            <td>: ' . $order['customer_name'] . '</td>
            <td><b>สถานะ</b></td>
            <td>: ' .
                ($order['order_status'] !== 'ชำระแล้ว'
                    ? '<span class="status-unpaid">ยังไม่ชำระเงิน</span>'
                    : 'ชำระแล้ว')
            . '</td>
        </tr>
        <tr>
            <td><b>วิธีชำระเงิน</b></td>
            <td>: ' . ($order['payment_type'] ?? '-') . '</td>
            <td><b>วันครบกำหนด</b></td>
            <td>: ' . (!empty($order['due_date'])
                ? date('d/m/Y', strtotime($order['due_date']))
                : '-') . '</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">ข้อมูลที่อยู่จัดส่ง</div>
    ' . nl2br(htmlspecialchars($order['shipping_address'])) . '
</div>

<div class="section">
    <div class="section-title">รายการสินค้า</div>
    <table class="items">
        <tr>
            <th width="8%">ลำดับ</th>
            <th width="40%">รายละเอียดสินค้า</th>
            <th width="15%">จำนวน</th>
            <th width="18%">ราคา/หน่วย</th>
            <th width="19%">รวม</th>
        </tr>
';

$i = 1;
$total = 0;

while ($row = mysqli_fetch_assoc($items)) {
    $sum = $row['quantity'] * $row['price'];
    $total += $sum;

    $html .= '
    <tr>
        <td>' . $i++ . '</td>
        <td style="text-align:left;">' . $row['product_name'] . '</td>
        <td>' . $row['quantity'] . ' ' . $row['unit'] . '</td>
        <td>' . number_format($row['price'], 2) . '</td>
        <td>' . number_format($sum, 2) . '</td>
    </tr>
    ';
}

$html .= '
        <tr>
            <td colspan="4" class="text-right"><b>ยอดรวมทั้งสิ้น</b></td>
            <td><b>' . number_format($total, 2) . '</b></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">หมายเหตุ</div>
    ' . (!empty($order['note'])
        ? nl2br(htmlspecialchars($order['note']))
        : '-') . '
</div>

<div class="footer-container">
    <div class="signature-box">
        <p>ลงชื่อ .............................................................. ผู้สั่งซื้อ</p>
        <br><br>
        <p>ลงชื่อ .............................................................. ผู้อนุมัติ</p>
        <br>
        <p>วันที่พิมพ์เอกสาร: ' . date('d/m/Y H:i') . '</p>
    </div>
    <div style="clear: both;"></div>
</div>
';

/* =========================
   แสดง PDF
========================= */
$mpdf->WriteHTML($html);
$mpdf->Output("order_$order_id.pdf", "I");