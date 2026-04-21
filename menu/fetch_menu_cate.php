<?php
header('Content-Type: application/json; charset=utf-8');
include('../data/db_connect.php');

// กรองหมวดวัตถุดิบ (cate_id 7-10)
$exclude_ids = [7,8,9,10];
$exclude_ids_str = implode(',', $exclude_ids);

$sql = "
    SELECT 
        c.cate_id, 
        c.cate_name, 
        COUNT(mc.menu_ID) AS menu_count
    FROM categories c
    LEFT JOIN menu_categories mc ON c.cate_id = mc.cate_id
    WHERE c.cate_id NOT IN ($exclude_ids_str)
    GROUP BY c.cate_id, c.cate_name
    ORDER BY c.cate_id ASC
";

$result = mysqli_query($conn, $sql);
$categories = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['menu_count'] = (int)$row['menu_count']; // แปลงเป็น integer
        $categories[] = $row;
    }
}

echo json_encode($categories, JSON_UNESCAPED_UNICODE);
mysqli_close($conn);
?>
