<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

try {
    // 1. Count active patrollers
    $patroller_res = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('peronda', 'ketua_peronda')");
    $total_patrollers = $patroller_res->fetch_assoc()['count'];

    // 2. Count active shifts (where clock_out_time is null)
    $active_res = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE clock_out_time IS NULL");
    $active_shifts = $active_res->fetch_assoc()['count'];

    // 3. Count weekly incidents (last 7 days)
    $incident_res = $conn->query("SELECT COUNT(*) as count FROM incidents WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $weekly_incidents = $incident_res->fetch_assoc()['count'];

    echo json_encode([
        "status" => "success",
        "data" => [
            "total_patrollers" => (int)$total_patrollers,
            "active_shifts" => (int)$active_shifts,
            "weekly_incidents" => (int)$weekly_incidents
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
