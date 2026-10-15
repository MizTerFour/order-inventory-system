<?php
require_once "../auth.php";
require_once "../php/connect.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
    $numtax = mysqli_real_escape_string($conn, $_POST['numtax']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $fax = mysqli_real_escape_string($conn, $_POST['Fax']);

    $sql = "INSERT INTO supplier 
            (supplier_name, numtax, address, phone, email)
            VALUES ('$name', '$numtax', '$address', '$phone', '$email')";

    mysqli_query($conn, $sql);
    header("Location: supplier_list.php");
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

                <h2 class="form-title">เพิ่มซัพพลายเออร์</h2>

                <form method="post">

                    <div class="form-group">
                        <label>ชื่อซัพพลายเออร์</label>
                        <input type="text" name="supplier_name" required>
                    </div>

                    <div class="form-group">
                        <label>ที่อยู่</label>
                        <textarea name="address"></textarea>
                    </div>

                    <div class="form-group">
                        <label>โทรศัพท์</label>
                        <input type="text" name="phone" required pattern="[0-9]{9,10}" maxlength="10" title="กรุณากรอกเบอร์โทรศัพท์เป็นตัวเลข 9 หรือ 10 หลัก">
                    </div>

                    <div class="form-group">
                        <label>เลขผู้เสียภาษี</label>
                        <input type="text" name="numtax" required>
                    </div>

                    <div class="form-group">
                        <label>Fax</label>
                        <input type="text" name="Fax">
                    </div>

                    <div class="form-actions">
                        <a href="supplier_list.php" class="btn-secondary">กลับ</a>
                        <button type="submit" class="btn-submit">บันทึก</button>
                    </div>

                </form>

            </div>
        </div>
    </div>

</body>

</html>