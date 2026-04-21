<?php
// update_order_status.php

include('../data/db_connect.php');
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $received_amount = isset($_POST['received_amount']) ? floatval($_POST['received_amount']) : null;
    $change_amount = isset($_POST['change_amount']) ? floatval($_POST['change_amount']) : null;
    // ---- END: รับค่าใหม่ ----

    if ($action === 'mark_as_paid' && $order_id > 0 && !empty($payment_method)) {
        
        // ---- START: แก้ไข SQL UPDATE ----
        $sql = "UPDATE order1 SET 
                    status = 'paid', 
                    payment_method = ?, 
                    received_amount = ?, 
                    change_amount = ? 
                WHERE order_id = ?";
        // ---- END: แก้ไข SQL UPDATE ----
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sddi', $payment_method, $received_amount, $change_amount, $order_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response = ['success' => true, 'message' => 'Payment status updated successfully.'];
                } else {
                    $response = ['success' => false, 'message' => 'Order not found or already paid.'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Execute failed: ' . $stmt->error];
            }
            $stmt->close();
        } else {
            $response = ['success' => false, 'message' => 'Prepare failed: ' . $conn->error];
        }
    } else {
        $response = ['success' => false, 'message' => 'Missing required parameters.'];
    }
}

echo json_encode($response);
$conn->close();
?>