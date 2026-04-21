<?php
// edit_material.php (ฉบับแก้ไข bind_param สมบูรณ์)

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Bangkok');

include('../data/db_connect.php'); 
include('../material/stock_manager.php'); 

// ตรวจสอบว่าเป็น POST request เท่านั้น
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'failed: Invalid request method.';
    exit;
}

// รับค่าจากฟอร์ม
$materialID = intval($_POST['material_id'] ?? 0);
$materialName = trim($_POST['material_name'] ?? '');
$materialQuantity = isset($_POST['material_quantity']) ? floatval($_POST['material_quantity']) : 0;
$unitID = isset($_POST['Unit_id']) ? intval($_POST['Unit_id']) : 0;
$min_stock = !empty($_POST['min_stock']) ? floatval($_POST['min_stock']) : null;
$max_stock = !empty($_POST['max_stock']) ? floatval($_POST['max_stock']) : null;
$supplier = !empty($_POST['supplier']) ? trim($_POST['supplier']) : null;
$categories = isset($_POST['mate_cate']) && is_array($_POST['mate_cate']) ? $_POST['mate_cate'] : [];

// แปลงค่าวันที่
$raw_expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
$expiry_date = null; 
if ($raw_expiry_date) {
    try {
        $date_obj = new DateTime($raw_expiry_date);
        $expiry_date = $date_obj->format('Y-m-d');
    } catch (Exception $e) {
        $expiry_date = null;
    }
}

// ตรวจสอบข้อมูลพื้นฐาน
if ($materialID === 0 || empty($materialName) || $unitID === 0) {
    http_response_code(400);
    echo "failed: ข้อมูลไม่ครบถ้วน (ID, ชื่อ หรือ หน่วย)";
    exit;
}

$conn->begin_transaction();
try {
    $conversion = convert_to_base_unit($conn, $materialQuantity, $unitID);
    $base_quantity = $conversion['base_quantity'];
    $base_unit = $conversion['base_unit'];

    $sql_update = "UPDATE material 
                   SET material_name = ?, material_quantity = ?, Unit_id = ?, 
                       base_quantity = ?, base_unit = ?, expiry_date = ?, 
                       min_stock = ?, max_stock = ?, supplier = ? 
                   WHERE material_id = ?";
    
    $stmt_update = $conn->prepare($sql_update);
    if (!$stmt_update) throw new Exception("Prepare statement failed: " . $conn->error);
    
    // *** START: จุดที่แก้ไข ***
    $stmt_update->bind_param(
        'sdidssddsi', // แก้ไข d ตัวที่ 6 เป็น s
        $materialName, 
        $materialQuantity, 
        $unitID,
        $base_quantity, 
        $base_unit,
        $expiry_date, 
        $min_stock, 
        $max_stock, 
        $supplier, 
        $materialID
    );
    // *** END: จุดที่แก้ไข ***
    
    if (!$stmt_update->execute()) {
         throw new Exception("Error updating material: " . $stmt_update->error);
    }
    $stmt_update->close();

    // ลบและเพิ่ม category ใหม่
    $stmt_del = $conn->prepare("DELETE FROM mate_cate WHERE material_id = ?");
    $stmt_del->bind_param("i", $materialID);
    $stmt_del->execute();
    $stmt_del->close();

    if (!empty($categories)) {
        $stmt_insert = $conn->prepare("INSERT INTO mate_cate (material_id, cate_id) VALUES (?, ?)");
        foreach ($categories as $cateID) {
            $cateID = intval($cateID);
            $stmt_insert->bind_param("ii", $materialID, $cateID);
            $stmt_insert->execute();
        }
        $stmt_insert->close();
    }

    $conn->commit();
    echo 'success';

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo 'failed: ' . $e->getMessage();
}

$conn->close();
?>