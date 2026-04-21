<?php
require '../data/db_connect.php';
header('Content-Type: application/json');

$username = $_GET['username'] ?? '';
$response = ['exists' => false];

if ($username) {
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $response['exists'] = true;
    }
}

echo json_encode($response);
?>