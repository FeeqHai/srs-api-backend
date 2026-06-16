<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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
        $stmt = $conn->prepare("SELECT id, item_name, total_quantity FROM equipment_inventory");
        $stmt->execute();
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // We can also calculate available quantity by subtracting currently borrowed items
        // Currently borrowed items = equipment_logs where returned_at IS NULL
        $calc_stmt = $conn->prepare("SELECT equipment_id, COUNT(*) as borrowed_count FROM equipment_logs WHERE returned_at IS NULL GROUP BY equipment_id");
        $calc_stmt->execute();
        $borrowed = $calc_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $results = [];
        foreach ($inventory as $item) {
            $borrowed_qty = $borrowed[$item['id']] ?? 0;
            $item['available_quantity'] = $item['total_quantity'] - $borrowed_qty;
            $results[] = $item;
        }
        
        echo json_encode(['success' => true, 'data' => $results]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
