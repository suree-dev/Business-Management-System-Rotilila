<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('../data/db_connect.php'); // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล

// **สำคัญ:** ตรวจสอบว่าผู้ใช้ล็อกอินหรือยัง
// หากยังไม่ได้ล็อกอิน ให้ส่งกลับไปหน้า login.php
// แก้ไข 'user_id' ให้ตรงกับชื่อ Session ที่คุณใช้เก็บ ID ของผู้ใช้ตอนล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // <-- อาจจะต้องเปลี่ยนเป็นหน้าล็อกอินของคุณ
    exit();
}

// ตัวแปรสำหรับเก็บข้อความแจ้งเตือน
$message = '';
$message_type = ''; // 'success' หรือ 'error'

// ตรวจสอบว่ามีการกดปุ่ม Submit ฟอร์มหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // รับค่าจากฟอร์ม
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];

    // 1. ตรวจสอบข้อมูลเบื้องต้น
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน';
        $message_type = 'error';
    } elseif (strlen($new_password) < 8) {
        $message = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
        $message_type = 'error';
    } else {
        
        // 2. ดึงรหัสผ่านปัจจุบัน (ที่เข้ารหัสแล้ว) จากฐานข้อมูล
        // **สำคัญ:** แก้ไข 'users', 'user_ID', 'password_hash' ให้ตรงกับชื่อตารางและคอลัมน์ของคุณ
        $sql = "SELECT password_hash FROM users WHERE user_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hashed_password_from_db = $user['password_hash'];

            // 3. ตรวจสอบว่ารหัสผ่านปัจจุบันที่กรอกมา ถูกต้องหรือไม่
            if (password_verify($current_password, $hashed_password_from_db)) {
                
                // 4. ถ้ารหัสผ่านถูกต้อง ให้เข้ารหัสรหัสผ่านใหม่และอัปเดตลงฐานข้อมูล
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update_sql = "UPDATE users SET password_hash = ? WHERE user_ID = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("si", $new_hashed_password, $user_id);
                
                if ($update_stmt->execute()) {
                    $message = 'เปลี่ยนรหัสผ่านสำเร็จแล้ว!';
                    $message_type = 'success';
                } else {
                    $message = 'เกิดข้อผิดพลาดในการอัปเดตฐานข้อมูล';
                    $message_type = 'error';
                }
                $update_stmt->close();

            } else {
                $message = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
                $message_type = 'error';
            }
        } else {
            $message = 'ไม่พบข้อมูลผู้ใช้ในระบบ';
            $message_type = 'error';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8">
    <title>เปลี่ยนรหัสผ่าน - ร้านโรตีลีลา</title>
    <link rel="stylesheet" href="../style/menu.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../style/navnsidebar.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f4f7f9;
        }
        .main-content {
            padding-top: 10px; /* เว้นที่ให้ navbar */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .change-password-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 450px;
        }
        .change-password-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #212529;
            font-size: 2rem;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #6c757d;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box; /* สำคัญมาก */
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        .btn-submit {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            background-color: #27ae60;
            color: white;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #0056b3;
        }
        /* Styles for messages */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .password-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #aaa;
        }
    </style>
</head>
<body>

<?php include('../layout/navbar.php'); ?>
<?php include('../layout/sidebar.php'); ?>

<div class="main-content">
    <div class="change-password-container">
        <h2>เปลี่ยนรหัสผ่าน</h2>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

<form action="../auth/change_password.php" method="post">
    <div class="form-group">
        <label for="current_password">รหัสผ่านปัจจุบัน</label>
        <div class="password-wrapper">
            <input type="password" id="current_password" name="current_password" required>
            <i class="fas fa-eye-slash toggle-password"></i>
        </div>
    </div>
    <div class="form-group">
        <label for="new_password">รหัสผ่านใหม่</label>
        <div class="password-wrapper">
            <input type="password" id="new_password" name="new_password" required>
            <i class="fas fa-eye-slash toggle-password"></i>
        </div>
    </div>
    <div class="form-group">
        <label for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
        <div class="password-wrapper">
            <input type="password" id="confirm_password" name="confirm_password" required>
            <i class="fas fa-eye-slash toggle-password"></i>
        </div>
    </div>
    <button type="submit" class="btn-submit">ยืนยัน</button>
</form>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
        document.body.classList.toggle('sidebar-open', sidebar.classList.contains('active'));
    }
}

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const content = document.querySelector('.main-content');
  sidebar.classList.toggle('active');
  if (content) content.classList.toggle('shifted');
}

document.addEventListener('DOMContentLoaded', function () {
  // Sidebar toggle
  const toggleBtn = document.getElementById('toggleSidebar');
  const sidebar = document.getElementById('sidebar');
  const content = document.querySelector('.main-content');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      sidebar.classList.toggle('active');
      if (content) content.classList.toggle('shifted');
    });
  }

  // ปิด sidebar เมื่อคลิกข้างนอก
//   document.addEventListener('click', function (e) {
//     if (sidebar && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
//       sidebar.classList.remove('active');
//       if (content) content.classList.remove('shifted');
//     }
//   });

  // ปิด sidebar เมื่อคลิกเมนู
  const menuLinks = document.querySelectorAll('#sidebar a');
  menuLinks.forEach(link => {
    link.addEventListener('click', () => {
      sidebar.classList.remove('active');
      if (content) content.classList.remove('shifted');
    });
  });

  // Logout confirmation
  const logoutBtn = document.querySelector('.logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function (e) {
      if (!confirm('คุณต้องการออกจากระบบใช่หรือไม่?')) {
        e.preventDefault();
      }
    });
  }

  // Profile dropdown (ถ้ามี)
  const profile = document.querySelector('.admin-profile');
  const dropdown = document.querySelector('.dropdown-menu');
  if (profile && dropdown) {
    profile.addEventListener('click', function (e) {
      e.stopPropagation();
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
    document.addEventListener('click', function () {
      dropdown.style.display = 'none';
    });
  }
});

 const togglePasswordIcons = document.querySelectorAll('.toggle-password');
  togglePasswordIcons.forEach(icon => {
    icon.addEventListener('click', function () {
      const passwordField = this.previousElementSibling;
      // Toggle the type
      const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordField.setAttribute('type', type);
      // Toggle the icon
      this.classList.toggle('fa-eye');
      this.classList.toggle('fa-eye-slash');
    });
  });
</script>

</body>
</html>