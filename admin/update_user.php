<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

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

if ($decoded['role'] !== 'ketua_krt') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Access denied. Only Ketua KRT can perform this action."]);
    exit();
}

$action = trim($_POST['action'] ?? '');
$target_user_id = trim($_POST['user_id'] ?? '');

if (empty($action) || empty($target_user_id)) {
    echo json_encode(["status" => "error", "message" => "Missing action or user ID."]);
    exit();
}

// Verify target user exists and is not ketua_krt
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("s", $target_user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows !== 1) {
    echo json_encode(["status" => "error", "message" => "User not found."]);
    $stmt->close();
    exit();
}
$target_user = $res->fetch_assoc();
$stmt->close();

if ($target_user['role'] === 'ketua_krt') {
    echo json_encode(["status" => "error", "message" => "Cannot modify Ketua KRT accounts."]);
    exit();
}

if ($action === 'update_status') {
    $status = trim($_POST['account_status'] ?? '');
    if (!in_array($status, ['pending', 'active', 'inactive'])) {
        echo json_encode(["status" => "error", "message" => "Invalid account status."]);
        exit();
    }

    $update_stmt = $conn->prepare("UPDATE users SET account_status = ? WHERE id = ?");
    $update_stmt->bind_param("ss", $status, $target_user_id);
    if ($update_stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Account status updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update account status."]);
    }
    $update_stmt->close();

} else if ($action === 'promote_role') {
    $role = trim($_POST['role'] ?? '');
    if (!in_array($role, ['peronda', 'ketua_peronda'])) {
        echo json_encode(["status" => "error", "message" => "Invalid role selected."]);
        exit();
    }

    $update_stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $update_stmt->bind_param("ss", $role, $target_user_id);
    if ($update_stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "User role updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update user role."]);
    }
    $update_stmt->close();

} else {
    echo json_encode(["status" => "error", "message" => "Invalid action."]);
}

$conn->close();
?>
