<?php
require_once "../auth.php";
require_once "../php/connect.php";

$order_id = (int)$_GET['id'];

// ดึง order
$order = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT o.*, c.customer_name
     FROM orders o
     JOIN customer c ON o.customer_id = c.customer_id
     WHERE o.order_id = $order_id"
));

if (!$order) {
    header("Location: order_list.php");
    exit;
}

$is_paid = ($order['order_status'] === 'ชำระแล้ว');


$items = mysqli_query(
    $conn,
    "SELECT od.product_id, od.quantity,
            p.product_name, p.price, p.unit
     FROM order_detail od
     JOIN product p ON od.product_id = p.product_id
     WHERE od.order_id = $order_id"
);

// ดึงสินค้าไว้เพิ่มใหม่
$products = mysqli_query(
    $conn,
    "SELECT product_id, product_name, unit, price
     FROM product"
);


if (isset($_POST['save'])) {
    $status          = mysqli_real_escape_string($conn, $_POST['order_status']);
    $delivery_status = mysqli_real_escape_string($conn, $_POST['delivery_status']);
    $address         = mysqli_real_escape_string($conn, trim($_POST['shipping_address']));
    $note            = mysqli_real_escape_string($conn, trim($_POST['note'] ?? ''));

    // จัดการอัปโหลดไฟล์สลิป
    $slip_update = "";
    if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION);
        $new_filename = "slip_" . $order_id . "_" . time() . "." . $ext;
        $upload_path = "../uploads/slips/" . $new_filename;

        if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $upload_path)) {
            $slip_update = ", payment_slip='$new_filename'";
        }
    }

    // อัปเดตตาราง orders
    $due_date_update = ($status === 'ชำระแล้ว') ? ", due_date=NULL" : "";

    mysqli_query(
        $conn,
        "UPDATE orders
         SET order_status='$status',
             delivery_status='$delivery_status',
             shipping_address='$address',
             note='$note'
             $due_date_update
             $slip_update
         WHERE order_id=$order_id"
    );

    // เช็คว่าถ้ายังไม่ชำระ ถึงจะให้แก้รายการสินค้าได้
    if (!$is_paid && isset($_POST['product_id'])) {
        mysqli_query($conn, "DELETE FROM order_detail WHERE order_id=$order_id");
        $total = 0;
        foreach ($_POST['product_id'] as $i => $pid) {
            $qty = (int)$_POST['quantity'][$i];
            if ($qty <= 0) continue;

            $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price, unit FROM product WHERE product_id=$pid"));
            $price = $p['price'];
            $unit  = $p['unit'];

            mysqli_query($conn, "INSERT INTO order_detail (order_id, product_id, quantity, unit, price)
                                 VALUES ('$order_id', '$pid', '$qty', '$unit', '$price')");
            $total += $price * $qty;
        }
        mysqli_query($conn, "UPDATE orders SET total_amount='$total' WHERE order_id=$order_id");
    }

    header("Location: order_details.php?id=$order_id");
    exit;
}
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูล</title>
    <link rel="icon" type="image/x-icon" href="../logo.jpg">
    <link rel="stylesheet" href="/PROJECT/style.css">
    <link rel="stylesheet" href="/PROJECT/form.css">

    <style>
        /* เพิ่มสไตล์ปุ่มให้เหมือนหน้าสร้างคำสั่งซื้อ */
        .btn-add {
            background-color: #0ea5e9 !important;
            color: white !important;
            border: none !important;
        }

        .btn-add:hover {
            background-color: #0284c7 !important;
        }

        .btn-remove {
            background-color: #ef4444 !important;
            color: white !important;
            border: none !important;
        }

        .btn-remove:hover {
            background-color: #dc2626 !important;
        }

        .btn-submit {
            background-color: #10b981 !important;
            color: white !important;
            border: none !important;
        }

        .btn-submit:hover {
            background-color: #059669 !important;
        }
    </style>
</head>


