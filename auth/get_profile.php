<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

if (!isset($_GET['user_id'])) {
    echo json_encode(["status" => "error", "message" => "user_id is required."]);
    exit();
}

$user_id = $_GET['user_id'];

$query = "SELECT id, full_name, ic_number, phone_number, email, district_zone, role, account_status, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit();
}

$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    echo json_encode([
        "status" => "success",
        "data" => $user
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "User not found."]);
}

$stmt->close();
$conn->close();
?>
