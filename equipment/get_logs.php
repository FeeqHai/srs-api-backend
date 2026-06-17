<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
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

if ($decoded['role'] !== 'ketua_krt') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Access denied. Only Ketua KRT can perform this action."]);
    exit();
}

try {
    $query = "SELECT 
                el.attendance_id, 
                el.borrowed_at, 
                el.returned_at, 
                u.full_name as patroller_name,
                u.phone_number,
                GROUP_CONCAT(ei.item_name ORDER BY ei.item_name SEPARATOR ', ') as items
              FROM equipment_logs el
              JOIN equipment_inventory ei ON el.equipment_id = ei.id
              JOIN attendance a ON el.attendance_id = a.id
              JOIN users u ON a.user_id = u.id
              GROUP BY el.attendance_id, el.borrowed_at, el.returned_at, u.full_name, u.phone_number
              ORDER BY el.borrowed_at DESC
              LIMIT 150";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $stmt->close();

    echo json_encode(["status" => "success", "data" => $logs]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

$conn->close();
?>
