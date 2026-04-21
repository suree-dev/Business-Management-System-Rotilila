<?php
// stock_manager.php (เวอร์ชัน Dynamic)

/**
 * [แก้ไข] ดึงข้อมูลการแปลงหน่วยจากฐานข้อมูลโดยตรง
 * @param mysqli $conn Connection object
 * @param int $unit_id ไอดีของหน่วย
 * @return array ข้อมูลประกอบด้วย 'base_unit' และ 'factor'
 */
function get_conversion_info($conn, $unit_id) {
    $stmt = $conn->prepare("SELECT base_unit, conversion_factor FROM unit WHERE Unit_id = ?");
    $stmt->bind_param("i", $unit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unit_data = $result->fetch_assoc();
    $stmt->close();

    if ($unit_data) {
        return [
            'base_unit' => $unit_data['base_unit'],
            'factor'    => (float)$unit_data['conversion_factor']
        ];
    }
    // กรณีหาไม่เจอ ให้คืนค่าพื้นฐานที่ไม่ทำให้ระบบพัง
    return ['base_unit' => 'หน่วย', 'factor' => 1.0];
}


/**
 * แปลงค่าจากหน่วยที่รับเข้ามาให้เป็นหน่วยย่อยที่สุด (Base Unit)
 * @param mysqli $conn
 * @param float $quantity
 * @param int $unit_id
 * @return array
 */
function convert_to_base_unit($conn, $quantity, $unit_id) {
    $unit_info = get_conversion_info($conn, $unit_id);
    $base_quantity = $quantity * $unit_info['factor'];
    return [
        'base_quantity' => $base_quantity,
        'base_unit'     => $unit_info['base_unit']
    ];
}

/**
 * ฟังก์ชันหลักสำหรับตัดสต็อก (ปรับปรุงให้ส่ง $conn เข้าไป)
 */
function deduct_stock($conn, $material_id, $quantity_to_deduct, $unit_id_of_deduction) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT base_quantity, base_unit, Unit_id FROM material WHERE material_id = ? FOR UPDATE");
        $stmt->bind_param("i", $material_id);
        $stmt->execute();
        $material = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$material) throw new Exception("ไม่พบวัตถุดิบ ID: {$material_id}");

        // [แก้ไข] ส่ง $conn เข้าฟังก์ชัน
        $deduction_info = convert_to_base_unit($conn, $quantity_to_deduct, $unit_id_of_deduction);
        $deduction_base_quantity = $deduction_info['base_quantity'];

        if ($material['base_unit'] !== $deduction_info['base_unit']) {
            throw new Exception("หน่วยไม่ตรงกัน: ไม่สามารถตัด '{$deduction_info['base_unit']}' จาก '{$material['base_unit']}' ได้");
        }
        if ($material['base_quantity'] < $deduction_base_quantity) {
            throw new Exception("สต็อกไม่เพียงพอ");
        }

        $new_base_quantity = $material['base_quantity'] - $deduction_base_quantity;

        // [แก้ไข] ส่ง $conn เข้าฟังก์ชัน
        $main_unit_info = get_conversion_info($conn, $material['Unit_id']);
        $new_material_quantity = ($main_unit_info['factor'] > 0) ? ($new_base_quantity / $main_unit_info['factor']) : 0;

        $stmt_update = $conn->prepare("UPDATE material SET base_quantity = ?, material_quantity = ? WHERE material_id = ?");
        $stmt_update->bind_param("ddi", $new_base_quantity, $new_material_quantity, $material_id);
        if (!$stmt_update->execute()) throw new Exception("อัปเดตสต็อกไม่สำเร็จ");
        $stmt_update->close();

        $conn->commit();
        return ['success' => true, 'message' => 'ตัดสต็อกเรียบร้อย'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * ฟังก์ชันสำหรับเพิ่มสต็อก (ปรับปรุงให้ส่ง $conn เข้าไป)
 */
function add_stock($conn, $material_id, $quantity_to_add, $unit_id_of_addition) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT base_quantity, base_unit, Unit_id FROM material WHERE material_id = ? FOR UPDATE");
        $stmt->bind_param("i", $material_id);
        $stmt->execute();
        $material = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$material) throw new Exception("ไม่พบวัตถุดิบ ID: {$material_id}");

        // [แก้ไข] ส่ง $conn เข้าฟังก์ชัน
        $addition_info = convert_to_base_unit($conn, $quantity_to_add, $unit_id_of_addition);
        $addition_base_quantity = $addition_info['base_quantity'];

        if ($material['base_unit'] !== $addition_info['base_unit']) {
            throw new Exception("หน่วยไม่ตรงกัน: ไม่สามารถเพิ่ม '{$addition_info['base_unit']}' เข้า '{$material['base_unit']}' ได้");
        }

        $new_base_quantity = $material['base_quantity'] + $addition_base_quantity;
        
        // [แก้ไข] ส่ง $conn เข้าฟังก์ชัน
        $main_unit_info = get_conversion_info($conn, $material['Unit_id']);
        $new_material_quantity = ($main_unit_info['factor'] > 0) ? ($new_base_quantity / $main_unit_info['factor']) : 0;

        $stmt_update = $conn->prepare("UPDATE material SET base_quantity = ?, material_quantity = ? WHERE material_id = ?");
        $stmt_update->bind_param("ddi", $new_base_quantity, $new_material_quantity, $material_id);
        if (!$stmt_update->execute()) throw new Exception("อัปเดตสต็อกไม่สำเร็จ");
        $stmt_update->close();

        $conn->commit();
        return ['success' => true, 'message' => 'เพิ่มสต็อกเรียบร้อย'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

?>