<?php
require_once "../auth.php";
require_once "../php/connect.php";

/* ดึงข้อมูลซัพพลายเออร์ทั้งหมด (DataTables จะจัดการแบ่งหน้าและค้นหาเองที่หน้าเว็บ) */
$result = mysqli_query($conn, "SELECT * FROM supplier ORDER BY supplier_id DESC");
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายชื่อซัพพลายเออร์</title>
    <link rel="icon" type="image/x-icon" href="../logo.jpg">
    <link rel="stylesheet" href="/PROJECT/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    
    <style>
        /* ปรับแต่งให้เข้ากับสไตล์มาตรฐานที่คุณต้องการ */
        .content { padding: 30px; }
        
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

        /* ใช้ #supplierTable_wrapper เพื่อให้ระบบ Shadow และ Card ทำงานตาม style.css */
        #supplierTable_wrapper { 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
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
            <h2 style="color: #1e293b;">🏢 รายชื่อซัพพลายเออร์</h2>
            <a href="supplier_add.php" class="btn-add">+ เพิ่มซัพพลายเออร์ใหม่</a>
        </div>

        <table id="supplierTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>ชื่อซัพพลายเออร์</th>
                    <th>ที่อยู่</th>
                    <th>เบอร์โทร</th>
                    <th>เลขผู้เสียภาษี</th>
                    <th>Fax</th>
                    <th class="no-print">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['supplier_name']); ?></td>
                        <td><?= htmlspecialchars($row['address']); ?></td>
                        <td><?= htmlspecialchars($row['phone']); ?></td>
                        <td><?= htmlspecialchars($row['numtax']); ?></td>
                        <td><?= htmlspecialchars($row['Fax']); ?></td>
                        <td class="no-print">
                            <a href="supplier_edit.php?id=<?= $row['supplier_id']; ?>" style="color: #3b82f6;">แก้ไข</a> |
                            <a href="supplier_delete.php?id=<?= $row['supplier_id']; ?>"
                               style="color: #ef4444;"
                               onclick="return confirm('ยืนยันการลบซัพพลายเออร์นี้?')">ลบ</a>
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

        $('#supplierTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json" 
            },
            "pageLength": 10,
            "order": [[ 0, "asc" ]], 
            "columnDefs": [
                { "orderable": false, "targets": 5 } 
            ]
        });
    });
    </script>
</body>

</html>