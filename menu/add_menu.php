<?php
include('../data/db_connect.php'); // ต้องแน่ใจว่า path ถูกต้อง

// ตรวจสอบว่าเป็นการส่งข้อมูลแบบ POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // รับข้อมูลจากฟอร์ม
    $menu_name = $_POST['menu_name'] ?? '';
    $menu_price = $_POST['menu_price'] ?? '';
    $food_categories = $_POST['food_category'] ?? [];

    if (empty(trim($menu_name))) {
        echo 'Error: ชื่อเมนูห้ามว่าง';
        exit; // หยุดการทำงาน
    }
    if ($menu_price === '') { // ใช้ === '' เพื่ออนุญาตให้ราคาเป็น 0 ได้
        echo 'Error: ราคาห้ามว่าง';
        exit; // หยุดการทำงาน
    }
    if (empty($food_categories)) {
        echo 'Error: กรุณาเลือกประเภทอย่างน้อย 1 รายการ';
        exit; // หยุดการทำงาน
    }
    // --- ส่วนที่ 1: การจัดการรูปภาพ ---
    $menu_image_path = ''; // กำหนดค่าเริ่มต้นเป็นค่าว่าง
    if (isset($_FILES['menu_image']) && $_FILES['menu_image']['error'] == 0) {
        $target_dir = "../uploads/menu/"; // โฟลเดอร์สำหรับเก็บรูปเมนู (ต้องมีอยู่จริง)
        
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $imageFileType = strtolower(pathinfo($_FILES["menu_image"]["name"], PATHINFO_EXTENSION));
        // สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกัน เพื่อป้องกันการเขียนทับ
        $new_filename = uniqid('menu_', true) . '.' . $imageFileType;
        $target_file = $target_dir . $new_filename;

        // ตรวจสอบว่าเป็นไฟล์รูปภาพจริงหรือไม่
        $check = getimagesize($_FILES["menu_image"]["tmp_name"]);
        if ($check !== false) {
            // พยายามย้ายไฟล์ไปยังโฟลเดอร์ปลายทาง
            if (move_uploaded_file($_FILES["menu_image"]["tmp_name"], $target_file)) {
                // เก็บเฉพาะ path ที่สัมพันธ์กับ root ของโปรเจกต์ ไม่ใช่ path เต็มของ server
                $menu_image_path = "uploads/menu/" . $new_filename;
            } else {
                echo 'Error: ไม่สามารถอัปโหลดไฟล์รูปภาพได้';
                exit;
            }
        } else {
            echo 'Error: ไฟล์ที่อัปโหลดไม่ใช่ไฟล์รูปภาพ';
            exit;
        }
    }

    // --- ส่วนที่ 2: บันทึกข้อมูลเมนูหลักลงตาราง `menu` ---
    $conn->begin_transaction(); // เริ่ม Transaction เพื่อความปลอดภัยของข้อมูล

    try {
        // ใช้ Prepared Statements เพื่อป้องกัน SQL Injection
        $stmt = $conn->prepare("INSERT INTO menu (menu_name, menu_price, menu_image, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("sds", $menu_name, $menu_price, $menu_image_path);
        
        if (!$stmt->execute()) {
            throw new Exception("Error on inserting menu: " . $stmt->error);
        }

        // ดึง ID ของเมนูที่เพิ่งเพิ่มเข้าไปล่าสุด
        $last_menu_id = $conn->insert_id;
        $stmt->close();

        // --- ส่วนที่ 3: บันทึกประเภทของเมนูลงตาราง `menu_categories` ---
        if (!empty($food_categories) && $last_menu_id > 0) {
            // เตรียม statement สำหรับการเพิ่มข้อมูลประเภท
            $stmt_cat = $conn->prepare("INSERT INTO menu_categories (menu_ID, cate_id) VALUES (?, ?)");
            
            foreach ($food_categories as $cate_id) {
                $stmt_cat->bind_param("ii", $last_menu_id, $cate_id);
                if (!$stmt_cat->execute()) {
                    throw new Exception("Error on inserting menu category: " . $stmt_cat->error);
                }
            }
            $stmt_cat->close();
        }

        // ถ้าทุกอย่างสำเร็จ ให้ commit transaction
        $conn->commit();
        echo 'success';

    } catch (Exception $e) {
        // หากมีข้อผิดพลาดใดๆ เกิดขึ้น ให้ rollback transaction
        $conn->rollback();
        echo 'Error: ' . $e->getMessage();
    }

    $conn->close();

} else {
    echo 'Invalid request method.';
}
?>