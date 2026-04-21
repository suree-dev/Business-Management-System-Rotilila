<?php
include('../data/db_connect.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // รับค่าจากฟอร์มและป้องกัน SQL Injection
  $menu_name = mysqli_real_escape_string($conn, $_POST['menu_id']);
  $cate_name = mysqli_real_escape_string($conn, $_POST['cate_name']);

  // ค้นหา cate_id จาก cate_name
  $stmtCate = $conn->prepare("SELECT cate_id FROM categories WHERE cate_name = ?");
  $stmtCate->bind_param("s", $cate_name);
  $stmtCate->execute();
  $result = $stmtCate->get_result();

  if ($row = $result->fetch_assoc()) {
    $cate_id = $row['cate_id'];

    // เตรียมคำสั่ง SQL สำหรับ insert ข้อมูล
    $stmt = $conn->prepare("INSERT INTO menu_categories (menu_id, cate_name, cate_id) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $menu_id, $cate_name, $cate_id);  // "ssi" → s = string, i = integer

    if ($stmt->execute()) {
      echo "success";
    } else {
      echo "❌ เกิดข้อผิดพลาด: " . $stmt->error;
    }

    $stmt->close();
  } else {
    echo "❌ ไม่พบหมวดหมู่ '$cate_name' ในตาราง categories";
  }

  $stmtCate->close();
}

$conn->close();
?>
