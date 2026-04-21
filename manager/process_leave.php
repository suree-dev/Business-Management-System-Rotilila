<?php
session_start();
require '../data/db_connect.php';

// 1. ตรวจสอบการล็อกอินและสิทธิ์การเข้าถึง
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'owner') {
    die("คุณไม่มีสิทธิ์ดำเนินการ");
}

// 2. ตรวจสอบว่าข้อมูลถูกส่งมาแบบ POST และมีค่าครบถ้วน
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['leave_id']) && isset($_POST['action'])) {
    
    $leave_id = $_POST['leave_id'];
    $action = $_POST['action'];
    $manager_id = $_SESSION['user_id']; // ID ของผู้จัดการที่กำลังอนุมัติ

    // 3. กำหนดค่า status ใหม่ตาม action ที่ได้รับ
    $new_status = '';
    if ($action === 'approve') {
        $new_status = 'approved';
    } elseif ($action === 'reject') {
        $new_status = 'rejected';
    } else {
        // ถ้า action ไม่ถูกต้อง
        $_SESSION['message'] = "การดำเนินการไม่ถูกต้อง";
        $_SESSION['message_type'] = "error";
        header("Location: ../manager/manage_leave.php");
        exit();
    }

    // 4. เตรียมคำสั่ง SQL เพื่ออัปเดตข้อมูล
    $sql = "UPDATE leave_requests SET status = ?, approved_by = ? WHERE leave_id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    // ผูกค่า: "sii" -> s=string (status), i=integer (approved_by), i=integer (leave_id)
    $stmt->bind_param("sii", $new_status, $manager_id, $leave_id);

    // 5. สั่งให้ทำงานและส่งผลลัพธ์กลับไป
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['message'] = "ดำเนินการเรียบร้อยแล้ว";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "ไม่พบคำขอที่ต้องการ หรือสถานะเป็นปัจจุบันอยู่แล้ว";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    
    $stmt->close();
    $conn->close();

} else {
    // ถ้าไม่ได้เข้ามาด้วยวิธีที่ถูกต้อง
    $_SESSION['message'] = "ข้อมูลที่ส่งมาไม่ถูกต้อง";
    $_SESSION['message_type'] = "error";
}

// Redirect กลับไปหน้าจัดการใบลา
header("Location: ../manager/manager_emwork.php");
exit();
?>