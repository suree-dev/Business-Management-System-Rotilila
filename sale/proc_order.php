<?php
include('../data/db_connect.php');
// ตั้งค่า header ให้ตอบกลับเป็น JSON
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$orderData = json_decode($input, true);

if (!$orderData || !isset($orderData['items']) || empty($orderData['items'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid or empty order data']);
    exit();
}

// เริ่ม Transaction เพื่อความปลอดภัยของข้อมูล
mysqli_begin_transaction($conn);

try {
    $orderDate = date('Y-m-d H:i:s');
    $status = 'pending'; // หรือสถานะเริ่มต้นอื่นๆ
    $service_type = $orderData['service_type'] ?? 'dine-in';
    $table_num = ($service_type === 'dine-in' && isset($orderData['table_num'])) ? $orderData['table_num'] : null;
    $customer_name = ($service_type === 'takeaway' && isset($orderData['customer_name'])) ? $orderData['customer_name'] : null;
    
    $total_amount = 0;
    $processed_items = [];

    // ดึง ID ของเมนูทั้งหมดที่สั่ง เพื่อดึงข้อมูลราคาจาก DB ในครั้งเดียว
    $menu_ids = array_map(function($item) {
        return (int)$item['id'];
    }, $orderData['items']);
    
    if (!empty($menu_ids)) {
        $ids_string = implode(',', $menu_ids);
        $sql_prices = "SELECT menu_ID, menu_price FROM menu WHERE menu_ID IN ($ids_string)";
        $price_result = mysqli_query($conn, $sql_prices);
        $menu_prices = [];
        while ($row = mysqli_fetch_assoc($price_result)) {
            $menu_prices[$row['menu_ID']] = (float)$row['menu_price'];
        }

        // คำนวณยอดรวมจากราคาในฐานข้อมูล
        foreach ($orderData['items'] as $item) {
            $menuId = (int)$item['id'];
            $quantity = (int)$item['quantity'];

            // ใช้ราคาจากฐานข้อมูลเสมอ
            if (isset($menu_prices[$menuId]) && $quantity > 0) {
                $price_from_db = $menu_prices[$menuId];
                $total_amount += $quantity * $price_from_db;
                $processed_items[] = ['id' => $menuId, 'quantity' => $quantity];
            }
        }
    }
    
    // 1. Insert order1 (หัวออเดอร์)
    $stmt_order = $conn->prepare("INSERT INTO order1 (Order_date, status, service_type, table_num, customer_name, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_order->bind_param("sssssd", $orderDate, $status, $service_type, $table_num, $customer_name, $total_amount);
    if (!$stmt_order->execute()) {
        throw new Exception("Error inserting order: " . $stmt_order->error);
    }
    $orderId = $stmt_order->insert_id;
    $stmt_order->close();

    // 2. Insert order_items (แต่ละเมนู)
    if ($orderId > 0 && !empty($processed_items)) {
        $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, menu_id, quantity) VALUES (?, ?, ?)");
        foreach ($processed_items as $item) {
            $stmt_items->bind_param("iii", $orderId, $item['id'], $item['quantity']);
            if (!$stmt_items->execute()) {
                throw new Exception("Error inserting order item: " . $stmt_items->error);
            }
        }
        $stmt_items->close();
    } else {
        throw new Exception("No valid items to process.");
    }

    // ถ้าทุกอย่างสำเร็จ ให้ Commit Transaction
    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Order submitted successfully', 'order_id' => $orderId]);

} catch (Exception $e) {
    // หากมีข้อผิดพลาด ให้ Rollback Transaction
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>