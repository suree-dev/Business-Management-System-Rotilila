<?php
// submit_customer_order.php (ฉบับแก้ไขสำหรับระบบ Group ID)
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../data/db_connect.php'); 

$response = ['success' => false, 'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'];

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$table_num = $data['table_number'] ?? '';
$items = $data['items'] ?? [];

if (empty($table_num) || empty($items)) {
    $response['message'] = 'ข้อมูลไม่ครบถ้วน';
    echo json_encode($response);
    exit();
}

$conn->begin_transaction();
try {
    // 1. ค้นหา Group ID ปัจจุบันของโต๊ะ
    $stmt_find_group = $conn->prepare("SELECT current_group_id FROM tables WHERE table_num = ? AND qr_status = 'active'");
    $stmt_find_group->bind_param('s', $table_num);
    $stmt_find_group->execute();
    $result = $stmt_find_group->get_result();
    $table_data = $result->fetch_assoc();
    $stmt_find_group->close();

    if (!$table_data) {
        throw new Exception('โต๊ะนี้ไม่ได้เปิดใช้งานอยู่ กรุณาติดต่อพนักงาน');
    }

    $current_group_id = $table_data['current_group_id'];
    $new_order_id = 0;
    
    // --- NEW LOGIC: สร้างออเดอร์ใหม่เสมอ ---
    
    // 2. คำนวณยอดรวมของออเดอร์ย่อยนี้
    $submission_total = 0;
    foreach ($items as $item) {
        $submission_total += ($item['price'] * $item['quantity']);
    }

    // 3. สร้างออเดอร์ใหม่ในตาราง order1
    // สถานะเริ่มต้นคือ 'pending' เสมอสำหรับออเดอร์ใหม่
$stmt_create_order = $conn->prepare("INSERT INTO order1 (table_num, total_amount, status) VALUES (?, ?, 'pending')");
$stmt_create_order->bind_param('sd', $table_num, $submission_total); // เพิ่ม 's' สำหรับ table_num
    $stmt_create_order->execute();
    $new_order_id = $conn->insert_id; // ดึง ID ของออเดอร์ที่เพิ่งสร้าง
    $stmt_create_order->close();

    if ($new_order_id === 0) {
        throw new Exception('ไม่สามารถสร้างออเดอร์ใหม่ได้');
    }

    // 4. จัดการ Group ID
    if (is_null($current_group_id)) {
        // ถ้าเป็นออเดอร์แรกของโต๊ะ ให้ใช้ ID ของตัวเองเป็น Group ID
        $group_id_to_set = $new_order_id;
        // และอัปเดต Group ID นี้ลงในตาราง tables
        $stmt_update_table = $conn->prepare("UPDATE tables SET current_group_id = ? WHERE table_num = ?");
        $stmt_update_table->bind_param('is', $group_id_to_set, $table_num);
        $stmt_update_table->execute();
        $stmt_update_table->close();
    } else {
        // ถ้าไม่ใช่ออเดอร์แรก ให้ใช้ Group ID ที่มีอยู่แล้ว
        $group_id_to_set = $current_group_id;
    }

    // 5. อัปเดต Group ID ให้ออเดอร์ที่เพิ่งสร้าง
    $stmt_set_group = $conn->prepare("UPDATE order1 SET group_id = ? WHERE order_id = ?");
    $stmt_set_group->bind_param('ii', $group_id_to_set, $new_order_id);
    $stmt_set_group->execute();
    $stmt_set_group->close();
    
    // 6. เพิ่มรายการอาหาร (order_items) สำหรับออเดอร์ใหม่นี้
    foreach ($items as $item) {
        $menu_id = $item['id'];
        $quantity = $item['quantity'];
        
        $stmt_add_item = $conn->prepare("INSERT INTO order_items (order_id, menu_id, quantity) VALUES (?, ?, ?)");
        $stmt_add_item->bind_param('iii', $new_order_id, $menu_id, $quantity);
        $stmt_add_item->execute();
        $stmt_add_item->close();
    }

    $conn->commit();
    // ส่งคืน ID ของออเดอร์ย่อยที่เพิ่งสร้างใหม่นี้กลับไปให้ลูกค้า
    $response = ['success' => true, 'message' => 'ส่งรายการอาหารสำเร็จ!', 'order_id' => $new_order_id];

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>