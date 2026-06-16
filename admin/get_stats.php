<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
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
        "success" => true,
        "data" => [
            "total_patrollers" => (int)$total_patrollers,
            "active_shifts" => (int)$active_shifts,
            "weekly_incidents" => (int)$weekly_incidents
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
