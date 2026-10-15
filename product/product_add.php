<?php
require_once "../auth.php";
require_once "../php/connect.php";


$suppliers = mysqli_query($conn, "SELECT * FROM supplier");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $price = number_format((float)$_POST['price'], 2, '.', '');
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $supplier_id = (int)$_POST['supplier_id'];

    $sql = "INSERT INTO product (product_name, price, unit, supplier_id)
                            VALUES ('$name', '$price', '$unit', '$supplier_id')";
    mysqli_query($conn, $sql);

    header("Location: product_list.php");
    exit;
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="icon" type="image/x-icon" href="../logo.jpg">
    <link rel="stylesheet" href="/PROJECT/style.css">
    <link rel="stylesheet" href="../form.css">
</head>

<body>
    <?php include "../aside.php"; ?>

    <div class="content form-page">
        <div class="form-wrapper">
            <div class="form-card">

                <h2 class="form-title">เพิ่มสินค้า</h2>


                <form method="post">

                    <div class="form-group">
                        <label>ชื่อสินค้า</label>
                        <input type="text" name="product_name" required>
                    </div>

                    <div class="form-group">
                        <label>ราคา</label>
                        <input type="number" step="0.01" name="price">
                    </div>

                    <div class="form-group">
                        <label>หน่วย</label>
                        <select name="unit" required>
                            <option value="กระสอบ">กระสอบ</option>
                            <option value="กิโลกรัม">กิโลกรัม</option>
                            <option value="ลัง">ลัง</option>
                            <option value="ขวด">ขวด</option>
                            <option value="ตัน">ตัน</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>โรงงาน / Supplier</label>
                        <select name="supplier_id" required>
                            <?php while ($s = mysqli_fetch_assoc($suppliers)) { ?>
                                <option value="<?= $s['supplier_id']; ?>">
                                    <?= $s['supplier_name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <a href="product_list.php" class="btn-secondary">กลับ</a>
                        <button type="submit" class="btn-submit">บันทึก</button>
                    </div>

                </form>

            </div>
        </div>
    </div>

</body>

</html>