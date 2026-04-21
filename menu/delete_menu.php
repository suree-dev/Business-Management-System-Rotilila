<?php
include('../data/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu_ID'])) {
    $menu_ID = intval($_POST['menu_ID']);

    // เริ่ม transaction เพื่อให้มั่นใจว่าลบครบทั้งสองตาราง
    mysqli_begin_transaction($conn);

    try {
        // ลบจากตาราง menu_categories ก่อน
        $sql1 = "DELETE FROM menu_categories WHERE menu_ID = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("i", $menu_ID);
        $stmt1->execute();

        // ลบจากตาราง menu
        $sql2 = "DELETE FROM menu WHERE menu_ID = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $menu_ID);
        $stmt2->execute();

        // ถ้าไม่มีปัญหา ให้ commit
        mysqli_commit($conn);
        echo "success";
    } catch (Exception $e) {
        // ถ้ามีข้อผิดพลาด ยกเลิกการเปลี่ยนแปลงทั้งหมด
        mysqli_rollback($conn);
        echo "error: " . $e->getMessage();
    }

    // ปิด statement
    $stmt1->close();
    $stmt2->close();
} else {
    echo "invalid request";
}

$conn->close();
?>
