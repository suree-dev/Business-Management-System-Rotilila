<?php
include '../data/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ดึงข้อมูลพนักงาน
    if (!isset($_GET['user_ID'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing user_ID']);
        exit;
    }

    $user_ID = intval($_GET['user_ID']);
    $stmt = $conn->prepare("SELECT user_ID, username, full_name, phone, email, role FROM users WHERE user_ID = ?");
    $stmt->bind_param('i', $user_ID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Employee not found']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. ดึง role ของผู้ใช้ที่กำลังล็อกอิน
    $loggedInUserRole = strtolower($_SESSION['role'] ?? '');

    // 2. รับค่า user_ID ที่จะแก้ไข
    $user_ID = intval($_POST['user_ID']);

    // 3. ดึง role ปัจจุบันของผู้ใช้ที่กำลังจะถูกแก้ไขจากฐานข้อมูล
    $stmt_check = $conn->prepare("SELECT role FROM users WHERE user_ID = ?");
    $stmt_check->bind_param("i", $user_ID);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $userBeingEditedRole = '';
    if ($row_check = $result_check->fetch_assoc()) {
        $userBeingEditedRole = $row_check['role'];
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User to be edited not found']);
        exit;
    }
    $stmt_check->close();

    // --- ส่วนตรรกะที่แก้ไขใหม่ ---

    // 4. ตรวจสอบเงื่อนไข: ถ้า admin กำลังแก้ไข owner
    if ($loggedInUserRole === 'admin' && $userBeingEditedRole === 'owner') {
        // กรณีพิเศษ: อนุญาตให้อัปเดตเฉพาะ full_name และ phone
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);

        $sql = "UPDATE users SET full_name = ?, phone = ? WHERE user_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $full_name, $phone, $user_ID);

    } else {
        // กรณีปกติ: สำหรับการแก้ไขอื่นๆ ทั้งหมด
        // (ป้องกันไม่ให้ admin แก้ไข owner โดยสมบูรณ์แบบเดิม หากไม่ใช่เงื่อนไขข้างบน)
        if ($loggedInUserRole === 'admin' && $userBeingEditedRole === 'owner') {
             http_response_code(403);
             echo json_encode(['error' => 'Permission denied.']);
             exit;
        }

        // รับค่าทั้งหมดจากฟอร์ม
        $username   = mysqli_real_escape_string($conn, $_POST['username']);
        $full_name  = mysqli_real_escape_string($conn, $_POST['full_name']);
        $phone      = mysqli_real_escape_string($conn, $_POST['phone']);
        $email      = mysqli_real_escape_string($conn, $_POST['email']);
        $role       = mysqli_real_escape_string($conn, strtolower($_POST['role']));

        // ป้องกันการตั้งค่า role เป็น owner (ยังคงเก็บไว้เพื่อความปลอดภัย)
        if ($role === 'owner' && $userBeingEditedRole !== 'owner') {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot assign owner role.']);
            exit;
        }
        
        // เตรียมคำสั่ง SQL แบบเต็ม
        $sql = "UPDATE users SET username=?, full_name=?, phone=?, email=?, role=? WHERE user_ID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $username, $full_name, $phone, $email, $role, $user_ID);
    }

    // 5. Execute คำสั่ง SQL และส่งผลลัพธ์กลับ
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Data updated successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database update failed: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// Method Not Allowed
http_response_code(405);
echo 'Method Not Allowed';
?>
