<?php
header('Content-Type: application/json; charset=utf-8');
include('../data/db_connect.php');

// สร้าง query ที่ซับซ้อนขึ้นเพื่อดึงทั้ง cate_ids และ cate_names
$sql = "
    SELECT 
        m.menu_ID, 
        m.menu_name, 
        m.menu_price, 
        m.menu_image, 
        m.visible,
        -- ใช้ GROUP_CONCAT เพื่อรวม ID ของ category ทั้งหมดที่เมนูนั้นมี
        -- ผลลัพธ์จะเป็น string เช่น '1,3,4'
        GROUP_CONCAT(DISTINCT mc.cate_id ORDER BY mc.cate_id) as cate_ids,
        -- ใช้ GROUP_CONCAT เพื่อรวมชื่อของ category ทั้งหมดที่เมนูนั้นมี
        -- ผลลัพธ์จะเป็น string เช่น 'เมนูโรตี,เมนูแนะนำ,เมนูยอดฮิต'
        GROUP_CONCAT(DISTINCT c.cate_name ORDER BY mc.cate_id SEPARATOR ', ') as cate_names
    FROM 
        menu m
    -- LEFT JOIN เพื่อให้เมนูที่ไม่มี category เลยยังคงแสดงผล
    LEFT JOIN 
        menu_categories mc ON m.menu_ID = mc.menu_ID
    LEFT JOIN
        categories c ON mc.cate_id = c.cate_id
    GROUP BY 
        m.menu_ID, m.menu_name, m.menu_price, m.menu_image, m.visible
    ORDER BY
        m.menu_ID ASC
";

$result = mysqli_query($conn, $sql);
$menus = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // แปลง string '1,3,4' ให้กลายเป็น array [1, 3, 4]
        // และจัดการกับกรณีที่เมนูไม่มี category (NULL)
        if ($row['cate_ids']) {
            // ใช้ array_map เพื่อแปลง string แต่ละตัวใน array ให้เป็น integer
            $row['cate_ids'] = array_map('intval', explode(',', $row['cate_ids']));
        } else {
            $row['cate_ids'] = []; // ถ้าไม่มี ให้เป็น array ว่าง
        }
        
        // แปลง string 'เมนูโรตี,เมนูแนะนำ' ให้กลายเป็น array ['เมนูโรตี', 'เมนูแนะนำ']
        if ($row['cate_names']) {
            $row['cate_names'] = explode(', ', $row['cate_names']);
        } else {
            $row['cate_names'] = []; // ถ้าไม่มี ให้เป็น array ว่าง
        }

        $menus[] = $row;
    }
}

echo json_encode($menus, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

mysqli_close($conn);
?>