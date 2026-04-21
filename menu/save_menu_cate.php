<?php
// menu/save_menu_cate.php

include('../data/db_connect.php'); // ตรวจสอบ path ให้ถูกต้อง

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // รับค่า cate_id (สำหรับการแก้ไข) และ cate_name
    $cate_id = $_POST['cate_id'] ?? null;
    $cate_name = trim($_POST['cate_name'] ?? '');

    // ตรวจสอบว่าชื่อประเภทไม่ว่างเปล่า
    if (empty($cate_name)) {
        echo 'Error: ชื่อประเภทห้ามว่าง';
        exit;
    }

    // ตรวจสอบว่าเป็นการ "แก้ไข" หรือ "เพิ่มใหม่"
    if (!empty($cate_id) && is_numeric($cate_id)) {
        // --- ส่วนของการแก้ไข (UPDATE) ---
        $stmt = $conn->prepare("UPDATE categories SET cate_name = ? WHERE cate_id = ?");
        $stmt->bind_param("si", $cate_name, $cate_id);

    } else {
        // --- ส่วนของการเพิ่มใหม่ (INSERT) ---
        // ไม่ต้องส่งค่า color หรือ icon อีกต่อไป
        $stmt = $conn->prepare("INSERT INTO categories (cate_name) VALUES (?)");
        $stmt->bind_param("s", $cate_name);
    }

    // ทำการ execute query และส่งผลลัพธ์กลับไป
    if ($stmt->execute()) {
        echo 'success';
    } else {
        // ให้แสดง error จากฐานข้อมูลเพื่อการดีบักที่ง่ายขึ้น
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();
    $conn->close();

} else {
    echo 'Invalid request.';
}
?>