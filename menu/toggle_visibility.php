<?php
// ตรวจสอบให้แน่ใจว่าไม่มีช่องว่างหรืออักขระใดๆ ก่อน <?php

include('../data/db_connect.php'); // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าได้รับค่าที่จำเป็นมาหรือไม่
if (isset($_POST['menu_ID']) && isset($_POST['visible'])) {

    // ป้องกัน SQL Injection
    $menu_id = (int)$_POST['menu_ID'];
    $visible = (int)$_POST['visible'];

    // ตรวจสอบว่าค่า visible เป็น 0 หรือ 1 เท่านั้น
    if ($visible !== 0 && $visible !== 1) {
        echo "Invalid visibility value.";
        exit; // หยุดการทำงานทันที
    }

    // เตรียมคำสั่ง SQL
    $sql = "UPDATE menu SET visible = ? WHERE menu_ID = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $visible, $menu_id);
        
        // สั่งให้คำสั่งทำงาน
        if (mysqli_stmt_execute($stmt)) {
            // ตรวจสอบว่ามีการอัปเดตแถวจริงหรือไม่
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                echo "success";
            } else {
                echo "No menu found with that ID.";
            }
        } else {
            // กรณี execute ล้มเหลว
            echo "Execute failed: " . mysqli_stmt_error($stmt);
        }
        
        // ปิด statement
        mysqli_stmt_close($stmt);

    } else {
        // กรณี prepare ล้มเหลว
        echo "Prepare failed: " . mysqli_error($conn);
    }
    
    // ปิดการเชื่อมต่อ
    mysqli_close($conn);

} else {
    echo "Required data is missing.";
}

exit; // **สำคัญมาก:** ใช้ exit เพื่อจบการทำงานของสคริปต์ทันที และป้องกันไม่ให้มี output อื่นส่งออกไป

?>