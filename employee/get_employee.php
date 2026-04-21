<?php
// get_employees.php

header("Content-Type: application/json"); // ระบุว่าไฟล์นี้จะคืนค่าเป็น JSON
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

include('../data/db_connect.php'); // เชื่อมต่อฐานข้อมูล

// --- การตั้งค่า Pagination ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// --- การค้นหา (Search) ---
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = "WHERE role NOT IN ('admin')"; // กรอง role ที่ไม่ต้องการออก

if (!empty($search)) {
    // ใช้ prepared statement เพื่อความปลอดภัย
    $search_term = "%" . $search . "%";
    $where_clause .= " AND (username LIKE ? OR full_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
}

// --- การดึงข้อมูลทั้งหมดเพื่อนับจำนวนหน้า ---
$count_sql = "SELECT COUNT(user_ID) as total FROM users " . $where_clause;
$stmt_count = mysqli_prepare($conn, $count_sql);

if (!empty($search)) {
    mysqli_stmt_bind_param($stmt_count, "ssss", $search_term, $search_term, $search_term, $search_term);
}

mysqli_stmt_execute($stmt_count);
$count_result = mysqli_stmt_get_result($stmt_count);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);


// --- การดึงข้อมูลสำหรับหน้าที่ต้องการ ---
$sql = "SELECT user_ID, username, full_name, role, phone, email FROM users " . $where_clause . " ORDER BY user_ID LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);

if (!empty($search)) {
    mysqli_stmt_bind_param($stmt, "ssssii", $search_term, $search_term, $search_term, $search_term, $limit, $offset);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$employees = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $employees[] = $row;
    }
}

// --- ส่งข้อมูลกลับเป็น JSON ---
echo json_encode([
    'pagination' => [
        'total_records' => (int)$total_rows,
        'total_pages' => (int)$total_pages,
        'current_page' => $page,
        'limit' => $limit
    ],
    'data' => $employees
]);

mysqli_close($conn);
?>