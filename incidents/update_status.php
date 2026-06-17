<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed."]);
    exit();
}

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

$id = trim($_POST['id'] ?? '');
$status = trim($_POST['status'] ?? 'Submitted');

if (empty($id)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Incident ID required."]);
    exit();
}

$query = "UPDATE incidents SET status = ? WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database preparation failed."]);
    exit();
}

$stmt->bind_param("ss", $status, $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Incident status updated to $status."]);
    } else {
        // Might be already set to that status
        echo json_encode(["status" => "success", "message" => "No changes made or record not found."]);
    }
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database execution failed."]);
}

$stmt->close();
$conn->close();
?>
