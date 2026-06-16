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
    // Query active patrollers with their squad name (if any) and clock in time
    $query = "SELECT a.id as attendance_id, a.clock_in_time, a.latitude, a.longitude, u.full_name, u.phone_number, s.name as squad_name 
              FROM attendance a
              JOIN users u ON a.user_id = u.id
              LEFT JOIN squad_members sm ON u.id = sm.user_id
              LEFT JOIN squads s ON sm.squad_id = s.id
              WHERE a.clock_out_time IS NULL
              ORDER BY a.clock_in_time DESC";
              
    $result = $conn->query($query);
    $active = [];
    while ($row = $result->fetch_assoc()) {
        $active[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $active
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
