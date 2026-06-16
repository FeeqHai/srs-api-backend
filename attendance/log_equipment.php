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

        $attendance_id = trim($_POST['attendance_id'] ?? '');
        $equipment = $_POST['equipment'] ?? [];

        // Dart sends json.encode(), so it might arrive as a JSON string
        if (is_string($equipment)) {
            $decoded = json_decode($equipment, true);
            if (is_array($decoded)) {
                $equipment = $decoded;
            } else {
                $equipment = [];
            }
        }

        if (empty($attendance_id)) {
            echo json_encode(["status" => "error", "message" => "Attendance ID is required."]);
            exit();
        }

        if (is_array($equipment) && count($equipment) > 0) {
            $eq_query = "INSERT INTO equipment_logs (id, attendance_id, equipment_id) VALUES (?, ?, ?)";
            $eq_stmt = $conn->prepare($eq_query);
            foreach ($equipment as $eq_id) {
                $log_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $eq_stmt->bind_param("sss", $log_id, $attendance_id, $eq_id);
                $eq_stmt->execute();
            }
            $eq_stmt->close();
        }

        echo json_encode(["status" => "success", "message" => "Equipment logged successfully."]);
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
