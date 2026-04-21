<?php
session_start();
include('../data/db_connect.php');

// ห้ามทุกคนที่ไม่ใช่ owner เปลี่ยน role เป็น owner
if ($_SESSION['role'] !== 'owner') {
    die("❌ ไม่สามารถกำหนด owner ได้ คุณไม่มีสิทธิ์");
}

$userID = isset($_POST['user_ID']) ? intval($_POST['user_ID']) : 0;
$newRole = $_POST['role'] ?? '';

// ดึง role เดิมของ user ที่กำลังถูกแก้ไข
$oldRoleQuery = $conn->prepare("SELECT role FROM users WHERE user_ID = ?");
$oldRoleQuery->bind_param("i", $userID);
$oldRoleQuery->execute();
$oldRoleResult = $oldRoleQuery->get_result();
$oldRoleRow = $oldRoleResult->fetch_assoc();
$oldRole = $oldRoleRow['role'] ?? '';
$oldRoleQuery->close();

// ❌ ไม่ให้เปลี่ยนคนอื่นเป็น owner ไม่ว่ามี owner อยู่หรือไม่ (ยกเว้นเจ้าของเดิม)
if ($newRole === 'owner' && $oldRole !== 'owner') {
    die("❌ ไม่อนุญาตให้เปลี่ยนตำแหน่งเป็น owner");
}

// ✅ ถ้าผ่านเงื่อนไขทั้งหมด ให้ทำการอัปเดต
if ($userID > 0 && in_array($newRole, ['owner', 'manager', 'accountant', 'sales'])) {
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_ID = ?");
    $stmt->bind_param("si", $newRole, $userID);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../employee/manage_role.php");
exit;
?>
