<?php
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ</title>
    <link rel="icon" type="image/x-icon" href="../logo.jpg">

    <style>
        /* ... CSS เดิมของคุณ ... */
        body {
            margin: 0;
            height: 100vh;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: linear-gradient(135deg, #e9f0f7, #f4f6f8);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .box {
            width: 320px;
            background: #fff;
            padding: 30px 25px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        .box h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        input {
            width: 92%;
            padding: 10px 12px;
            margin-bottom: 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        /* ปรับปรุงปุ่ม Button */
        button {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: none;
            background: #007bff;
            color: #fff;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px; /* เว้นวรรคระหว่างหมุนๆ กับตัวอักษร */
        }

        button:hover {
            background: #0056b3;
        }

        button:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        /* สร้างตัวหมุน (Spinner) */
        .spinner {
            display: none; /* ปิดไว้ก่อน */
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .login-error {
            background:#fdecea;
            color:#b42318;
            padding:12px 14px;
            border-radius:8px;
            font-size:14px;
            margin-bottom:14px;
        }
    </style>
</head>

<body>

    <div class="box">
        <img src="logo.jpg" alt="Logo" class="logo-login">
        <h3>เข้าสู่ระบบ</h3>
        
        <?php if (!empty($_SESSION['login_error'])) { ?>
            <div class="login-error">
                <?= $_SESSION['login_error']; ?>
            </div>
        <?php unset($_SESSION['login_error']); } ?>

        <form id="loginForm" method="post" action="checklogin.php">
            <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            
            <button type="submit" id="submitBtn">
                <span class="spinner" id="btnSpinner"></span>
                <span id="btnText">เข้าสู่ระบบ</span>
            </button>
        </form>
    </div>

<script>
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');

    loginForm.onsubmit = function(e) {
        e.preventDefault();

        submitBtn.disabled = true;
        btnSpinner.style.display = 'inline-block';
        btnText.innerText = 'กำลังตรวจสอบข้อมูล...';

        setTimeout(() => {
            loginForm.submit(); 
        }, 1000); 
    };
</script>
</body>
</html>