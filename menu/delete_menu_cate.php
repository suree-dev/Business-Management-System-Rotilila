<?php
include('../data/db_connect.php');

$cate_id = $_POST['cate_id'] ?? null;

if (!$cate_id) {
    die('ไม่พบ ID ประเภท');
}

// เริ่ม Transaction เพื่อความปลอดภัย
$conn->begin_transaction();

try {
    // 1. ลบความสัมพันธ์ในตาราง menu_categories ก่อน
    $stmt1 = $conn->prepare("DELETE FROM menu_categories WHERE cate_id = ?");
    $stmt1->bind_param("i", $cate_id);
    $stmt1->execute();
    $stmt1->close();

    // 2. ลบ category หลัก
    $stmt2 = $conn->prepare("DELETE FROM categories WHERE cate_id = ?");
    $stmt2->bind_param("i", $cate_id);
    $stmt2->execute();
    $stmt2->close();

    // ถ้าสำเร็จทั้งหมด
    $conn->commit();
    echo 'success';

} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    echo 'เกิดข้อผิดพลาดในการลบ: ' . $exception->getMessage();
}

$conn->close();
?>