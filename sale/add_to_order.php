<?php
// sale/add_to_order.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

include('../data/db_connect.php'); 
include('../material/stock_manager.php'); // ต้องใช้สำหรับตัดสต็อก

// รับข้อมูล JSON ที่ส่งมาจาก JavaScript
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$order_id = isset($data['order_id']) ? intval($data['order_id']) : 0;
$new_items = isset($data['items']) ? $data['items'] : [];

if ($order_id === 0 || empty($new_items)) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

$conn->begin_transaction();
try {
    // 1. ตรวจสอบสถานะออเดอร์เดิม และดึงยอดรวมเดิม
    $stmt_check = $conn->prepare("SELECT status, total_amount FROM order1 WHERE order_id = ? FOR UPDATE");
    $stmt_check->bind_param('i', $order_id);
    $stmt_check->execute();
    $original_order = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$original_order) throw new Exception("ไม่พบออเดอร์เดิม");
    if ($original_order['status'] === 'paid') throw new Exception("ออเดอร์นี้ชำระเงินแล้ว ไม่สามารถเพิ่มรายการได้");

    $new_items_total = 0;

    // 2. วนลูปเพิ่มรายการใหม่และตัดสต็อก
    $stmt_insert = $conn->prepare("INSERT INTO order_items (order_id, menu_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    foreach ($new_items as $item) {
        $menu_id = intval($item['id']);
        $quantity = intval($item['quantity']);
        $price = floatval($item['price']);

        // --- ตัดสต็อกสำหรับรายการที่สั่งเพิ่ม ---
        $recipe_res = $conn->query("SELECT Recipes_id FROM menu WHERE menu_ID = $menu_id");
        $recipe_data = $recipe_res->fetch_assoc();
        if ($recipe_data && !empty($recipe_data['Recipes_id'])) {
            $recipe_id = $recipe_data['Recipes_id'];
            $recipe_items_res = $conn->query("SELECT material_id, ingr_quantity, Unit_id FROM ingredient WHERE Recipes_id = $recipe_id");
            while ($material = $recipe_items_res->fetch_assoc()) {
                $deduct_result = deduct_stock($conn, $material['material_id'], $material['ingr_quantity'] * $quantity, $material['Unit_id']);
                if (!$deduct_result['success']) {
                    throw new Exception("วัตถุดิบสำหรับเมนู '" . htmlspecialchars($item['name']) . "' ไม่เพียงพอ");
                }
            }
        }
        // --- จบการตัดสต็อก ---

        // เพิ่มรายการลงใน order_items
        $stmt_insert->bind_param("iiid", $order_id, $menu_id, $quantity, $price);
        $stmt_insert->execute();

        // คำนวณยอดรวมของรายการที่สั่งเพิ่ม
        $new_items_total += ($price * $quantity);
    }
    $stmt_insert->close();

    // 3. อัปเดตยอดรวมทั้งหมด และเปลี่ยนสถานะกลับไปเป็น 'processing'
    $new_total_amount = $original_order['total_amount'] + $new_items_total;
    
    $stmt_update = $conn->prepare("UPDATE order1 SET total_amount = ?, status = 'processing' WHERE order_id = ?");
    $stmt_update->bind_param('di', $new_total_amount, $order_id);
    $stmt_update->execute();
    $stmt_update->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'เพิ่มรายการสำเร็จ']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>