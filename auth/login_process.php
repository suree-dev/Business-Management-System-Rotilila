<?php
session_start();
require '../data/db_connect.php';

// ตั้งค่าโซนเวลาให้เป็นของประเทศไทย
date_default_timezone_set('Asia/Bangkok');

$username = $_POST['username'];
$password = $_POST['password'];

// --- START: แก้ไขจุดที่ 1 (เพิ่ม force_password_reset) force_password_reset---
// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$sql = "SELECT user_id, username, password_hash, role FROM users WHERE username = ?";
// --- END: แก้ไขจุดที่ 1 ---

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // ตรวจสอบรหัสผ่านที่ผู้ใช้ป้อนเทียบกับ hash ที่เก็บไว้
    if (password_verify($password, $user['password_hash'])) {
        // เข้าสู่ระบบสำเร็จ
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // --- START: แก้ไขจุดที่ 2 (เพิ่มเงื่อนไขตรวจสอบ) ---
        // ตรวจสอบว่าต้องบังคับเปลี่ยนรหัสผ่านหรือไม่
        if ($user['force_password_reset'] == 1) {
            // ถ้าใช่ (ค่าเป็น 1) ให้ส่งไปหน้าบังคับเปลี่ยนรหัสทันที
            header("Location: ../auth/force_reset_pw.php");
            exit(); // จบการทำงานทันที
        }
        // ถ้าไม่ (ค่าเป็น 0 หรือ NULL) ก็จะทำโค้ดด้านล่างต่อไปตามปกติ
        // --- END: แก้ไขจุดที่ 2 ---

        // --- ส่วนที่เพิ่มเข้ามาสำหรับบันทึกเวลา ---
        $user_id = $user['user_id'];
        $status = 'present'; // กำหนดสถานะเบื้องต้นเป็น 'present' (มาทำงาน)

        // เตรียมคำสั่ง SQL เพื่อเพิ่มข้อมูลลงในตาราง attendance
        $attendance_sql = "INSERT INTO attendance (user_id, check_in_time, status) VALUES (?, NOW(), ?)";
        
        $stmt_attendance = $conn->prepare($attendance_sql);
        // bind_param "is" หมายถึง: i = integer (สำหรับ user_id), s = string (สำหรับ status)
        $stmt_attendance->bind_param("is", $user_id, $status);
        
        // ทำการ execute query
        $stmt_attendance->execute();
        
        // ปิด statement ของ attendance
        $stmt_attendance->close();
        // --- จบส่วนที่เพิ่มเข้ามา ---

        // หลังจากบันทึกเวลาแล้ว ก็ redirect ไปยังหน้าที่เหมาะสมตาม role
        if ($user['role'] === 'owner') {
            header("Location: ../dashboard/dashboard.php");
            exit(); // ควรมี exit() หลัง header เสมอ
        } else if ($user['role'] === 'manager') {
            header("Location: ../manager/dashboard_mn.php");
            exit();
        } else if ($user['role'] === 'accountant') {    
            header("Location: accountant_dashboard.php");
            exit();
        } else if ($user['role'] === 'sales') {
            header("Location: ../sale/sale.php");
            exit();
        } else {
            // กรณีมี role อื่นๆ หรือ role ที่ไม่ตรงกับเงื่อนไขข้างต้น
            header("Location: ../employee/employees.php");
            exit();
        } 
    } else {
        // ใช้ session ในการส่งข้อความผิดพลาดกลับไปจะดีกว่าการ echo เฉยๆ
        $_SESSION['error'] = "รหัสผ่านไม่ถูกต้อง";
        header("Location: login.php");
        exit();
    }
} else {
    $_SESSION['error'] = "ไม่พบชื่อผู้ใช้นี้";
    header("Location: login.php");
    exit();
}

$stmt->close();
$conn->close();
?>