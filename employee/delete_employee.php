<?php
// เริ่ม session เพื่อให้สามารถตรวจสอบสิทธิ์ในอนาคตได้ (ถ้าต้องการ)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../data/db_connect.php');

// เปลี่ยน header เป็น text/plain เพราะเราจะส่งแค่ข้อความกลับไป
header('Content-Type: text/plain'); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_ID'])) {
    http_response_code(400); // Bad Request
    echo "invalid request";
    exit;
}

$user_ID = intval($_POST['user_ID']);

// --- ส่วนที่เพิ่มเข้ามาเพื่อตรวจสอบ Role ---

// 1. ค้นหา Role ของ User ที่กำลังจะถูกลบ
$role_check_stmt = $conn->prepare("SELECT role FROM users WHERE user_ID = ?");
$role_check_stmt->bind_param("i", $user_ID);
$role_check_stmt->execute();
$result = $role_check_stmt->get_result();
$user_to_delete = $result->fetch_assoc();
$role_check_stmt->close();

// 2. ตรวจสอบว่า User ที่จะลบมีอยู่จริง และมี Role เป็น 'owner' หรือไม่
if ($user_to_delete && $user_to_delete['role'] === 'owner') {
    // 3. ถ้าเป็น owner ให้ส่งข้อผิดพลาดกลับไปและหยุดทำงาน
    http_response_code(403); // Forbidden
    echo "error: Cannot delete the owner account.";
    exit;
}

// --- จบส่วนที่เพิ่มเข้ามา ---

// ถ้าไม่ใช่ owner ให้ดำเนินการลบตามปกติ (ใช้ prepared statement เพื่อความปลอดภัย)
$delete_stmt = $conn->prepare("DELETE FROM users WHERE user_ID = ?");
$delete_stmt->bind_param("i", $user_ID);

if ($delete_stmt->execute()) {
    echo "success";
} else {
    http_response_code(500); // Internal Server Error
    echo "error: " . $delete_stmt->error;
}

$delete_stmt->close();
$conn->close();

?>