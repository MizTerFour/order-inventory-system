<?php
require_once "../auth.php";
require_once "../php/connect.php";

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    die("ไม่พบคำสั่งซื้อ");
}

/* 1️⃣ ดึงข้อมูล order + customer */
$order = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT o.*, c.customer_name
     FROM orders o
     JOIN customer c ON o.customer_id = c.customer_id
     WHERE o.order_id = $order_id"
));

if (!$order) {
    die("ไม่พบคำสั่งซื้อ");
}

/* 2️⃣ ดึงรายการสินค้า */
$items = mysqli_query(
    $conn,
    "SELECT od.quantity, od.unit, od.price,
            p.product_name,
            s.supplier_name
     FROM order_detail od
     JOIN product p ON od.product_id = p.product_id
     JOIN supplier s ON p.supplier_id = s.supplier_id
     WHERE od.order_id = $order_id"
);

/* 3️⃣ ตรวจสอบใบเสร็จ */
$receipt = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT receipt_id
     FROM receipt
     WHERE order_id = $order_id
     LIMIT 1"
));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>รายละเอียดคำสั่งซื้อ</title>
    <link rel="icon" type="image/x-icon" href="../logo.jpg">
    <link rel="stylesheet" href="/PROJECT/style.css">
    <link rel="stylesheet" href="../form.css">
    <style>
        .info-block p {
            margin: 6px 0;
            font-size: 14px;
        }

        table {
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }

        table th,
        table td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: center;
        }

        table th {
            background: #f3f4f6;
            font-weight: 600;
        }

        .total {
            text-align: right;
            margin-top: 12px;
            font-weight: bold;
        }

        .actions a {
            margin-right: 12px;
        }
    </style>
</head>

<body>

    <?php include "../aside.php"; ?>

    <div class="content form-page">
        <div class="form-wrapper">
            <div class="form-card">

                <h2 class="form-title">
                    รายละเอียดคำสั่งซื้อ #<?= $order['order_id']; ?>
                </h2>

                <div class="info-block">
                    <p><b>วันที่:</b> <?= $order['order_date']; ?></p>
                    <p><b>สถานะ:</b> <?= $order['order_status']; ?></p>
                    <p><b>วิธีชำระเงิน:</b> <?= $order['payment_type'] ?? '-'; ?></p>
                    <?php if (!empty($order['note'])) { ?>
                        <p><b>หมายเหตุ:</b> <?= nl2br(htmlspecialchars($order['note'])) ?></p>
                    <?php } ?>

                    <?php if (!empty($order['due_date'])) { ?>
                        <p><b>วันครบกำหนด:</b> <?= $order['due_date']; ?></p>
                    <?php } ?>

                    <p><b>ยอดรวม:</b> <?= number_format($order['total_amount'], 2); ?> บาท</p>
                </div>

                <hr>

                <h4>ข้อมูลลูกค้า</h4>
                <div class="info-block">
                    <p><b>ชื่อ:</b> <?= htmlspecialchars($order['customer_name']); ?></p>
                    <p><b>เบอร์โทร:</b> <?= htmlspecialchars($order['phone']); ?></p>
                    <p><b>ที่อยู่จัดส่ง:</b><br>
                        <?= nl2br(htmlspecialchars($order['shipping_address'])); ?>
                    </p>

                    <p><b>สถานะจัดส่ง:</b> <?= $order['delivery_status'] ?? 'รอจัดส่ง'; ?></p>

                    <?php if (!empty($order['payment_slip'])) { ?>
                        <div style="margin-top: 15px; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: inline-block;">
                            <p style="margin: 0 0 10px 0;"><b>สลิปโอนเงิน:</b></p>
                            <a href="../uploads/slips/<?= $order['payment_slip'] ?>" target="_blank">
                                <img src="../uploads/slips/<?= $order['payment_slip'] ?>" style="max-height: 300px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            </a>
                        </div>
                    <?php } ?>
                </div>
                <hr>

                <h4>รายการสินค้า</h4>

                <table width="100%">
                    <tr>
                        <th>#</th>
                        <th>สินค้า</th>
                        <th>จำนวน</th>
                        <th>หน่วย</th>
                        <th>ราคา/หน่วย</th>
                        <th>รวม</th>
                        <th>โรงงานผลิต</th>
                    </tr>

                    <?php
                    $i = 1;
                    $sum = 0;
                    while ($row = mysqli_fetch_assoc($items)) {
                        $line = $row['quantity'] * $row['price'];
                        $sum += $line;
                    ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= htmlspecialchars($row['product_name']); ?></td>
                            <td><?= $row['quantity']; ?></td>
                            <td><?= $row['unit']; ?></td>
                            <td><?= number_format($row['price'], 2); ?></td>
                            <td><?= number_format($line, 2); ?></td>
                            <td><?= htmlspecialchars($row['supplier_name']); ?></td>
                        </tr>
                    <?php } ?>
                </table>

                <div class="total">
                    รวมทั้งสิ้น <?= number_format($sum, 2); ?> บาท
                </div>

                <hr>

                <div class="actions">

                    <!-- พิมพ์ใบสั่งซื้อ (มีเสมอ) -->
                    <a href="order_pdf.php?id=<?= $order_id ?>" target="_blank" class="btn-print">
                        🖨 พิมพ์ใบสั่งซื้อ
                    </a>

                    <!-- แก้ไขคำสั่งซื้อ (ถ้ายังไม่ชำระ) -->
                    <?php if ($order['order_status'] !== 'ชำระแล้ว') { ?>
                        <a href="order_edit.php?id=<?= $order_id; ?>" class="btn-secondary">
                            ✏️ แก้ไขคำสั่งซื้อ
                        </a>
                    <?php } ?>

                    <!-- เฉพาะกรณีชำระแล้ว -->
                    <?php if ($order['order_status'] === 'ชำระแล้ว') { ?>

                        <?php if (!$receipt) { ?>
                            <a href="../receipt/receipt_create.php?order_id=<?= $order_id ?>"
                                onclick="return confirm('ออกใบเสร็จใช่หรือไม่?')"
                                class="btn-printpdf">
                                ออกใบเสร็จ
                            </a>
                        <?php } else { ?>
                            <a href="../receipt/receipt_pdf.php?id=<?= $order_id ?>"
                                target="_blank"
                                class="btn-printpdf">
                                🖨 พิมพ์ใบเสร็จ
                            </a>
                        <?php } ?>

                    <?php } ?>

                </div>


                <div class="form-actions">
                    <a href="order_list.php" class="btn-secondary">กลับ</a>
                </div>

            </div>
        </div>
    </div>

</body>

</html>