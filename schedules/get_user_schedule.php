<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

$user_id = $decoded['user_id'];

try {
    // 1. Get the user's squad
    $squad_query = "SELECT s.id as squad_id, s.name as squad_name, sm.role as squad_role 
                    FROM squad_members sm 
                    JOIN squads s ON sm.squad_id = s.id 
                    WHERE sm.user_id = ?";
    $stmt = $conn->prepare($squad_query);
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $squad_res = $stmt->get_result();
    
    $squad = null;
    $schedules = [];
    
    if ($squad_res->num_rows > 0) {
        $squad = $squad_res->fetch_assoc();
        $squad_id = $squad['squad_id'];
        $stmt->close();
        
        // 2. Get the squad's schedules (today and upcoming/all)
        $schedule_query = "SELECT id, shift_date, start_time, end_time 
                           FROM schedules 
                           WHERE squad_id = ? 
                           ORDER BY shift_date DESC, start_time ASC";
        $stmt2 = $conn->prepare($schedule_query);
        if (!$stmt2) {
            throw new Exception($conn->error);
        }
        $stmt2->bind_param("s", $squad_id);
        $stmt2->execute();
        $schedule_res = $stmt2->get_result();
        while ($row = $schedule_res->fetch_assoc()) {
            $schedules[] = $row;
        }
        $stmt2->close();

        // 3. Get the squad's members
        $members = [];
        $members_query = "SELECT u.id as user_id, u.full_name, u.phone_number, sm.role as squad_role 
                          FROM squad_members sm 
                          JOIN users u ON sm.user_id = u.id 
                          WHERE sm.squad_id = ? 
                          ORDER BY sm.role DESC, u.full_name ASC";
        $stmt3 = $conn->prepare($members_query);
        if ($stmt3) {
            $stmt3->bind_param("s", $squad_id);
            $stmt3->execute();
            $members_res = $stmt3->get_result();
            while ($row = $members_res->fetch_assoc()) {
                $members[] = $row;
            }
            $stmt3->close();
        }
        $squad['members'] = $members;
    } else {
        $stmt->close();
    }
    
    echo json_encode([
        "status" => "success",
        "squad" => $squad,
        "schedules" => $schedules
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

$conn->close();
?>
