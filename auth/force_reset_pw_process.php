<?php
session_start();

// ถ้ายังไม่ได้ล็อกอิน หรือไม่มี session ให้เด้งกลับ
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); // แก้ไข path เป็น ../login.php เพื่อความถูกต้อง
    exit();
}

// --- การเชื่อมต่อฐานข้อมูล (DB Connection) ---
// แนะนำให้ใช้ไฟล์ connect กลางเพื่อความง่ายในการจัดการ
require '../data/db_connect.php'; 
// --- จบส่วนการเชื่อมต่อฐานข้อมูล ---


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];

    // --- Server-side Validation (สำคัญมาก) ---
    if ($new_password !== $confirm_password) {
        die("Error: Passwords do not match.");
    }
    if (strlen($new_password) < 8) {
        die("Error: Password must be at least 8 characters long.");
    }
    if (!preg_match('/[A-Z]/', $new_password)) {
        die("Error: Password must contain at least one uppercase letter.");
    }
    // --- จบส่วน Validation ---

    // Hash รหัสผ่านใหม่
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // อัปเดตรหัสผ่านใหม่ และตั้งค่า force_password_reset กลับเป็น 0
    // *** หมายเหตุ: ใช้คอลัมน์ user_ID ตามโค้ดที่คุณให้มา ***
    $stmt = $conn->prepare("UPDATE users SET password_hash = ?, force_password_reset = 0 WHERE user_ID = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        // --- START: ส่วนที่แก้ไขและเพิ่มเข้ามา ---

        // 1. ดึงข้อมูล role ของผู้ใช้จากฐานข้อมูล
        $role_stmt = $conn->prepare("SELECT role FROM users WHERE user_ID = ?");
        $role_stmt->bind_param("i", $user_id);
        $role_stmt->execute();
        $result = $role_stmt->get_result();
        $user = $result->fetch_assoc();
        $user_role = $user['role'];
        $role_stmt->close();

        // 2. อัปเดต session role เพื่อให้หน้าต่อไปทำงานได้ถูกต้อง
        $_SESSION['role'] = $user_role;
        
        // 3. Redirect ไปยังหน้าที่เหมาะสมตาม role (นำ Logic มาจาก login_process.php)
        // *** หมายเหตุ: path เหล่านี้อ้างอิงจากโค้ด login_process เดิมที่คุณให้มา ***
        if ($user_role === 'owner') {
            header("Location: ../menu/own.editmenu.php");
            exit();
        } else if ($user_role === 'manager') {
            header("Location: ../manager/dashboard_mn.php");
            exit();
        } else if ($user_role === 'accountant') {    
            // ตรวจสอบ path ของ accountant ให้ถูกต้อง อาจจะต้องเป็น ../accountant/accountant_dashboard.php
            header("Location: accountant_dashboard.php");
            exit();
        } else if ($user_role === 'sales') {
            header("Location: ../sale/sale.php");
            exit();
        } else {
            // กรณีมี role อื่นๆ หรือ role ที่ไม่ตรงกับเงื่อนไขข้างต้น
            header("Location: ../employee/employees.php");
            exit();
        }
        // --- END: ส่วนที่แก้ไขและเพิ่มเข้ามา ---
        
    } else {
        echo "เกิดข้อผิดพลาดในการอัปเดตรหัสผ่าน";
    }

    $stmt->close();
    $conn->close();
}
?>