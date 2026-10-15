<?php
require_once "../php/connect.php";
require_once "../auth.php";

// ดึงข้อมูลลูกค้าทั้งหมด
$result = mysqli_query($conn, "SELECT * FROM customer ORDER BY customer_id DESC");

// คำนวณ Ratio
$count_query = mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN customer_type = 'เกษตรกร' THEN 1 ELSE 0 END) as farmer_count,
    SUM(CASE WHEN customer_type = 'บริษัท' THEN 1 ELSE 0 END) as company_count
    FROM customer");
$summary = mysqli_fetch_assoc($count_query);

$total = $summary['total'] > 0 ? $summary['total'] : 1;
$farmer_ratio = ($summary['farmer_count'] / $total) * 100;
$company_ratio = ($summary['company_count'] / $total) * 100;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายชื่อลูกค้า - ระบบจัดการ</title>
    <link rel="icon" type="image/x-icon" href="../logo.jpg">
    <link rel="stylesheet" href="/PROJECT/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        .content { padding: 30px; }
        .btn-add { display: inline-block; background: #1075b9; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; margin-bottom: 20px; font-weight: bold; }
        
        /* Ratio Cards Style */
        .ratio-container { display: flex; gap: 20px; margin-bottom: 25px; }
        .ratio-card { background: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex: 1; border-left: 5px solid #1075b9; }
        .ratio-card h4 { margin: 0; color: #64748b; font-size: 0.9rem; }
        .ratio-card .value { font-size: 1.5rem; font-weight: bold; color: #1e293b; margin-top: 5px; }
        .ratio-card .percentage { font-size: 0.85rem; color: #10b981; }

        #customerTable_wrapper { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
        .badge-farmer { background: #dcfce7; color: #166534; }
        .badge-company { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>
    <?php include "../aside.php"; ?>

    <div class="content">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="color: #1e293b;">👥 รายชื่อลูกค้า</h2>
            <a href="customer_add.php" class="btn-add">+ เพิ่มลูกค้าใหม่</a>
        </div>

        <div class="ratio-container">
            <div class="ratio-card" style="border-left-color: #166534;">
                <h4>🌾 สัดส่วนเกษตรกร</h4>
                <div class="value"><?= number_format($summary['farmer_count']); ?> ราย</div>
                <div class="percentage">คิดเป็น <?= number_format($farmer_ratio, 2); ?>% ของทั้งหมด</div>
            </div>
            <div class="ratio-card" style="border-left-color: #1e40af;">
                <h4>🏢 สัดส่วนบริษัท</h4>
                <div class="value"><?= number_format($summary['company_count']); ?> ราย</div>
                <div class="percentage">คิดเป็น <?= number_format($company_ratio, 2); ?>% ของทั้งหมด</div>
            </div>
        </div>

        <table id="customerTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>รหัส</th>
                    <th>ประเภท</th>
                    <th>ชื่อลูกค้า</th>
                    <th>ที่อยู่</th>
                    <th>เบอร์โทรศัพท์</th>
                    <th class="no-print">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { 
                    $type_class = ($row['customer_type'] == 'เกษตรกร') ? 'badge-farmer' : 'badge-company';
                ?>
                    <tr>
                        <td><?= $row['customer_id']; ?></td>
                        <td><span class="badge <?= $type_class ?>"><?= $row['customer_type']; ?></span></td>
                        <td><?= htmlspecialchars($row['customer_name']); ?></td>
                        <td><?= htmlspecialchars($row['address']); ?></td>
                        <td><?= htmlspecialchars($row['phone']); ?></td>
                        <td class="no-print">
                            <a href="customer_edit.php?id=<?= $row['customer_id']; ?>" style="color: #3b82f6;">แก้ไข</a> |
                            <a href="customer_delete.php?id=<?= $row['customer_id']; ?>" 
                               style="color: #ef4444;" 
                               onclick="return confirm('ยืนยันการลบลูกค้าคนนี้?')">ลบ</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#customerTable').DataTable({
                "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json" },
                "order": [[0, "desc"]]
            });
        });
    </script>
</body>
</html>