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

// Fetch all users with role 'peronda' or 'ketua_peronda' and their current squad if assigned
$query = "SELECT u.id, u.full_name, u.ic_number, u.district_zone, u.phone_number, u.account_status, u.role, s.name as squad_name 
          FROM users u 
          LEFT JOIN squad_members sm ON u.id = sm.user_id 
          LEFT JOIN squads s ON sm.squad_id = s.id 
          WHERE u.role IN ('peronda', 'ketua_peronda') 
          ORDER BY u.full_name ASC";
$result = $conn->query($query);

$patrollers = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $patrollers[] = $row;
    }
}

echo json_encode([
    "status" => "success",
    "data" => $patrollers
]);

$conn->close();
?>
