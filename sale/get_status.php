<?php
// get_status.php (ฉบับแก้ไข ปลอดภัยและเสถียร)

// สำหรับการดีบัก แสดงข้อผิดพลาดทั้งหมด
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

include('../data/db_connect.php');

// 1. ตรวจสอบการเชื่อมต่อฐานข้อมูล
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// 2. ตรวจสอบว่ามีการส่ง order_id มาหรือไม่
if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request: order_id is missing']);
    exit();
}

// 3. รับค่าและแปลงเป็นตัวเลข
$order_id = intval($_GET['order_id']);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order id format']);
    exit();
}

try {
    // 4. ใช้ Prepared Statement เพื่อความปลอดภัยและถูกต้องสูงสุด
    $stmt = $conn->prepare("SELECT status FROM order1 WHERE order_id = ?");
    
    // ตรวจสอบว่า prepare สำเร็จหรือไม่
    if ($stmt === false) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    
    // 5. ผูกค่าตัวแปร (Bind parameter)
    $stmt->bind_param('i', $order_id); // 'i' หมายถึงตัวแปรประเภท integer

    // 6. สั่งให้คำสั่งทำงาน (Execute)
    $stmt->execute();

    // 7. รับผลลัพธ์
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // เจอข้อมูล
        echo json_encode(['success' => true, 'status' => $row['status']]);
    } else {
        // ไม่เจอข้อมูล
        echo json_encode(['success' => false, 'message' => 'Order not found for ID: ' . $order_id]);
    }

    // 8. ปิด statement
    $stmt->close();

} catch (Exception $e) {
    // จัดการข้อผิดพลาดที่อาจเกิดขึ้นระหว่างการทำงาน
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// 9. ปิดการเชื่อมต่อ
$conn->close();

?>