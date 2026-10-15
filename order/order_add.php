<?php
require_once "../auth.php";
require_once "../php/connect.php";



if (isset($_GET['action']) && $_GET['action'] == 'get_customers') {
    $cust_res = mysqli_query($conn, "SELECT customer_id, customer_name, phone, address FROM customer ORDER BY customer_id DESC");
    $data = [];
    while ($r = mysqli_fetch_assoc($cust_res)) {
        $data[] = $r;
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}


// ดึงข้อมูลสินค้า
$products = mysqli_query(
    $conn,
    "SELECT product_id, product_name, unit, price FROM product"
);

// ดึงข้อมูลลูกค้าเพื่อนำไปแสดงใน Dropdown
$customers = mysqli_query(
    $conn,
    "SELECT customer_id, customer_name, phone, address FROM customer ORDER BY customer_id DESC"
);

if (isset($_POST['create_order'])) {

    $customer_id   = (int)$_POST['customer_id'];
    $phone         = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address       = mysqli_real_escape_string($conn, trim($_POST['shipping_address']));
    $payment_type  = mysqli_real_escape_string($conn, $_POST['payment_type']);
    $note          = mysqli_real_escape_string($conn, $_POST['note']);

    // จุดสำคัญ: คำนวณวันกำหนดชำระเงินสำหรับบริษัทตัวกลาง
    $due_date = null;
    if ($payment_type === 'เครดิต 7 วัน') {
        $due_date = date('Y-m-d', strtotime('+7 days'));
    } elseif ($payment_type === 'เครดิต 30 วัน') {
        $due_date = date('Y-m-d', strtotime('+30 days'));
    }

    $product_ids = $_POST['product_id'];
    $quantities  = $_POST['quantity'];

    // อัปเดตข้อมูลเบอร์โทรและที่อยู่ให้ลูกค้า (เผื่อมีการแก้ไขในหน้าฟอร์ม)
    mysqli_query($conn, "UPDATE customer SET phone='$phone', address='$address' WHERE customer_id=$customer_id");

    // จุดสำคัญ: บันทึกข้อมูล Orders พร้อมสถานะและประเภทการจ่ายเงิน
    $due_date_val = $due_date ? "'$due_date'" : "NULL";
    mysqli_query($conn, "INSERT INTO orders 
        (order_date, customer_id, phone, shipping_address, note, total_amount, order_status, payment_type, due_date)
        VALUES (CURDATE(), '$customer_id', '$phone', '$address', '$note', 0, 'รอชำระเงิน', '$payment_type', $due_date_val)");

    $order_id = mysqli_insert_id($conn);
    $total = 0;

    foreach ($product_ids as $i => $pid) {
        $pid = (int)$pid;
        $qty = isset($quantities[$i]) ? (int)$quantities[$i] : 0;
        if ($qty <= 0) continue;

        $p_res = mysqli_query($conn, "SELECT price, unit FROM product WHERE product_id=$pid");
        $p = mysqli_fetch_assoc($p_res);
        $price = $p['price'];
        $unit  = $p['unit'];

        mysqli_query($conn, "INSERT INTO order_detail (order_id, product_id, quantity, unit, price)
                             VALUES ('$order_id', '$pid', '$qty', '$unit', '$price')");
        $total += $price * $qty;
    }

    mysqli_query(
        $conn,
        "UPDATE orders SET total_amount='$total' WHERE order_id=$order_id"
    );

    header("Location: order_list.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างคำสั่งซื้อ</title>
    <link rel="icon" type="image/x-icon" href="../logo.jpg">
    <link rel="stylesheet" href="/PROJECT/style.css">
    <link rel="stylesheet" href="../form.css">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
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

        /* ปรับสไตล์ของ Select2 ให้เข้ากับฟอร์ม */
        .select2-container .select2-selection--single {
            height: 40px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 5px;
        }
    </style>
</head>

<body>

    <?php include "../aside.php"; ?>

    <div class="content form-page">
        <div class="form-wrapper">
            <div class="form-card">

                <h2 class="form-title">สร้างคำสั่งซื้อ</h2>

                <form method="post">

                    <div class="form-group">
                        <label>เลือกลูกค้า</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <select name="customer_id" id="customer_select" required style="width: 100%;">
                                <option value="">-- พิมพ์ค้นหา หรือ เลือกลูกค้า --</option>
                                <?php
                                mysqli_data_seek($customers, 0);
                                while ($c = mysqli_fetch_assoc($customers)) {
                                ?>
                                    <option value="<?= $c['customer_id']; ?>"
                                        data-phone="<?= htmlspecialchars($c['phone']); ?>"
                                        data-address="<?= htmlspecialchars($c['address']); ?>">
                                        <?= htmlspecialchars($c['customer_name']); ?>
                                    </option>
                                <?php } ?>
                            </select>

                            <a href="../customer/customer_add.php" target="_blank" style="white-space: nowrap; padding: 8px 15px; background: #e2e8f0; color: #0f172a; text-decoration: none; border-radius: 4px; border: 1px solid #cbd5e1; font-size: 14px;">
                                + เพิ่มลูกค้า
                            </a>

                            <button type="button" id="btn-refresh-customer" style="white-space: nowrap; padding: 8px 15px; background: #fef08a; color: #854d0e; border: 1px solid #fde047; border-radius: 4px; font-size: 14px; cursor: pointer;">
                                🔄 ดึงชื่อล่าสุด
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>เบอร์โทร</label>
                        <input type="text" name="phone" id="phone" required pattern="[0-9]{9,10}" maxlength="10">
                    </div>

                    <div class="form-group">
                        <label>ที่อยู่จัดส่ง</label>
                        <textarea name="shipping_address" id="shipping_address" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>หมายเหตุ</label>
                        <input type="text" name="note" placeholder="เช่น ฝากโทรก่อนส่ง / ส่งช่วงเช้า">
                    </div>

                    <div class="form-group">
                        <label>วิธีชำระเงิน</label>
                        <select name="payment_type" required>
                            <option value="เงินสด">เงินสด</option>
                            <option value="โอน">โอน</option>
                            <option value="เครดิต 7 วัน">เครดิต 7 วัน</option>
                            <option value="เครดิต 30 วัน">เครดิต 30 วัน</option>
                        </select>
                    </div>

                    <hr style="margin:20px 0;">

                    <h4 style="margin-bottom:10px;">รายการสินค้า</h4>

                    <div id="items">
                        <div class="item form-group" style="display:flex; gap:10px; align-items:center;">

                            <select name="product_id[]" required style="flex:2;" onchange="updateTotal()">
                                <option value="">-- เลือกสินค้า --</option>
                                <?php
                                mysqli_data_seek($products, 0);
                                while ($p = mysqli_fetch_assoc($products)) { ?>
                                    <option value="<?= $p['product_id']; ?>"
                                        data-price="<?= $p['price']; ?>"
                                        data-unit="<?= $p['unit']; ?>">
                                        <?= $p['product_name']; ?>
                                    </option>
                                <?php } ?>
                            </select>

                            <input type="number" name="quantity[]" min="1" placeholder="จำนวน" required style="flex: 0 0 100px; width: 100px;" oninput="updateTotal()">

                            <span class="unit-text" style="width: 50px; font-size: 14px; color: #4b5563;"></span>

                            <button type="button" onclick="removeItem(this)" class="btn-secondary btn-remove">
                                ลบ
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" onclick="addItem()" class="btn-secondary btn-add">
                            + เพิ่มสินค้า
                        </button>
                    </div>

                    <div class="form-actions" style="margin-top: 20px; text-align: right; display: block;">
                        <h4 style="margin:0; font-size:18px; color:#111827;">
                            ยอดรวมทั้งหมด: <span id="grandTotal" style="color: #000;">0.00</span> บาท
                        </h4>
                    </div>
                    <div class="form-actions" style="margin-top: 20px;">
                        <a href="order_list.php" class="btn-secondary">กลับ</a>
                        <button type="submit" name="create_order" class="btn-submit">
                            สร้างคำสั่งซื้อ
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // เปิดใช้งาน Select2 สำหรับช่องเลือกลูกค้า
            $('#customer_select').select2({
                placeholder: "-- พิมพ์ค้นหา หรือ เลือกลูกค้า --"
            });

            // เมื่อมีการเปลี่ยนตัวเลือกลูกค้า
            $('#customer_select').on('change', function() {
                // ดึงข้อมูล option ที่ถูกเลือก
                let selected = $(this).find('option:selected');

                // ดึงค่าเบอร์โทร และ ที่อยู่จาก data-attribute
                let phone = selected.data('phone') || '';
                let address = selected.data('address') || '';

                // นำไปใส่ในช่อง Input อัตโนมัติ
                $('#phone').val(phone);
                $('#shipping_address').val(address);
            });
        });

        function updateTotal() {
            let grand = 0;

            document.querySelectorAll('#items .item').forEach(item => {
                const select = item.querySelector('select[name="product_id[]"]');
                const qtyInput = item.querySelector('input[name="quantity[]"]');
                const unitText = item.querySelector('.unit-text');

                if (select && qtyInput) {
                    const selectedOption = select.options[select.selectedIndex];
                    const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                    const unit = selectedOption.getAttribute('data-unit') || '';

                    const qty = parseInt(qtyInput.value) || 0;

                    if (unitText) {
                        unitText.innerText = unit;
                    }

                    grand += (price * qty);
                }
            });

            document.getElementById('grandTotal').innerText = grand.toFixed(2);
        }

        window.onload = function() {
            updateTotal();
        };

        function addItem() {
            const items = document.getElementById('items');
            const firstItem = items.querySelector('.item');
            const clone = firstItem.cloneNode(true);

            clone.querySelector('input').value = '';
            clone.querySelector('select').selectedIndex = 0;
            const unitText = clone.querySelector('.unit-text');
            if (unitText) unitText.innerText = '';

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


        $('#btn-refresh-customer').click(function() {
            let btn = $(this);
            let originalText = btn.html();
            btn.html('⏳ กำลังดึงข้อมูล...');
            btn.prop('disabled', true);

            $.ajax({
                url: 'order_add.php?action=get_customers',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    let select = $('#customer_select');
                    select.empty(); // ล้างข้อมูลเดิมออก
                    select.append('<option value="">-- พิมพ์ค้นหา หรือ เลือกลูกค้า --</option>');

                    // วนลูปเพิ่มข้อมูลใหม่เข้าไป
                    data.forEach(function(c) {
                        select.append(`<option value="${c.customer_id}" data-phone="${c.phone}" data-address="${c.address}">${c.customer_name}</option>`);
                    });

                    // แจ้งให้ Select2 รู้ว่าข้อมูลเปลี่ยนแล้ว
                    select.trigger('change');

                    btn.html(originalText);
                    btn.prop('disabled', false);
                },
                error: function() {
                    alert('เกิดข้อผิดพลาดในการดึงข้อมูล');
                    btn.html(originalText);
                    btn.prop('disabled', false);
                }
            });
        });
    </script>

</body>

</html>