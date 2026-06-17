<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

$token = get_bearer_token();
if (!$token) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Missing authentication token"]);
    exit();
}
$decoded = verify_jwt($token);
if (!$decoded) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
    exit();
}

$user_id = $decoded['user_id'];

$query = "SELECT id, full_name, ic_number, phone_number, email, district_zone, role, account_status, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    http_response_code(500);
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
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "User not found."]);
}

$stmt->close();
$conn->close();
?>
