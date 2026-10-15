<?php
require_once "../auth.php";
require_once "../php/connect.php";

/* ดึงข้อมูลสินค้าทั้งหมดพร้อมชื่อโรงงาน */
$sql = "
    SELECT p.*, s.supplier_name
    FROM product p
    LEFT JOIN supplier s ON p.supplier_id = s.supplier_id
    ORDER BY p.product_id ASC
";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายการสินค้า</title>
    <link rel="icon" type="image/x-icon" href="../logo.jpg">
    <link rel="stylesheet" href="/PROJECT/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
        /* แก้ไข ID ให้ตรงกับชื่อตารางสินค้า เพื่อให้ดีไซน์ออกมาเหมือนกัน */
        .content {
            padding: 30px;
        }

        .btn-add {
            display: inline-block;
            background: #1075b9;
            color: white !important;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none !important;
            margin-bottom: 20px;
            font-weight: bold;
        }

        /* ใช้ #productTable_wrapper เพื่อให้ครอบคลุมส่วนค้นหาและส่วนแบ่งหน้า */
        #productTable_wrapper {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        table.dataTable thead th {
            background-color: #f8fafc !important;
            color: #475569 !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }

        .dataTables_filter input {
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            padding: 5px 10px !important;
            margin-bottom: 10px !important;
        }
    </style>
</head>

<body>

    <?php include "../aside.php"; ?>

    <div class="content">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="color: #1e293b;">📦 รายการสินค้า</h2>
            <a href="product_add.php" class="btn-add">+ เพิ่มสินค้าใหม่</a>
        </div>

        <table id="productTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>รหัสสินค้า</th>
                    <th>ชื่อสินค้า</th>
                    <th>ราคา</th>
                    <th>หน่วย</th>
                    <th>ซัพพลายเออร์</th>
                    <th class="no-print">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?= $row['product_id']; ?></td>
                        <td><?= htmlspecialchars($row['product_name']); ?></td>
                        <td class="amount"><?= number_format($row['price'], 2); ?></td>
                        <td><?= htmlspecialchars($row['unit']); ?></td>
                        <td><?= htmlspecialchars($row['supplier_name']); ?></td>
                        <td class="no-print">
                            <a href="product_edit.php?id=<?= $row['product_id']; ?>" style="color: #3b82f6;">แก้ไข</a> |
                            <a href="product_delete.php?id=<?= $row['product_id']; ?>"
                                style="color: #ef4444;"
                                onclick="return confirm('ยืนยันการลบสินค้านี้?')">ลบ</a>
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
            $('#productTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json" // ภาษาไทย
                },
                "pageLength": 10,
                "order": [
                    [0, "asc"]
                ],
                "columnDefs": [{
                    "orderable": false,
                    "targets": 5
                }]
            });
        });
    </script>
</body>

</html>