<?php
include('../data/db_connect.php');
include('../material/stock_manager.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Bangkok');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['material_name'])) {
    
    $conn->begin_transaction();

    try {
        $stmt_material = $conn->prepare(
            "INSERT INTO material (material_name, material_quantity, Unit_id, base_quantity, base_unit, expiry_date, min_stock, max_stock, supplier, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt_material) throw new Exception("Prepare 'material' failed: " . $conn->error);

        // [แก้ไข] ทำให้ statement ง่ายขึ้น ไม่ต้องใช้ name
        $stmt_category = $conn->prepare("INSERT INTO mate_cate (material_id, cate_id) VALUES (?, ?)");
        if (!$stmt_category) throw new Exception("Prepare 'mate_cate' failed: " . $conn->error);

        // [ลบ] ไม่จำเป็นต้องดึงชื่อ category อีกต่อไป
        // $stmt_get_cate_name = $conn->prepare("SELECT cate_name FROM categories WHERE cate_id = ?");

        $current_time = date('Y-m-d H:i:s');

        foreach ($_POST['material_name'] as $key => $name) {
            // ... (ส่วนของการบันทึกข้อมูล material เหมือนเดิม) ...
            $quantity = !empty($_POST['material_quantity'][$key]) ? floatval($_POST['material_quantity'][$key]) : 0.0;
            $unit_id = $_POST['Unit_id'][$key] ?? null;
            if (is_null($unit_id)) throw new Exception("กรุณาเลือกหน่วยสำหรับ: " . htmlspecialchars($name));

            $conversion = convert_to_base_unit($conn, $quantity, $unit_id);
            $base_quantity = $conversion['base_quantity'];
            $base_unit = $conversion['base_unit'];
            
            $expiry = !empty($_POST['expiry_date'][$key]) ? $_POST['expiry_date'][$key] : null;
            $min_stock = !empty($_POST['min_stock'][$key]) ? floatval($_POST['min_stock'][$key]) : null;
            $max_stock = !empty($_POST['max_stock'][$key]) ? floatval($_POST['max_stock'][$key]) : null;
            $supplier = !empty($_POST['supplier'][$key]) ? $_POST['supplier'][$key] : null;

            $stmt_material->bind_param("sdidssddsss", $name, $quantity, $unit_id, $base_quantity, $base_unit, $expiry, $min_stock, $max_stock, $supplier, $current_time, $current_time);
            
            // เพิ่มการตรวจสอบการ execute
            if (!$stmt_material->execute()) {
                // ถ้า execute ล้มเหลว ให้โยน Exception พร้อม error จากฐานข้อมูล
                throw new Exception("SQL Execute Error (material): " . $stmt_material->error);
            }
            
            $material_id = $conn->insert_id;
            if ($material_id == 0) throw new Exception("Failed to insert material: " . htmlspecialchars($name));

            // [แก้ไข] ปรับปรุงลูปการบันทึก category ทั้งหมด
            if (isset($_POST['mate_category'][$key]) && is_array($_POST['mate_category'][$key])) {
                foreach ($_POST['mate_category'][$key] as $cate_id) {
                    $cate_id = intval($cate_id); // แปลงเป็น integer เพื่อความปลอดภัย
                    
                    // บันทึกลงตารางเชื่อม โดยใช้แค่ ID
                    $stmt_category->bind_param("ii", $material_id, $cate_id);
                    $stmt_category->execute();
                }
            }
        }

        $conn->commit();
        echo 'success';

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo $e->getMessage();
    }

    if (isset($stmt_material)) $stmt_material->close();
    if (isset($stmt_category)) $stmt_category->close();

} else {
    http_response_code(400);
    echo 'No data submitted or invalid request method.';
}

$conn->close();
?>