<?php
include('../data/db_connect.php');

$cate_id = isset($_POST['cate_id']) ? intval($_POST['cate_id']) : 0;
if ($cate_id <= 0) {
    echo 'invalid id';
    exit;
}
// เช็คว่ามีวัตถุดิบผูกกับหมวดหมู่นี้หรือไม่
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM mate_cate WHERE cate_id = $cate_id");
$row = mysqli_fetch_assoc($res);
if ($row['cnt'] > 0) {
    echo 'ไม่สามารถลบประเภทที่มีวัตถุดิบผูกอยู่ได้';
    exit;
}
// ลบประเภท
$sql = "DELETE FROM categories WHERE cate_id = $cate_id";
if (mysqli_query($conn, $sql)) {
    echo 'success';
} else {
    echo 'error: ' . mysqli_error($conn);
} 