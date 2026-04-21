<?php
include('../data/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['id'])) {
        echo json_encode(['error' => 'Missing id']);
        exit;
    }

    $id = intval($_GET['id']);
    $sql = "SELECT * FROM menu_categories WHERE id = $id";
    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Record not found']);
    }
    exit;
}

// ส่วน POST แก้ไขข้อมูลประเภทอาหารและชื่อเมนู
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจากฟอร์ม
    $id = $_POST['id'];
    $menuName = $_POST['menu_name'] ?? '';
    $cateName = $_POST['cate_name'] ?? '';

    // เตรียมคำสั่งอัปเดต
    $sql = "UPDATE menu_categories SET menu_name = ?, cate_name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $menuName, $cateName, $id);
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        echo 'success';
    } else {
        echo 'failed';
    }
    exit;
}
?>
