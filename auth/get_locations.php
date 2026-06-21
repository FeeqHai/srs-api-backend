<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$level = $_GET['level'] ?? 'states';
$parent_id = trim($_GET['parent_id'] ?? '');

if ($level === 'states') {
    $result = $conn->query('SELECT id, code, name FROM states WHERE is_active = 1 ORDER BY name ASC');
} elseif ($level === 'districts' && $parent_id !== '') {
    $stmt = $conn->prepare('SELECT id, name FROM districts WHERE state_id = ? AND is_active = 1 ORDER BY name ASC');
    $stmt->bind_param('s', $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($level === 'zones' && $parent_id !== '') {
    $stmt = $conn->prepare('SELECT id, name FROM zones WHERE district_id = ? AND is_active = 1 ORDER BY name ASC');
    $stmt->bind_param('s', $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid location request."]);
    $conn->close();
    exit();
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["status" => "success", "data" => $data]);

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>
