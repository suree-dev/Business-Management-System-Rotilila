<?php
session_start();
include('../data/db_connect.php');

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? 0;
    // ... (โค้ดส่วนอื่น ๆ เหมือนเดิม) ...

    if ($order_id > 0 && !empty($payment_method)) {
        $conn->begin_transaction();
        try {
            // ดึงข้อมูลโต๊ะของออเดอร์นี้ (ถ้ามี) มาเก็บไว้ก่อน
            $stmt_table = $conn->prepare("SELECT table_num, service_type FROM order1 WHERE order_id = ?");
            $stmt_table->bind_param('i', $order_id);
            $stmt_table->execute();
            $order_info = $stmt_table->get_result()->fetch_assoc();

            // 1. อัปเดตสถานะออเดอร์เป็น 'paid'
            // ... (โค้ดเหมือนเดิม) ...

            if ($stmt_order->affected_rows > 0) {
                
                // --- MODIFIED: START ---
                // 2. (ฟังก์ชันใหม่) เคลียร์โต๊ะในตาราง tables
                if ($order_info && $order_info['service_type'] === 'dine-in' && !empty($order_info['table_num'])) {
                    $table_num = $order_info['table_num'];
                    // ตั้งค่า qr_status เป็น inactive และ current_order_id เป็น NULL
                    $stmt_clear_table = $conn->prepare("UPDATE tables SET qr_status = 'inactive', current_order_id = NULL WHERE table_num = ?");
                    $stmt_clear_table->bind_param('s', $table_num);
                    $stmt_clear_table->execute();
                }
                // --- MODIFIED: END ---

                $conn->commit();
                $response = ['success' => true, 'message' => 'ชำระเงินสำเร็จ'];
            } else {
                throw new Exception('ไม่สามารถอัปเดตสถานะออเดอร์ได้ อาจมีการชำระเงินไปแล้ว');
            }
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = $e->getMessage();
        }
    } else {
        $response['message'] = 'ข้อมูลไม่ครบถ้วน';
    }
}

echo json_encode($response);
?>