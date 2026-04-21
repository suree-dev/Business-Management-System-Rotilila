<?php
// ตั้งค่า header ให้ตอบกลับเป็น JSON
header('Content-Type: application/json');

// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
include('../data/db_connect.php');

// รับข้อมูลที่ส่งมาเป็น JSON
$data = json_decode(file_get_contents('php://input'), true);

// ตรวจสอบว่ามี order_id ส่งมาหรือไม่
if (!isset($data['order_id']) || !is_numeric($data['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง: ไม่พบ Order ID']);
    exit;
}

$order_id = intval($data['order_id']);

// --- เริ่มการทำงานกับฐานข้อมูล ---

// เริ่ม Transaction เพื่อความปลอดภัยของข้อมูล
mysqli_begin_transaction($conn);

try {
    // 1. ตรวจสอบสถานะปัจจุบันของออเดอร์
    $sql_check = "SELECT status FROM order1 WHERE order_id = ? FOR UPDATE";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, 'i', $order_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $order = mysqli_fetch_assoc($result_check);

    if (!$order) {
        throw new Exception('ไม่พบออเดอร์ที่ต้องการยกเลิก');
    }

    // 2. ตรวจสอบว่าสามารถยกเลิกได้หรือไม่ (อนุญาตเฉพาะสถานะ 'pending')
    if ($order['status'] !== 'pending') {
        throw new Exception('ไม่สามารถยกเลิกออเดอร์ได้ เนื่องจากพนักงานยืนยันรายการแล้ว');
    }

    // 3. อัปเดตสถานะเป็น 'cancelled'
    $sql_update = "UPDATE order1 SET status = 'cancelled' WHERE order_id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, 'i', $order_id);
    mysqli_stmt_execute($stmt_update);

    // 4. ตรวจสอบว่าการอัปเดตสำเร็จหรือไม่
    if (mysqli_stmt_affected_rows($stmt_update) > 0) {
        // ยืนยันการเปลี่ยนแปลง
        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'ยกเลิกออเดอร์สำเร็จ']);
    } else {
        // หากไม่มีแถวที่ถูกอัปเดต อาจเกิดจากปัญหาบางอย่าง
        throw new Exception('ไม่สามารถอัปเดตสถานะออเดอร์ได้');
    }

} catch (Exception $e) {
    // หากเกิดข้อผิดพลาด ให้ยกเลิกการเปลี่ยนแปลงทั้งหมด
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ปิดการเชื่อมต่อ
mysqli_close($conn);
?>