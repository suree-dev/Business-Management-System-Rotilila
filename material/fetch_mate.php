<?php

header('Content-Type: application/json; charset=utf-8');
include('../data/db_connect.php'); 

try {
    // 1. ดึงข้อมูลหลักของวัตถุดิบทั้งหมด (เหมือนโค้ดเดิมของคุณ)
    $sql = "SELECT 
                m.material_id, m.material_name,
                m.material_quantity, m.Unit_id,
                m.base_quantity, m.base_unit,
                m.expiry_date, m.min_stock, m.max_stock,
                m.supplier, u.Unit_name
            FROM 
                material m
            LEFT JOIN 
                unit u ON m.Unit_id = u.Unit_id
            ORDER BY 
                m.material_name ASC";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        throw new Exception("SQL Error: " . mysqli_error($conn));
    }

    $materials = [];
    while ($row = mysqli_fetch_assoc($result)) {
        
        // 2. สำหรับวัตถุดิบแต่ละชิ้น ให้ไปค้นหา Category ของมัน (เหมือนโค้ดเดิมของคุณ)
        $cate_sql = "SELECT cate_id FROM mate_cate WHERE material_id = " . (int)$row['material_id'];
        $cate_result = mysqli_query($conn, $cate_sql);
        
        $cate_ids = []; // สร้าง array ว่างรอไว้
        if ($cate_result) {
            while ($cate_row = mysqli_fetch_assoc($cate_result)) {
                // เพิ่ม ID ของ category ที่เจอเข้าไปใน array
                $cate_ids[] = (int)$cate_row['cate_id'];
            }
        }
        
        // 3. เพิ่ม array ของ category ID เข้าไปในข้อมูลของวัตถุดิบแถวนั้นๆ
        $row['cate_ids'] = $cate_ids;

        // 4. เพิ่มข้อมูลวัตถุดิบที่สมบูรณ์แล้วเข้าไปในผลลัพธ์สุดท้าย
        $materials[] = $row;
    }

    // ส่งข้อมูลทั้งหมดกลับไปในรูปแบบ JSON ที่ถูกต้อง
    echo json_encode(['data' => $materials], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

mysqli_close($conn);
?>