<?php
// material/save_unit.php
include('../data/db_connect.php'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_id = isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0;
    $unit_name = isset($_POST['unit_name']) ? trim($_POST['unit_name']) : '';
    $base_unit = isset($_POST['base_unit']) ? trim($_POST['base_unit']) : '';
    $factor = isset($_POST['conversion_factor']) ? floatval($_POST['conversion_factor']) : 1.0;

    if (empty($unit_name) || empty($base_unit)) {
        echo 'failed: กรุณากรอกข้อมูลให้ครบถ้วน';
        exit;
    }

    try {
        if ($unit_id > 0) {
            // โหมดแก้ไข
            $stmt = $conn->prepare("UPDATE unit SET Unit_name = ?, base_unit = ?, conversion_factor = ? WHERE Unit_id = ?");
            $stmt->bind_param("ssdi", $unit_name, $base_unit, $factor, $unit_id);
        } else {
            // โหมดเพิ่มใหม่
            $stmt = $conn->prepare("INSERT INTO unit (Unit_name, base_unit, conversion_factor) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $unit_name, $base_unit, $factor);
        }

        if ($stmt->execute()) {
            echo 'success';
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo 'failed: ' . $e->getMessage();
    }
    $conn->close();
}
?>