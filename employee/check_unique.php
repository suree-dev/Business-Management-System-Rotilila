<?php
header('Content-Type: application/json');

// $conn = new mysqli("rotilila2", "root", "", "rotilila");
$conn = new mysqli("106765033.student.yru.ac.th", "S106765033_db", "1959900814134", "S106765033_db");
if ($conn->connect_error) {
    echo json_encode(["exists" => false]);
    exit;
}

$type = $_GET['type'] ?? '';
$value = $_GET['value'] ?? '';
$excludeID = isset($_GET['exclude_id']) ? intval($_GET['exclude_id']) : 0;

$column = '';
$table = 'users';

if ($type === 'generate_username') {
    $base = $conn->real_escape_string($value);
    $sql = "SELECT username FROM users WHERE username LIKE '{$base}%'";
    $result = $conn->query($sql);

    $usernames = [];
    while ($row = $result->fetch_assoc()) {
        $usernames[] = $row['username'];
    }

    $username = $base;
    $i = 1;
    while (in_array($username, $usernames)) {
        $i++;
        $username = $base . $i;
    }

    echo json_encode(['username' => $username]);
    exit;
}

switch ($type) {
    case 'username': $column = 'username'; break;
    case 'full_name': $column = 'full_name'; break;
    case 'phone': $column = 'phone'; break;
    case 'email': $column = 'email'; break;
    default: echo json_encode(['exists' => false]); exit;
}

if ($excludeID > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM $table WHERE $column = ? AND user_ID != ?");
    $stmt->bind_param("si", $value, $excludeID);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
    $stmt->bind_param("s", $value);
}

$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();
$conn->close();


echo json_encode(["exists" => $count > 0]);
?>