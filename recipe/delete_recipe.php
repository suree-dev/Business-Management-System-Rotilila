<?php
include('../data/db_connect.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error); }
$conn->set_charset("utf8");

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM Ingredient WHERE Recipes_id = $delete_id");
        $conn->query("DELETE FROM Recipes WHERE Recipes_id = $delete_id");
        $conn->commit();
        echo "<script>alert('ลบสูตรอาหารสำเร็จ!'); window.location.href='../recipe/own.editrecipe.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('เกิดข้อผิดพลาดในการลบ: " . $e->getMessage() . "'); window.history.back();</script>";
    }
}

$conn->close();
?>
