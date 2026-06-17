<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

// Enforce JWT check
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

$current_user_id = $decoded['user_id'];
$after = isset($_GET['after']) ? $_GET['after'] : null;

// Always exclude the current user from SOS checks to avoid self-notifying
$exclude_user = $current_user_id;

if (!$after) {
    // Fetch SOS from the last 30 minutes for dashboard display
    $query = "SELECT i.*, u.full_name FROM incidents i JOIN users u ON i.user_id = u.id WHERE i.category = 'Kecemasan' AND i.created_at >= NOW() - INTERVAL 30 MINUTE AND i.user_id != ? ORDER BY i.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $exclude_user);
} else {
    $query = "SELECT i.*, u.full_name FROM incidents i JOIN users u ON i.user_id = u.id WHERE i.category = 'Kecemasan' AND i.created_at > ?";
    $query .= " AND i.user_id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $after, $exclude_user);
}

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

$sos_list = [];
while ($row = $result->fetch_assoc()) {
    $sos_list[] = $row;
}

echo json_encode([
    "status" => "success",
    "data" => $sos_list
]);

$stmt->close();
$conn->close();
?>
