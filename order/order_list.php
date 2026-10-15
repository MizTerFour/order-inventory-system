<?php
require_once "../auth.php";
require_once "../php/connect.php";

/* ===== ดึงข้อมูลคำสั่งซื้อทั้งหมด ===== */
$sql = "
SELECT o.order_id,
       o.order_date,
       o.total_amount,
       o.order_status,
       o.delivery_status,
       o.payment_type,
       o.due_date,
       c.customer_name,
       r.receipt_id
FROM orders o
JOIN customer c ON o.customer_id = c.customer_id
LEFT JOIN receipt r ON o.order_id = r.order_id
ORDER BY o.order_id DESC
";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายการคำสั่งซื้อ - ระบบจัดการ</title>
    <link rel="icon" type="image/x-icon" href="../logo.jpg">
    <link rel="stylesheet" href="/PROJECT/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
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

        #orderTable_wrapper {
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

        table td a.btn-action {
            display: inline-block;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 999px;
            text-decoration: none !important;
            margin: 2px 4px;
            transition: all 0.2s ease;
        }
    </style>
</head>

<body>

    <?php include "../aside.php"; ?>

    <div class="content">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="color: #1e293b;">📦 รายการคำสั่งซื้อ</h2>
            <a href="order_add.php" class="btn-add">+ สร้างคำสั่งซื้อใหม่</a>
        </div>

        <table id="orderTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>วันที่</th>
                    <th>ลูกค้า</th>
                    <th>ยอดรวม</th>
                    <th>สถานะเงิน</th>
                    <th>การจัดส่ง</th>
                    <th>การชำระ</th>
                    <th>วันครบกำหนด</th>
                    <th>ใบเสร็จ</th>
                    <th class="no-print">รายละเอียด</th>
                    <th class="no-print">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $today = date('Y-m-d'); // ดึงวันที่ปัจจุบันมาไว้เช็ก

                while ($row = mysqli_fetch_assoc($result)) {
                    // --- ตรวจสอบสถานะว่าเลยกำหนด หรือไม่ ---
                    $is_overdue = false;
                    $status_text = $row['order_status'];
                    $badge_class = 'pending'; // สีเหลือง/ส้มเริ่มต้น

                    if ($row['order_status'] === 'ชำระแล้ว') {
                        $badge_class = 'paid'; // สีเขียว
                    } elseif ($row['order_status'] === 'ยกเลิกรายการ' || $row['order_status'] === 'ยกเลิก') {
                        $badge_class = 'no-receipt'; // สีเทา
                    } else {
                        // ถ้ารอชำระเงิน ให้เช็กวันที่
                        if (!empty($row['due_date']) && $row['due_date'] < $today) {
                            $is_overdue = true;
                            $badge_class = 'overdue'; // เปลี่ยนคลาสเป็นสีแดง
                            $status_text = 'เลยกำหนดชำระ';
                        }
                    }
                ?>
                    <tr>
                        <td><?= $row['order_id']; ?></td>
                        <td data-sort="<?= $row['order_date']; ?>"><?= date('d/m/Y', strtotime($row['order_date'])); ?></td>
                        <td><?= htmlspecialchars($row['customer_name']); ?></td>
                        <td class="amount"><?= number_format($row['total_amount'], 2); ?></td>

                        <td>
                            <span class="badge <?= $badge_class; ?>"><?= $status_text; ?></span>
                        </td>

                        <?php $del_status = $row['delivery_status'] ?? 'รอจัดส่ง'; ?>
                        <td data-search="<?= $del_status ?>">
                            <select class="status-dropdown" data-id="<?= $row['order_id']; ?>" data-field="delivery_status" style="padding: 4px; border-radius: 4px; font-size: 12px; font-weight: bold; border: 1px solid #cbd5e1; cursor: pointer; outline: none;">
                                <option value="รอจัดส่ง" <?= $del_status == 'รอจัดส่ง' ? 'selected' : '' ?>>⏳ รอจัดส่ง</option>
                                <option value="กำลังจัดส่ง" <?= $del_status == 'กำลังจัดส่ง' ? 'selected' : '' ?>>🚚 กำลังจัดส่ง</option>
                                <option value="รับของแล้ว" <?= $del_status == 'รับของแล้ว' ? 'selected' : '' ?>>📦 รับของแล้ว</option>
                            </select>
                        </td>

                        <td><?= $row['payment_type'] ?? '-' ?></td>
                        <td data-sort="<?= $row['due_date'] ?? ''; ?>">
                            <?php
                            if ($row['due_date']) {
                                // แปลงรูปแบบวันที่เป็น dd/mm/yyyy
                                $formatted_due_date = date('d/m/Y', strtotime($row['due_date']));

                                if ($is_overdue) {
                                    echo "<span class='badge overdue' style='color:red; font-weight:bold;'>{$formatted_due_date}</span>";
                                } else {
                                    echo $formatted_due_date;
                                }
                            } else {
                                echo "-";
                            }
                            ?>
                        </td>

                        <td>
                            <?php if ($row['receipt_id']) { ?>
                                <span class="badge receipt">ออกแล้ว</span>
                            <?php } else { ?>
                                <span class="badge no-receipt">ยังไม่ออก</span>
                            <?php } ?>
                        </td>

                        <td class="no-print">
                            <a href="order_details.php?id=<?= $row['order_id']; ?>"
                                class="btn-action"
                                style="background: #fff7ed; color: #f97316; border: 1px solid #fdba74;">
                                ดูข้อมูล
                            </a>
                        </td>

                        <td class="no-print">
                            <a href="order_edit.php?id=<?= $row['order_id']; ?>"
                                class="btn-action"
                                style="background: #eef2ff; color: #1d4ed8; border: 1px solid #c7d2fe;">แก้ไข</a>
                            <a href="order_delete.php?id=<?= $row['order_id']; ?>"
                                class="btn-action"
                                style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;"
                                onclick="return confirm('ยืนยันการลบรายการนี้?')">ลบ</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        // --- 1. ฟังก์ชันเปลี่ยนสี Dropdown ---
        function updateDropdownColor(dropdown) {
            let val = dropdown.val();
            if (val === 'รับของแล้ว' || val === 'ชำระแล้ว') {
                dropdown.css({
                    'background-color': '#dcfce7',
                    'color': '#166534'
                });
            } else if (val === 'กำลังจัดส่ง') {
                dropdown.css({
                    'background-color': '#fef08a',
                    'color': '#854d0e'
                });
            } else if (val === 'ยกเลิกรายการ') {
                dropdown.css({
                    'background-color': '#fee2e2',
                    'color': '#b91c1c'
                });
            } else {
                dropdown.css({
                    'background-color': '#f1f5f9',
                    'color': '#475569'
                });
            }
        }

        $(document).ready(function() {
            // --- 2. ส่วนของ DataTables ---
            // 🟢 จุดที่แก้ที่ 1: ประกาศตัวแปร table เพื่อเอาไว้สั่งอัปเดตค่าทีหลัง
            var table = $('#orderTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json"
                },
                "pageLength": 10,
                "order": [
                    [0, "desc"]
                ],
                "columnDefs": [{
                    "orderable": false,
                    "targets": [9, 10]
                }]
            });

            // --- 3. ส่วนของ AJAX (อัปเดตสถานะแบบเรียลไทม์) ---
            $('.status-dropdown').each(function() {
                updateDropdownColor($(this));
            });

            $('#orderTable').on('change', '.status-dropdown', function() {
                let dropdown = $(this);
                let order_id = dropdown.data('id');
                let field = dropdown.data('field');
                let value = dropdown.val();

                updateDropdownColor(dropdown);

                $.ajax({
                    url: 'ajax_update_status.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        order_id: order_id,
                        field: field,
                        value: value
                    },
                    success: function(res) {
                        if (res.status === 'success') {

                            alert('✅ อัปเดตสถานะออเดอร์ #' + order_id + ' สำเร็จ!');

                            let originalBg = dropdown.css('background-color');
                            dropdown.css('background-color', '#86efac');
                            setTimeout(() => updateDropdownColor(dropdown), 500);

                            // สั่งให้ DataTables อัปเดตระบบค้นหาทันที//
                            if (field === 'delivery_status') {
                                let td = dropdown.closest('td'); 
                                td.attr('data-search', value); 
                                table.cell(td).invalidate().draw(false); 
                            }

                            if (field === 'order_status' && value === 'ชำระแล้ว') {
                                location.reload();
                            }
                        } else {
                            alert('❌ เกิดข้อผิดพลาดในการอัปเดตสถานะ');
                        }
                    },
                    error: function() {
                        alert('❌ ไม่สามารถติดต่อเซิร์ฟเวอร์ได้ กรุณาตรวจสอบไฟล์ ajax_update_status.php');
                    }
                });
            });

        });
    </script>
</body>

</html>