<?php
include('../data/db_connect.php');
header('Content-Type: application/json');

$fields = [];
$res = mysqli_query($conn, "SHOW COLUMNS FROM categories");
while ($row = mysqli_fetch_assoc($res)) {
    $fields[] = $row['Field'];
}

$sql = "SELECT c.cate_id, c.cate_name"
    . ", COUNT(mc.material_id) as count
        FROM categories c
        LEFT JOIN mate_cate mc ON c.cate_id = mc.cate_id
        GROUP BY c.cate_id
        ORDER BY c.cate_id ASC";
$result = mysqli_query($conn, $sql);
$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}
echo json_encode($categories); 