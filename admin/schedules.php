<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    if ($method === 'GET') {
        // Get all schedules
        $date_filter = $_GET['date'] ?? null;
        
        $sql = "SELECT sc.id, sc.squad_id, sc.shift_date, sc.start_time, sc.end_time, sc.created_at, sq.name as squad_name 
                FROM schedules sc 
                JOIN squads sq ON sc.squad_id = sq.id";
        
        if ($date_filter) {
            $sql .= " WHERE sc.shift_date = ?";
        }
        
        $sql .= " ORDER BY sc.shift_date DESC, sc.start_time ASC";
        
        $stmt = $conn->prepare($sql);
        if ($date_filter) {
            $stmt->bind_param("s", $date_filter);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $schedules = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $schedules]);
    } 
    elseif ($method === 'POST') {
        $action = $_POST['action'] ?? 'create_schedule';

        if ($action === 'create_schedule') {
            $squad_id = $_POST['squad_id'] ?? '';
            $shift_date = $_POST['shift_date'] ?? '';
            $start_time = $_POST['start_time'] ?? '';
            $end_time = $_POST['end_time'] ?? '';

            if (empty($squad_id) || empty($shift_date) || empty($start_time) || empty($end_time)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit();
            }

            $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $stmt = $conn->prepare("INSERT INTO schedules (id, squad_id, shift_date, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $id, $squad_id, $shift_date, $start_time, $end_time);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Schedule created successfully', 'schedule_id' => $id]);
        }
        elseif ($action === 'delete_schedule') {
            $schedule_id = $_POST['schedule_id'] ?? '';

            if (empty($schedule_id)) {
                echo json_encode(['success' => false, 'message' => 'Schedule ID required']);
                exit();
            }

            $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
            $stmt->bind_param('s', $schedule_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Schedule deleted']);
        }
        else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
