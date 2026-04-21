<?php
include('../data/db_connect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ส่ง next index (นับจำนวนพนักงานแล้ว +1)
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
    $row = mysqli_fetch_assoc($result);
    echo json_encode(['nextIndex' => $row['count'] + 1]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';
    $password = $_POST['password_hash'] ?? '';

    if (!$username || !$full_name || !$password) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, full_name, phone, email, role, password_hash)
                            VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Prepare failed', 'details' => $conn->error]);
        exit;
    }

    $stmt->bind_param("ssssss", $username, $full_name, $phone, $email, $role, $hashed_password);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Execute failed', 'details' => $stmt->error]);
    }

    $stmt->close();
    exit;
}
?>
