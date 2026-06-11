<?php
header('Content-Type: application/json');
require_once 'config/db_connect.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 10;

$offset = ($page - 1) * $limit;

// Fetch incidents with user details
$query = "SELECT i.*, u.full_name, u.district_zone 
          FROM incidents i 
          JOIN users u ON i.user_id = u.id 
          ORDER BY i.created_at DESC 
          LIMIT ? OFFSET ?";
          
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $conn->error]);
    exit();
}

$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$incidents = [];
while ($row = $result->fetch_assoc()) {
    $incidents[] = $row;
}

echo json_encode([
    "status" => "success",
    "page" => $page,
    "limit" => $limit,
    "data" => $incidents
]);

$stmt->close();
$conn->close();
?>
