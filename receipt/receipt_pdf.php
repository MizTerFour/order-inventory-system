<?php
date_default_timezone_set('Asia/Bangkok');
require_once "../auth.php";
require_once "../php/connect.php";
require_once "../vendor/autoload.php";

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

$order_id = (int)($_GET['id'] ?? 0);

/* =========================
   ดึงข้อมูลใบเสร็จ + order + customer
========================= */
$data = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT
        r.receipt_id,
        r.receipt_date,
        r.total_paid,
        r.payment_status,
        o.order_id,
        o.shipping_address,
        c.customer_name
     FROM receipt r
     JOIN orders o ON r.order_id = o.order_id
     JOIN customer c ON o.customer_id = c.customer_id
     WHERE o.order_id = $order_id"
));

if (!$data) {
    echo "<script>
        alert('ไม่พบใบเสร็จ กรุณาออกใบเสร็จก่อน');
        window.location = 'order_details.php?id=$order_id';
    </script>";
    exit;
}

/* =========================
   ดึงรายการสินค้า
========================= */
$items = mysqli_query(
    $conn,
    "SELECT 
        od.quantity,
        od.unit,
        od.price,
        p.product_name
     FROM order_detail od
     JOIN product p ON od.product_id = p.product_id
     WHERE od.order_id = $order_id"
);

/* =========================
   ตั้งค่า mPDF
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
        'sarabun' => ['R' => 'THSarabunNew.ttf'],
    ],
    'default_font' => 'sarabun'
]);

function thaiBahtText($number)
{
    $number = number_format($number, 2, '.', '');
    [$baht, $satang] = explode('.', $number);

    $txtnum1 = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
    $txtnum2 = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];

    $convert = function ($num) use ($txtnum1, $txtnum2) {
        $result = '';
        $len = strlen($num);
        for ($i = 0; $i < $len; $i++) {
            $n = (int)$num[$i];
            if ($n !== 0) {
                if ($i == $len - 1 && $n == 1 && $len > 1) {
                    $result .= 'เอ็ด';
                } elseif ($i == $len - 2 && $n == 2) {
                    $result .= 'ยี่';
                } elseif ($i == $len - 2 && $n == 1) {
                    $result .= '';
                } else {
                    $result .= $txtnum1[$n];
                }
                $result .= $txtnum2[$len - $i - 1];
            }
        }
        return $result;
    };

    $bahtText = $baht == '0' ? 'ศูนย์' : $convert($baht);
    $bahtText .= 'บาท';
    $satangText = $satang == '00' ? 'ถ้วน' : $convert($satang) . 'สตางค์';

    return $bahtText . $satangText;
}

/* =========================
   HTML ใบเสร็จ
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
.logo{
    display:block;
    margin:0 auto 8px auto;
    width:200px;
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
    padding-bottom: 4px;
    margin-bottom: 8px;
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

/* ส่วนเซ็นชื่อย้ายมาขวา */
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
    <h2>ใบเสร็จรับเงิน</h2>
</div>

<div class="section">
    <table class="info-table">
        <tr>
            <td width="20%"><b>เลขที่ใบเสร็จ</b></td>
            <td width="30%">: ' . $data['receipt_id'] . '</td>
            <td width="20%"><b>วันที่</b></td>
            <td width="30%">: ' . date('d/m/Y', strtotime($data['receipt_date'])) . '</td>
        </tr>
        <tr>
            <td><b>เลขที่ออเดอร์</b></td>
            <td>: ' . $data['order_id'] . '</td>
            <td><b>สถานะ</b></td>
            <td>: ' . $data['payment_status'] . '</td>
        </tr>
        <tr>
            <td><b>ลูกค้า</b></td>
            <td colspan="3">: ' . $data['customer_name'] . '</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">ที่อยู่จัดส่ง</div>
    ' . nl2br($data['shipping_address']) . '
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
        <td>' . $row['product_name'] . '</td>
        <td>' . $row['quantity'] . ' ' . $row['unit'] . '</td>
        <td>' . number_format($row['price'], 2) . '</td>
        <td>' . number_format($sum, 2) . '</td>
    </tr>';
}

$bahtText = thaiBahtText($total);

$html .= '
    <tr>
        <td colspan="4" class="text-right"><b>รวมเป็นเงิน</b></td>
        <td><b>' . number_format($total, 2) . '</b></td>
    </tr>
    </table>

    <p style="margin-top:8px; text-align:right;">
        (<b>' . $bahtText . '</b>)
    </p>
</div>

<div class="footer-container">
    <div class="signature-box">
        <p>ลงชื่อ .............................................................. ผู้รับเงิน</p>
        <p style="font-size:12px;">(เอกสารฉบับนี้ออกโดยระบบ ไม่ต้องลงลายมือชื่อ)</p>
        <br>
        <p>พิมพ์เมื่อ: ' . date('d/m/Y H:i') . '</p>
    </div>
    <div style="clear: both;"></div>
</div>
';

$mpdf->WriteHTML($html);
$mpdf->Output("receipt_$order_id.pdf", "I");