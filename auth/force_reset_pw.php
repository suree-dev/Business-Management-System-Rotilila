<?php
session_start();
// ถ้ายังไม่ได้ล็อกอิน หรือไม่มี session ให้เด้งกลับไปหน้า login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปลี่ยนรหัสผ่าน - Roti Leela</title>
    <link rel="stylesheet" href="../style/login.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h1 class="header-title">ตั้งรหัสผ่านใหม่</h1>
            <p class="subtitle">เพื่อความปลอดภัย กรุณาตั้งรหัสผ่านใหม่</p>
            
            <form id="resetForm" method="POST" action="../auth/force_reset_pw_process.php">
                <!-- New Password -->
                <div class="input-group">
                    <input type="password" id="new_password" name="new_password" placeholder=" ">
                    <div class="input-icon">🔐</div>
                    <label for="new_password">รหัสผ่านใหม่</label>
                    <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('new_password')">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>

                <!-- Confirm Password -->
                <div class="input-group">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder=" ">
                    <div class="input-icon">🔐</div>
                    <label for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
                    <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('confirm_password')">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>

                <div id="password-error" class="error-message" style="display: none;"></div>

                <button type="submit" class="login-button">✔️ ยืนยันการเปลี่ยนรหัสผ่าน</button>
            </form>
        </div>
    </div>

    <!-- เพิ่ม CSS สำหรับปุ่มเปิด/ปิดตา -->
    <style>
        .toggle-password-btn {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            font-size: 16px; color: #666; outline: none; padding: 5px;
        }
        .input-group input {
             padding-right: 55px;
        }
    </style>

    <script src="../js/force_reset_pw.js?v=<?= time() ?>"></script>
</body>
</html>