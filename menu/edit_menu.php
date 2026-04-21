<?php
// menu/edit_menu.php
include('../data/db_connect.php');

// ตรวจสอบว่าเป็น POST request หรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ส่วนของการบันทึกข้อมูลที่แก้ไข
    $menu_ID = $_POST['menu_ID'] ?? 0;
    $menu_name = $_POST['menu_name'] ?? '';
    $menu_price = $_POST['menu_price'] ?? 0;
    $food_categories = $_POST['food_category'] ?? [];
    $new_image_path = null; // กำหนดค่าเริ่มต้นเป็น null

    // เริ่ม Transaction
    mysqli_begin_transaction($conn);

    // 1. ตรวจสอบและจัดการไฟล์ภาพอัพโหลดใหม่
    if (isset($_FILES['menu_image']) && $_FILES['menu_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../picture/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = basename($_FILES['menu_image']['name']);
        $targetFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['menu_image']['tmp_name'], $targetFilePath)) {
            // หากสำเร็จ ให้เก็บ path ไว้เพื่ออัปเดตฐานข้อมูล
            $new_image_path = 'picture/' . $fileName;
        } else {
            // หากไม่สำเร็จ ให้ยกเลิกการทำงานทั้งหมดและแจ้งข้อผิดพลาด
            mysqli_rollback($conn);
            echo 'failed: ไม่สามารถอัปโหลดรูปภาพได้';
            exit;
        }
    }

    try {
        // 2. อัปเดตตาราง menu
        // ตรวจสอบว่ามีรูปใหม่หรือไม่ เพื่อกำหนดว่าจะอัปเดตฟิลด์ menu_image ด้วยหรือไม่
        if ($new_image_path) {
            $sql = "UPDATE menu SET menu_name = ?, menu_price = ?, menu_image = ? WHERE menu_ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sdsi', $menu_name, $menu_price, $new_image_path, $menu_ID);
        } else {
            $sql = "UPDATE menu SET menu_name = ?, menu_price = ? WHERE menu_ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sdi', $menu_name, $menu_price, $menu_ID);
        }
        $stmt->execute();

        // 3. ลบรายการเดิมใน menu_categories
        $del = $conn->prepare("DELETE FROM menu_categories WHERE menu_ID = ?");
        $del->bind_param("i", $menu_ID);
        $del->execute();

        // 4. เพิ่มรายการใหม่ใน menu_categories
        if (!empty($food_categories)) {
            $insert = $conn->prepare("INSERT INTO menu_categories (menu_ID, cate_id) VALUES (?, ?)");
            foreach ($food_categories as $cateID) {
                $cateID_int = intval($cateID);
                $insert->bind_param("ii", $menu_ID, $cateID_int);
                $insert->execute();
            }
        }

        // ยืนยันการทำรายการทั้งหมด
        mysqli_commit($conn);
        echo 'success';

    } catch (Exception $e) {
        // หากมีข้อผิดพลาด ให้ย้อนกลับการทำรายการทั้งหมด
        mysqli_rollback($conn);
        echo 'failed: ' . $e->getMessage();
    }
    exit;

} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['menu_ID'])) {
    // ส่วนของการดึงข้อมูลเพื่อไปแสดงในฟอร์มแก้ไข (โค้ดเดิม)
    $menuID = $_GET['menu_ID'];
    $sql = "SELECT menu_ID, menu_name, menu_price, menu_image FROM menu WHERE menu_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $menuID);
    $stmt->execute();
    $result = $stmt->get_result();
    $menu = $result->fetch_assoc();
    $stmt->close();

    if ($menu) {
        // ดึงประเภทของเมนูนี้
        $sql_cats = "SELECT cate_id FROM menu_categories WHERE menu_ID = ?";
        $stmt_cats = $conn->prepare($sql_cats);
        $stmt_cats->bind_param("i", $menuID);
        $stmt_cats->execute();
        $result_cats = $stmt_cats->get_result();
        $cate_ids = [];
        while ($row = $result_cats->fetch_assoc()) {
            $cate_ids[] = $row['cate_id'];
        }
        $menu['cate_ids'] = $cate_ids;
        
        header('Content-Type: application/json');
        echo json_encode($menu);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'ไม่พบเมนู']);
    }
    exit;
}

$conn->close();
?>