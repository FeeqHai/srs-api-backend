<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

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

$jwt_user_id = $decoded['user_id'];
$jwt_role = $decoded['role'];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 10;

$offset = ($page - 1) * $limit;

// Row-level security: regular patrollers can only see their own incidents
if ($jwt_role === 'peronda') {
    $user_id = $jwt_user_id;
} else {
    // Admins/leaders can view specific users if specified, otherwise see all
    $user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : null;
}
$category_filter = $_GET['category'] ?? null;

$where_clauses = [];
$params = [];
$types = "";

if ($user_id) {
    $where_clauses[] = "i.user_id = ?";
    $params[] = $user_id;
    $types .= "s";
}

$zone_filter = $_GET['zone'] ?? null;
if ($zone_filter && $zone_filter !== 'Semua') {
    $where_clauses[] = "u.district_zone = ?";
    $params[] = $zone_filter;
    $types .= "s";
}

$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

if ($category_filter && $category_filter !== 'Semua') {
    // Exact match or prefix match (e.g., "Jenayah Kekerasan" should match "Jenayah Kekerasan - Pembunuhan")
    // Use LIKE with % to allow matching the parent category
    $where_clauses[] = "i.category LIKE ?";
    $params[] = $category_filter . "%";
    $types .= "s";
}

if ($start_date && $end_date) {
    $where_clauses[] = "DATE(i.created_at) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

$where_sql = "WHERE i.category != 'Kecemasan'";
if (count($where_clauses) > 0) {
    $where_sql .= " AND " . implode(" AND ", $where_clauses);
}

$query = "SELECT i.*, u.full_name, u.district_zone 
          FROM incidents i 
          JOIN users u ON i.user_id = u.id 
          $where_sql
          ORDER BY i.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Call bind_param dynamically
$stmt->bind_param($types, ...$params);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Server error: " . $conn->error]);
    exit();
}
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