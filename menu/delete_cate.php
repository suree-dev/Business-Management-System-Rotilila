<?php
include('../data/db_connect.php');

// ตรวจสอบว่ามีการส่งข้อมูลผ่าน POST และมี id หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $sql = "DELETE FROM menu_categories WHERE id = $id";

  if (mysqli_query($conn, $sql)) {
    echo "success";
  } else {
    echo "error: " . mysqli_error($conn);
  }
} else {
  echo "invalid request";
}
?>
