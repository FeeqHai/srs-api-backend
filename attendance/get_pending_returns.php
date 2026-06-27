<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

$token = get_bearer_token();
$decoded = $token ? verify_jwt($token) : false;
if (!$decoded) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
    exit();
}
if (!in_array(($decoded['role'] ?? ''), ['ketua_krt', 'super_admin'], true)) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Ketua KRT or Super Admin access required."]);
    exit();
}

$query = "SELECT a.id, a.return_requested_at, a.clock_in_time, u.full_name
    FROM attendance a
    JOIN users u ON u.id = a.user_id
    WHERE a.clock_out_time IS NULL
      AND a.clock_in_time IS NOT NULL
      AND a.return_status = 'pending_acknowledgement'
    ORDER BY a.return_requested_at ASC";
$result = $conn->query($query);
$returns = [];
while ($return = $result->fetch_assoc()) {
    $equipment_query = "SELECT ei.item_name FROM equipment_logs el
        JOIN equipment_inventory ei ON ei.id = el.equipment_id
        WHERE el.attendance_id = ? AND el.returned_at IS NULL
        ORDER BY ei.item_name";
    $equipment_stmt = $conn->prepare($equipment_query);
    $equipment_stmt->bind_param("s", $return['id']);
    $equipment_stmt->execute();
    $equipment_result = $equipment_stmt->get_result();
    $equipment = [];
    while ($item = $equipment_result->fetch_assoc()) {
        $equipment[] = $item['item_name'];
    }
    $equipment_stmt->close();
    $return['equipment'] = $equipment;
    $returns[] = $return;
}

echo json_encode(["status" => "success", "data" => $returns]);
$conn->close();
?>