<body>

    <?php include "../aside.php"; ?>

    <div class="content form-page">
        <div class="form-wrapper">
            <div class="form-card">
                <h2 class="form-title">แก้ไขคำสั่งซื้อ #<?= $order_id ?></h2>

                <?php if ($is_paid) { ?>
                    <p style="color:red;">ชำระเงินแล้ว ไม่สามารถแก้ไขได้</p>
                <?php } ?>

                <form method="post" enctype="multipart/form-data">

                    <div class="form-group">
                        <label>ชื่อลูกค้า</label>
                        <input type="text"
                            value="<?= htmlspecialchars($order['customer_name']) ?>"
                            disabled>
                    </div>

                    <div class="form-group">
                        <label>ที่อยู่จัดส่ง</label>
                        <textarea name="shipping_address"
                            <?= $is_paid ? 'readonly' : 'required' ?>><?= htmlspecialchars($order['shipping_address']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>หมายเหตุ</label>
                        <input type="text"
                            name="note"
                            value="<?= htmlspecialchars($order['note'] ?? '') ?>"
                            <?= $is_paid ? 'readonly' : '' ?>>
                    </div>

                    <div class="form-group">
                        <label>วิธีชำระเงิน</label>
                        <input type="text"
                            value="<?= htmlspecialchars($order['payment_type'] ?? '-') ?>"
                            disabled>
                    </div>

                    <?php if (!empty($order['due_date'])) { ?>
                        <div class="form-group">
                            <label>วันครบกำหนด</label>
                            <input type="text"
                                value="<?= $order['due_date'] ?>"
                                disabled>
                        </div>
                    <?php } ?>

                    <div class="form-group">
                        <label>สถานะ</label>
                        <select name="order_status" <?= $is_paid ? 'disabled' : '' ?>>
                            <option value="รอชำระเงิน"
                                <?= $order['order_status'] == 'รอชำระเงิน' ? 'selected' : '' ?>>
                                รอชำระเงิน
                            </option>
                            <option value="ชำระแล้ว"
                                <?= $order['order_status'] == 'ชำระแล้ว' ? 'selected' : '' ?>>
                                ชำระแล้ว
                            </option>

                            <?php if ($order['order_status'] !== 'ชำระแล้ว') { ?>
                                <option value="ยกเลิกรายการ"
                                    <?= $order['order_status'] == 'ยกเลิกรายการ' ? 'selected' : '' ?>>
                                    ยกเลิก
                                </option>
                            <?php } ?>
                        </select>

                        <div class="form-group">
                            <label>สถานะการจัดส่ง</label>
                            <select name="delivery_status">
                                <option value="รอจัดส่ง" <?= ($order['delivery_status'] ?? '') == 'รอจัดส่ง' ? 'selected' : '' ?>>รอจัดส่ง</option>
                                <option value="กำลังจัดส่ง" <?= ($order['delivery_status'] ?? '') == 'กำลังจัดส่ง' ? 'selected' : '' ?>>กำลังจัดส่ง</option>
                                <option value="รับของแล้ว" <?= ($order['delivery_status'] ?? '') == 'รับของแล้ว' ? 'selected' : '' ?>>รับของแล้ว</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>อัปโหลดสลิปโอนเงิน (หากมี)</label>
                            <input type="file" name="payment_slip" accept="image/jpeg, image/png, image/jpg">

                            <?php if (!empty($order['payment_slip'])) { ?>
                                <div style="margin-top: 10px;">
                                    <p style="font-size: 13px; color: green;">มีสลิปในระบบแล้ว (อัปโหลดใหม่เพื่อทับไฟล์เดิม)</p>
                                    <img src="../uploads/slips/<?= $order['payment_slip'] ?>" style="max-width: 200px; border-radius: 8px; border: 1px solid #ccc;">
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <hr style="margin:20px 0;">

                    <h4 style="margin-bottom:10px;">รายการสินค้า</h4>

                    <div id="items">

                        <?php while ($row = mysqli_fetch_assoc($items)) { ?>
                            <div class="item form-group"
                                style="display:flex; gap:10px; align-items:center;">

                                <select name="product_id[]"
                                    <?= $is_paid ? 'disabled' : '' ?>
                                    onchange="updateTotal()"
                                    style="flex:2;">
                                    <?php
                                    mysqli_data_seek($products, 0);
                                    while ($p = mysqli_fetch_assoc($products)) {
                                    ?>
                                        <option value="<?= $p['product_id'] ?>"
                                            data-price="<?= $p['price'] ?>"
                                            <?= $p['product_id'] == $row['product_id'] ? 'selected' : '' ?>>
                                            <?= $p['product_name'] ?> (<?= $p['unit'] ?>)
                                        </option>
                                    <?php } ?>
                                </select>

                                <input type="number"
                                    name="quantity[]"
                                    value="<?= $row['quantity'] ?>"
                                    min="1"
                                    <?= $is_paid ? 'readonly' : '' ?>
                                    oninput="updateTotal()"
                                    style="flex: 0 0 100px; width: 100px;">

                                <?php if (!$is_paid) { ?>
                                    <button type="button"
                                        onclick="removeItem(this)"
                                        class="btn-secondary btn-remove">
                                        ลบ
                                    </button>
                                <?php } ?>

                            </div>
                        <?php } ?>

                    </div>

                    <?php if (!$is_paid) { ?>
                        <div class="form-actions">
                            <button type="button"
                                onclick="addItem()"
                                class="btn-secondary btn-add">
                                + เพิ่มสินค้า
                            </button>
                        </div>
                    <?php } ?>

                    <div class="form-actions" style="margin-top: 20px; text-align: right; display: block;">
                        <h4 style="margin:0; font-size:18px; color:#111827;">
                            ยอดรวมทั้งหมด: <span id="grandTotal" style="color:#000;">0.00</span> บาท
                        </h4>
                    </div>

                    <div class="form-actions" style="margin-top: 20px;">
                        <a href="order_list.php" class="btn-secondary">กลับ</a>
                        <button type="submit" name="save" class="btn-submit">
                            บันทึกการแก้ไข
                        </button>
                    </div>


                </form>

            </div>
        </div>
    </div>

    <script>
        function updateTotal() {
            let grand = 0;

            document.querySelectorAll('#items .item').forEach(item => {
                const select = item.querySelector('select[name="product_id[]"]');
                const qtyInput = item.querySelector('input[name="quantity[]"]');

                if (select && qtyInput) {
                    const selectedOption = select.options[select.selectedIndex];
                    const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                    const qty = parseInt(qtyInput.value) || 0;

                    grand += price * qty;
                }
            });

            document.getElementById('grandTotal').innerText = grand.toFixed(2);
        }

        window.onload = function() {
            updateTotal();
        };

        function addItem() {
            const items = document.getElementById('items');

            // หา item ตัวแรกเพื่อใช้เป็นต้นแบบในการโคลน
            const firstItem = items.querySelector('.item');
            if (!firstItem) return; // ถ้าไม่มีสักแถวให้หยุดทำงาน

            const clone = firstItem.cloneNode(true);

            // รีเซ็ตค่าให้แถวใหม่
            clone.querySelector('input').value = '1';
            clone.querySelector('select').selectedIndex = 0;

            items.appendChild(clone);
            updateTotal();
        }

        function removeItem(btn) {
            const items = document.getElementById('items');
            if (items.children.length > 1) {
                btn.closest('.item').remove();
                updateTotal();
            } else {
                alert('ต้องมีสินค้าอย่างน้อย 1 รายการ');
            }
        }
    </script>

</body>

</html>