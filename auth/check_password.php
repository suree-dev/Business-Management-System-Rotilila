<?php
require '../data/db_connect.php';
header('Content-Type: application/json');

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$response = ['valid' => false];

if ($username && $password) {
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            $response['valid'] = true;
        }
    }
    $stmt->close();
}
$conn->close();

echo json_encode($response);
?>
