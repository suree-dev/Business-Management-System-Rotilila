<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Roti Leela</title>
    <link rel="stylesheet" href="../style/login.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <div class="side-decoration left"></div>
    <div class="side-decoration right"></div>

    <div class="navbar">
        <img src="../picture/logo.png" alt="Roti-Leela-Logo">
    </div>

    <!-- Container หลัก -->
    <div class="container">
        <div class="login-box">
            <h1 class="header-title">เข้าสู่ระบบ</h1>
            <p class="subtitle">ระบบจัดการร้านโรตีลีลา</p>
            
            <form id="loginForm" method="POST" action="../auth/login_process.php">
                <div class="input-group">
                    <input type="text" id="username" name="username" placeholder=" ">
                    <div class="input-icon">👨‍🍳</div>
                    <label for="username">ชื่อผู้ใช้งาน</label>
                </div>

                <div class="input-group">
                    <!-- FIX: เพิ่ม placeholder=" " เพื่อให้ label ทำงานเหมือนช่อง username -->
                    <input type="password" id="password" name="password" placeholder=" ">
                    <div class="input-icon">🔐</div>
                    <label for="password">รหัสผ่าน</label>
                    <!-- FIX: ย้ายปุ่มออกมาจาก .input-icon เพื่อให้จัดตำแหน่งง่ายขึ้น -->
                    <button type="button" id="togglePasswordBtn" onclick="togglePassword()">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>

                <div id="password-error" class="error-message" style="display: none;"></div>

                <button type="submit" class="login-button" id="loginButton">🍽️ เข้าสู่ระบบร้าน</button>
                
                <!-- ADD: เพิ่มส่วน "ลืมรหัสผ่าน" -->
                <div class="forgot-password">
                    <a href="forgot_pw.php">ลืมรหัสผ่าน?</a>
                </div>
                
                <div class="loading" id="loading">
                    <div class="loading-spinner"></div>
                    <p>กำลังเข้าสู่ระบบ กรุณารอสักครู่...</p>
                </div>
            </form>
        </div>
    </div>

<script src="../js/login.js?v=<?= time() ?>"></script>

</body>
</html>