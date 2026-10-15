<?php
require_once "../auth.php";
require_once "../php/connect.php";

// --- 1. รับค่าฟิลเตอร์วันที่และโรงงาน ---
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$supplier_id = $_GET['supplier_id'] ?? '';

// --- 2. สร้างเงื่อนไข SQL ตามโรงงานที่เลือก ---
$supplier_where = "";
if (!empty($supplier_id)) {
    $supplier_where = " AND p.supplier_id = '$supplier_id' ";
}

// --- 3. ดึงรายชื่อโรงงานทั้งหมดมาใส่ใน Dropdown ---
$suppliers_res = mysqli_query($conn, "SELECT supplier_id, supplier_name FROM supplier");

// --- 4. ดึงข้อมูลภาพรวม ---
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

// --- 5. ดึงข้อมูลสินค้าขายดี (ใช้ p.product_id และ p.product_name ตาม SQL ของคุณ) ---
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
    <title>รายงานสรุปยอดขาย</title>
    <link rel="stylesheet" href="/PROJECT/style.css">
    <link rel="icon" type="image/x-icon" href="../logo.jpg">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        body {
            margin: 0;
            display: flex;
            background: #f8fafc;
            font-family: 'Segoe UI', sans-serif;
        }

        .report-page {
            flex: 1;
            padding: 40px;
            min-height: 100vh;
        }

        .report-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 1000px;
            margin: auto;
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 20px;
            /* ลดลงนิดนึงให้ปุ่มไม่ห่างเกิน */
        }

        /* --- ส่วนที่เพิ่มใหม่: ปุ่ม Quick Filters --- */
        .quick-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .btn-quick {
            background: #e2e8f0;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            transition: 0.2s;
        }

        .btn-quick:hover {
            background: #cbd5e1;
            color: #0f172a;
        }

        /* ----------------------------------- */

        .no-print-filter {
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: flex-end;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-item label {
            font-size: 12px;
            font-weight: bold;
            color: #64748b;
        }

        .filter-item select,
        .filter-item input {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            font-size: 14px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-box {
            padding: 20px;
            border-radius: 10px;
            background: #f1f5f9;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .stat-box p {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
        }

        .table-report {
            width: 100%;
            border-collapse: collapse;
        }

        .table-report th {
            background: #3b82f6;
            color: #fff;
            padding: 12px;
            text-align: left;
        }

        .table-report td {
            padding: 12px;
            border-bottom: 1px solid #edf2f7;
        }

        .clickable-row {
            cursor: pointer;
        }

        .clickable-row:hover {
            background-color: #f1f5f9 !important;
        }

        #orderModal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: #fff;
            margin: 5% auto;
            padding: 25px;
            border-radius: 12px;
            width: 85%;
            max-width: 850px;
            position: relative;
        }

        @media print {

            .no-print,
            aside,
            .no-print-filter,
            .quick-filters {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <?php include "../aside.php"; ?>
    <div class="report-page">
        <div class="report-card">
            <div class="header-flex">
                <div>
                    <h1 style="margin:0; font-size: 24px; color: #1e293b;">📈 รายงานสรุปยอดขาย</h1>
                    <p style="color: #64748b; margin: 5px 0 0;">
                        ช่วงวันที่: <strong><?= date('d/m/Y', strtotime($start_date)) ?></strong> ถึง <strong><?= date('d/m/Y', strtotime($end_date)) ?></strong>
                    </p>
                </div>
                <a href="reportpdf.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&supplier_id=<?= $supplier_id ?>"
                    target="_blank"

                    class="btn-printpdf no-print"
                    style="background: #0f172a; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; display: inline-block;">
                    🖨️ พิมพ์รายงาน
                </a>
            </div>

            <div class="quick-filters no-print">
                <button type="button" class="btn-quick" onclick="setDateRange('today')">📅 วันนี้</button>
                <button type="button" class="btn-quick" onclick="setDateRange('week')">📆 ประจำสัปดาห์</button>
                <button type="button" class="btn-quick" onclick="setDateRange('month')">🗓️ ประจำเดือน</button>
            </div>
            <form method="get" id="filterForm" class="no-print-filter no-print">
                <div class="filter-item">
                    <label>โรงงาน</label>
                    <select name="supplier_id" onchange="document.getElementById('filterForm').submit()">
                        <option value="">-- ทั้งหมด --</option>
                        <?php mysqli_data_seek($suppliers_res, 0);
                        while ($s = mysqli_fetch_assoc($suppliers_res)): ?>
                            <option value="<?= $s['supplier_id'] ?>" <?= ($supplier_id == $s['supplier_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['supplier_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-item">
                    <label>เริ่ม</label>
                    <input type="text" id="start_date" name="start_date" value="<?= $start_date ?>" placeholder="เลือกวันที่">
                </div>
                <div class="filter-item">
                    <label>ถึง</label>
                    <input type="text" id="end_date" name="end_date" value="<?= $end_date ?>" placeholder="เลือกวันที่">
                </div>
            </form>

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

            <table class="table-report">
                <thead>
                    <tr>
                        <th>อันดับ</th>
                        <th>ชื่อสินค้า</th>
                        <th style="text-align: center;">จำนวนที่ขาย</th>
                        <th style="text-align: right;">ยอดขายรวม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1;
                    while ($row = mysqli_fetch_assoc($resTopSell)): ?>
                        <tr class="clickable-row" onclick="showOrderDetails('<?= $row['product_id'] ?>', '<?= htmlspecialchars($row['product_name']) ?>')">
                            <td><?= $rank++ ?></td>

                            <td style="color: #000; text-decoration: none; font-weight: 500;">
                                <?= htmlspecialchars($row['product_name']) ?>
                            </td>

                            <td style="text-align: center;"><?= number_format($row['total_qty']) ?></td>
                            <td style="text-align: right; font-weight: bold;"><?= number_format($row['total_sales'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="orderModal">
        <div class="modal-content">
            <span onclick="document.getElementById('orderModal').style.display='none'" style="position:absolute; right:20px; top:15px; font-size:28px; cursor:pointer;">&times;</span>
            <h2 id="modalTitle"></h2>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        function showOrderDetails(productId, productName) {
            const modal = document.getElementById('orderModal');
            modal.style.display = 'block';
            document.getElementById('modalTitle').innerText = "รายการขาย: " + productName;
            document.getElementById('modalBody').innerHTML = 'กำลังโหลด...';
            fetch(`get_order_details.php?product_id=${productId}&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>`)
                .then(r => r.text()).then(d => {
                    document.getElementById('modalBody').innerHTML = d;
                });
        }

        //ปรับวันที่ Flatpickr
        flatpickr("#start_date, #end_date", {
            altInput: true,
            altFormat: "d/m/Y",
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr, instance) {
                document.getElementById('filterForm').submit();
            }
        });

        // --- ส่วนที่เพิ่มใหม่: ฟังก์ชันเปลี่ยนวันที่ตามปุ่ม ---
        function setDateRange(type) {
            let today = new Date();
            let start = new Date();
            let end = new Date();

            if (type === 'today') {
                // วันนี้
                start = today;
                end = today;
            } else if (type === 'week') {
                // สัปดาห์นี้ (จันทร์ - อาทิตย์)
                let day = today.getDay() || 7; // ให้วันอาทิตย์เป็น 7
                if (day !== 1) start.setHours(-24 * (day - 1));
                end = new Date(start);
                end.setDate(start.getDate() + 6);
            } else if (type === 'month') {
                // เดือนนี้
                start = new Date(today.getFullYear(), today.getMonth(), 1);
                end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            }

            // เซ็ตค่าลง Input
            document.getElementById('start_date').value = start.toISOString().split('T')[0];
            document.getElementById('end_date').value = end.toISOString().split('T')[0];

            // Submit ฟอร์มทันที
            document.getElementById('filterForm').submit();
        }
        // ----------------------------------------------
    </script>
</body>

</html>