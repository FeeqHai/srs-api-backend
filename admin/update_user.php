<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';
require_once '../config/access_control.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

$actor = get_current_actor($conn);
require_role($actor, ['ketua_krt', 'super_admin']);

$action = trim($_POST['action'] ?? '');
$target_user_id = trim($_POST['user_id'] ?? '');

if (empty($action) || empty($target_user_id)) {
    echo json_encode(["status" => "error", "message" => "Missing action or user ID."]);
    exit();
}

// Authorisation comes from the current database state, not the JWT's old role.
$stmt = $conn->prepare("SELECT role, district_id FROM users WHERE id = ?");
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

if ($actor['role'] === 'ketua_krt') {
    if (in_array($target_user['role'], ['ketua_krt', 'super_admin'], true)) {
        api_error(403, 'Ketua KRT cannot modify Ketua KRT or Super Admin accounts.');
    }

    if (!actor_can_access_district($actor, $target_user['district_id'])) {
        api_error(403, 'Ketua KRT can only modify users in their own district.');
    }
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
