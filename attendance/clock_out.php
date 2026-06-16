<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    if ($method === 'POST') {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if ($data) {
            $_POST = array_merge($_POST, $data);
        }

        $user_id = trim($_POST['user_id'] ?? '');
        $lat = (float)($_POST['latitude'] ?? 0);
        $lon = (float)($_POST['longitude'] ?? 0);

        if (empty($user_id)) {
            echo json_encode(["status" => "error", "message" => "User ID is required."]);
            exit();
        }

        // 1. Find the active attendance record for this user (where clock_out_time is null)
        $find_query = "SELECT id FROM attendance WHERE user_id = ? AND clock_out_time IS NULL ORDER BY clock_in_time DESC LIMIT 1";
        $find_stmt = $conn->prepare($find_query);
        $find_stmt->bind_param("s", $user_id);
        $find_stmt->execute();
        $res = $find_stmt->get_result();
        
        if ($res->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "Tiada rekod kehadiran aktif dijumpai."]);
            exit();
        }
        
        $row = $res->fetch_assoc();
        $attendance_id = $row['id'];
        $find_stmt->close();

        // 2. Update attendance with clock_out details
        $update_query = "UPDATE attendance SET clock_out_time = NOW(), clock_out_latitude = ?, clock_out_longitude = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sss", $lat, $lon, $attendance_id);
        
        if ($update_stmt->execute()) {
            // 3. Mark all borrowed equipment as returned
            $eq_query = "UPDATE equipment_logs SET returned_at = NOW() WHERE attendance_id = ? AND returned_at IS NULL";
            $eq_stmt = $conn->prepare($eq_query);
            $eq_stmt->bind_param("s", $attendance_id);
            $eq_stmt->execute();
            $eq_stmt->close();

            echo json_encode(["status" => "success", "message" => "Syif berjaya ditamatkan."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menamatkan syif."]);
        }
        $update_stmt->close();
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
