<?php
include('../data/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['material_id'])) {
$material_id = intval($_POST['material_id']);

// [แก้ไข] ใช้ Prepared Statements เพื่อความปลอดภัย
$conn->begin_transaction();
try {
// ลบจากตารางเชื่อมก่อน
$stmt1 = $conn->prepare("DELETE FROM mate_cate WHERE material_id = ?");
$stmt1->bind_param("i", $material_id);
$stmt1->execute();
$stmt1->close();

// ลบจากตารางหลัก
$stmt2 = $conn->prepare("DELETE FROM material WHERE material_id = ?");
$stmt2->bind_param("i", $material_id);
$stmt2->execute();
$stmt2->close();

$conn->commit();
echo "success";
} catch (Exception $e) {
$conn->rollback();
echo "error: " . $e->getMessage();
}

} else {
echo "invalid request";
}
?>