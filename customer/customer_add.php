<?php
require_once "../php/connect.php";
require_once "../auth.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $type = mysqli_real_escape_string($conn, $_POST['customer_type']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $sql = "INSERT INTO `customer`( `customer_name`, `customer_type`, `address`, `phone`, `email`)
                         VALUES ('$name','$type','$address','$phone','$email')";
    mysqli_query($conn, $sql);

    header("Location: customer_list.php");
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
                <h2 class="form-title">เพิ่มข้อมูลลูกค้า</h2>
                <form method="post">

                    <div class="form-group">
                        <label>ชื่อลูกค้า</label>
                        <input type="text" name="customer_name" required title="กรุณากรอกชื่อ">
                    </div>

                    <div class="form-group">
                        <label>ประเภทลูกค้า</label>
                        <select name="customer_type" required style="width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc;">
                            <option value="เกษตรกร">เกษตรกร</option>
                            <option value="บริษัท">บริษัท</option>
                        </select>
                    </div>


                    <div class="form-group">
                        <label>โทรศัพท์</label>
                        <input type="text" name="phone" required pattern="[0-9]{9,10}" maxlength="10" title="กรุณากรอกเบอร์โทรศัพท์เป็นตัวเลข 9 หรือ 10 หลัก">
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>

                    <div class="form-group">
                        <label>ที่อยู่</label>
                        <textarea name="address"></textarea>
                    </div>
                    <div class="form-actions">
                        <a href="customer_list.php" class="btn-secondary">กลับ</a>
                        <button type="submit" class="btn-submit">บันทึก</button>
                    </div>


                </form>
            </div>
        </div>
    </div>
</body>

</html>