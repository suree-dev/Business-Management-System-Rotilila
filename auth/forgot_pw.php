<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - Roti Leela</title>
    <!-- ใช้ CSS เดิมเพื่อให้หน้าตาเหมือนกัน -->
    <link rel="stylesheet" href="../style/login.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <div class="side-decoration left"></div>
    <div class="side-decoration right"></div>

    <div class="navbar">
        <img src="../picture/logo.png" alt="Roti-Leela-Logo">
    </div>

    <div class="container">
        <div class="login-box">
            <h1 class="header-title">ลืมรหัสผ่าน</h1>
            <p class="subtitle">กรุณากรอกอีเมลเพื่อรับรหัสผ่านชั่วคราว</p>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message" style="color: #27ae60; background: #e9f7ef; padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 20px; border-left: 4px solid #27ae60;">
                    <i class="fas fa-check-circle"></i> หากอีเมลถูกต้อง ระบบได้ส่งรหัสผ่านชั่วคราวไปให้แล้ว
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message" style="display: block;">
                    <i class="fas fa-times-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <form id="forgotForm" method="POST" action="../auth/forgot_pw_process.php">
                <div class="input-group">
                    <input type="email" id="email" name="email" placeholder=" " required>
                    <div class="input-icon">📧</div>
                    <label for="email">อีเมลที่ลงทะเบียน</label>
                </div>

                <button type="submit" class="login-button">✔️ ส่งรหัสผ่านใหม่</button>

                <div class="forgot-password">
                    <a href="../auth/login.php">กลับไปหน้าเข้าสู่ระบบ</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>