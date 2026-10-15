<?php
require_once "../auth.php";
require_once "../php/connect.php";

// รับค่าจาก URL ที่ส่งมาจากหน้าหลัก
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$supplier_id = $_GET['supplier_id'] ?? '';

// ดึงชื่อโรงงานมาแสดง (ถ้ามีการเลือก)
$supplier_where = "";
$display_supplier_name = "ทั้งหมด";
if (!empty($supplier_id)) {
    $supplier_where = " AND p.supplier_id = '$supplier_id' ";
    $s_res = mysqli_query($conn, "SELECT supplier_name FROM supplier WHERE supplier_id = '$supplier_id'");
    if ($s_row = mysqli_fetch_assoc($s_res)) {
        $display_supplier_name = $s_row['supplier_name'];
    }
}

// 1. ดึงข้อมูลภาพรวม
$sqlOverview = "SELECT 
    SUM(od.quantity * od.price) as total_revenue, 
    COUNT(DISTINCT o.order_id) as total_orders,
    COUNT(DISTINCT o.customer_id) as total_customers
    FROM orders o
    JOIN order_detail od ON o.order_id = od.order_id
    JOIN product p ON od.product_id = p.product_id
    WHERE o.order_status = 'ชำระแล้ว' 
    AND o.order_date BETWEEN '$start_date' AND '$end_date'
    $supplier_where";
$resOverview = mysqli_fetch_assoc(mysqli_query($conn, $sqlOverview));

// 2. ดึงข้อมูลสินค้าขายดี 
$sqlTopSell = "SELECT p.product_id, p.product_name, SUM(od.quantity) as total_qty, SUM(od.quantity * od.price) as total_sales
    FROM order_detail od
    JOIN orders o ON od.order_id = o.order_id
    JOIN product p ON od.product_id = p.product_id
    WHERE o.order_status = 'ชำระแล้ว' AND o.order_date BETWEEN '$start_date' AND '$end_date'
    $supplier_where
    GROUP BY p.product_id ORDER BY total_sales DESC LIMIT 10";
$resTopSell = mysqli_query($conn, $sqlTopSell);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>พิมพ์รายงานสรุปยอดขาย</title>
    <link rel="icon" type="image/x-icon" href="../logo.jpg">
    <style>
        body { 
            margin: 0; 
            padding: 20px;
            background: #f8fafc; 
            font-family: 'Segoe UI', sans-serif; 
        }

        /* ขอบเขตของกระดาษ A4 */
        .paper-a4 {
            background: #fff;
            max-width: 210mm; /* ความกว้างกระดาษ A4 */
            min-height: 297mm; /* ความสูงกระดาษ A4 */
            margin: 0 auto;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header-flex { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1e293b; padding-bottom: 20px; margin-bottom: 30px; }
        .header-flex h1 { margin: 0; font-size: 26px; color: #1e293b; }
        .header-flex p { margin: 5px 0 0; color: #475569; font-size: 14px;}

        .stat-grid { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 40px; }
        .stat-box { flex: 1; padding: 15px; border-radius: 8px; background: #f1f5f9; text-align: center; border: 1px solid #cbd5e1; }
        .stat-box h3 { margin: 0; color: #475569; font-size: 14px; text-transform: uppercase; }
        .stat-box p { margin: 10px 0 0; font-size: 20px; font-weight: bold; color: #1e293b; }

        .table-report { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-report th { background: #3b82f6; color: #fff; padding: 12px; text-align: left; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .table-report td { padding: 12px; border-bottom: 1px solid #edf2f7; color: #000; }
        
        .footer { margin-top: 50px; text-align: right; color: #64748b; font-size: 12px; border-top: 1px solid #edf2f7; padding-top: 10px; }

        /* ปุ่มควบคุม (ไม่โชว์ตอนปริ้น) */
        .control-buttons { text-align: center; margin-bottom: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; margin: 0 5px; color: #fff;}

        @media print {
            @page { size: A4; margin: 10mm; }
            body { background: #fff; padding: 0; }
            .paper-a4 { box-shadow: none; max-width: 100%; min-height: auto; padding: 0; border: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="control-buttons no-print">
        <button class="btn" onclick="window.print()" style="background: #3b82f6;">🖨️ พิมพ์รายงาน</button>
        <button class="btn" onclick="window.close()" style="background: #ef4444;">❌ ย้อนกลับ</button>
    </div>

    <div class="paper-a4">
        <div class="header-flex">
            <div>
                <h1>รายงานสรุปยอดขายสินค้า</h1>
                <p><strong>โรงงาน:</strong> <?= htmlspecialchars($display_supplier_name) ?></p>
                <p><strong>ประจำวันที่:</strong> <?= date('d/m/Y', strtotime($start_date)) ?> <strong>ถึง</strong> <?= date('d/m/Y', strtotime($end_date)) ?></p>
            </div>
            <div style="text-align: right;">
                <h2 style="margin: 0; color: #3b82f6; -webkit-print-color-adjust: exact; print-color-adjust: exact;">เอกสารรายงาน</h2>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-box">
                <h3>ยอดขาย</h3>
                <p><?= number_format($resOverview['total_revenue'] ?? 0, 2) ?> บาท</p>
            </div>
            <div class="stat-box">
                <h3>จำนวนคำสั่งซื้อ</h3>
                <p><?= number_format($resOverview['total_orders'] ?? 0) ?> ใบ</p>
            </div>
            <div class="stat-box">
                <h3>จำนวนลูกค้า</h3>
                <p><?= number_format($resOverview['total_customers'] ?? 0) ?> ราย</p>
            </div>
        </div>

        <h3 style="color: #1e293b;">รายการสินค้าขายดี (Top 10)</h3>
        <table class="table-report">
            <thead>
                <tr>
                    <th style="width: 10%; text-align: center;">อันดับ</th>
                    <th style="width: 50%;">ชื่อสินค้า</th>
                    <th style="width: 20%; text-align: center;">จำนวนที่ขาย</th>
                    <th style="width: 20%; text-align: right;">ยอดขายรวม (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; if (mysqli_num_rows($resTopSell) > 0): while ($row = mysqli_fetch_assoc($resTopSell)): ?>
                    <tr>
                        <td style="text-align: center;"><?= $rank++ ?></td>
                        <td style="font-weight: 500; color: #000;"><?= htmlspecialchars($row['product_name']) ?></td>
                        <td style="text-align: center;"><?= number_format($row['total_qty']) ?></td>
                        <td style="text-align: right; font-weight: bold;"><?= number_format($row['total_sales'], 2) ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="4" style="text-align:center; padding: 30px;">ไม่พบข้อมูล</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer">
            พิมพ์เมื่อวันที่: <?= date('d/m/Y H:i') ?> | ออกรายงานโดยระบบจัดการอัตโนมัติ
        </div>
    </div>

</body>
</html>