<?php
include('../data/db_connect.php'); // ต้องมีไฟล์เชื่อมต่อฐานข้อมูลของคุณ

// ตรวจสอบว่ามีข้อมูลส่งมาและเป็น array
if (isset($_POST['cate_name']) && is_array($_POST['cate_name'])) {
    
    $category_names = $_POST['cate_name'];
    
    // เตรียมคำสั่ง SQL เพื่อเพิ่มข้อมูล
    // การใช้ prepared statement จะปลอดภัยกว่าการต่อ string โดยตรง
    $stmt = mysqli_prepare($conn, "INSERT INTO categories (cate_name) VALUES (?)");

    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }

    $all_success = true;

    // วนลูปเพื่อเพิ่มข้อมูลทีละรายการ
    foreach ($category_names as $name) {
        // ทำความสะอาดข้อมูลและตรวจสอบว่าไม่เป็นค่าว่าง
        $trimmed_name = trim($name);
        if (!empty($trimmed_name)) {
            // ผูกค่าตัวแปรเข้ากับ statement
            mysqli_stmt_bind_param($stmt, "s", $trimmed_name);
            
            // Execute a query
            if (!mysqli_stmt_execute($stmt)) {
                // หากมีข้อผิดพลาดในการเพิ่มรายการใดรายการหนึ่ง ให้หยุดทำงาน
                $all_success = false;
                break; 
            }
        }
    }
    
    // ปิด statement
    mysqli_stmt_close($stmt);

    // ปิดการเชื่อมต่อ
    mysqli_close($conn);

    if ($all_success) {
        echo 'success';
    } else {
        echo 'Error: มีข้อผิดพลาดในการบันทึกข้อมูลบางรายการ';
    }

} else {
    echo 'Error: ไม่มีข้อมูลถูกส่งมาหรือข้อมูลมีรูปแบบไม่ถูกต้อง';
}
?>