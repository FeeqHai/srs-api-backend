<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
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
    if ($method === 'GET') {
        // Get all squads with their members
        $stmt = $conn->prepare("SELECT s.id as squad_id, s.name as squad_name, s.created_at, sm.user_id, sm.role, u.full_name, u.ic_number 
                                FROM squads s 
                                LEFT JOIN squad_members sm ON s.id = sm.squad_id 
                                LEFT JOIN users u ON sm.user_id = u.id
                                ORDER BY s.created_at DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);

        $squads = [];
        foreach ($results as $row) {
            $squad_id = $row['squad_id'];
            if (!isset($squads[$squad_id])) {
                $squads[$squad_id] = [
                    'id' => $squad_id,
                    'name' => $row['squad_name'],
                    'created_at' => $row['created_at'],
                    'members' => []
                ];
            }
            if ($row['user_id']) {
                $squads[$squad_id]['members'][] = [
                    'user_id' => $row['user_id'],
                    'role' => $row['role'],
                    'full_name' => $row['full_name'],
                    'ic_number' => $row['ic_number']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'data' => array_values($squads)]);
    } 
    elseif ($method === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_squad') {
            $name = $_POST['name'] ?? '';
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Squad name is required']);
                exit();
            }

            $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $stmt = $conn->prepare("INSERT INTO squads (id, name) VALUES (?, ?)");
            $stmt->bind_param('ss', $id, $name);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Squad created successfully', 'squad_id' => $id]);
        } 
        elseif ($action === 'add_member') {
            $squad_id = $_POST['squad_id'] ?? '';
            $user_id = $_POST['user_id'] ?? '';
            $role = $_POST['role'] ?? 'member';

            if (empty($squad_id) || empty($user_id)) {
                echo json_encode(['success' => false, 'message' => 'Squad ID and User ID are required']);
                exit();
            }

            // Check if already in ANY squad
            $check = $conn->prepare("SELECT sm.*, s.name as squad_name FROM squad_members sm JOIN squads s ON sm.squad_id = s.id WHERE sm.user_id = ?");
            $check->bind_param('s', $user_id);
            $check->execute();
            $check_res = $check->get_result();
            if ($check_res->num_rows > 0) {
                $row = $check_res->fetch_assoc();
                echo json_encode(['success' => false, 'message' => 'Peronda sudah ditugaskan ke kumpulan lain (' . $row['squad_name'] . ')']);
                exit();
            }

            $stmt = $conn->prepare("INSERT INTO squad_members (squad_id, user_id, role) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $squad_id, $user_id, $role);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Member added to squad']);
        }
        elseif ($action === 'delete_squad') {
            $squad_id = $_POST['squad_id'] ?? '';
            if (empty($squad_id)) {
                echo json_encode(['success' => false, 'message' => 'Squad ID is required']);
                exit();
            }

            $conn->begin_transaction();
            try {
                // Delete squad members first
                $stmt1 = $conn->prepare("DELETE FROM squad_members WHERE squad_id = ?");
                $stmt1->bind_param('s', $squad_id);
                $stmt1->execute();

                // Delete squad schedules
                $stmt2 = $conn->prepare("DELETE FROM schedules WHERE squad_id = ?");
                $stmt2->bind_param('s', $squad_id);
                $stmt2->execute();

                // Delete squad itself
                $stmt3 = $conn->prepare("DELETE FROM squads WHERE id = ?");
                $stmt3->bind_param('s', $squad_id);
                $stmt3->execute();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Squad deleted successfully']);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed to delete squad: ' . $e->getMessage()]);
            }
        }
        elseif ($action === 'remove_member') {
            $squad_id = $_POST['squad_id'] ?? '';
            $user_id = $_POST['user_id'] ?? '';

            if (empty($squad_id) || empty($user_id)) {
                echo json_encode(['success' => false, 'message' => 'Squad ID and User ID are required']);
                exit();
            }

            $stmt = $conn->prepare("DELETE FROM squad_members WHERE squad_id = ? AND user_id = ?");
            $stmt->bind_param('ss', $squad_id, $user_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Member removed from squad']);
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
