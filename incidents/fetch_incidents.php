<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 10;

$offset = ($page - 1) * $limit;

$user_id = $_GET['user_id'] ?? null;
$requester_id = $_GET['requester_id'] ?? null;
$category_filter = $_GET['category'] ?? null;

// Enforce row-level security
if ($requester_id) {
    $role_query = "SELECT role FROM users WHERE id = ?";
    $stmt_role = $conn->prepare($role_query);
    if ($stmt_role) {
        $stmt_role->bind_param("s", $requester_id);
        $stmt_role->execute();
        $result_role = $stmt_role->get_result();
        if ($row_role = $result_role->fetch_assoc()) {
            if ($row_role['role'] === 'peronda') {
                // Force user_id filter to their own ID if they are a regular patroller
                $user_id = $requester_id;
            }
        }
        $stmt_role->close();
    }
}

$where_clauses = [];
$params = [];
$types = "";

if ($user_id) {
    $where_clauses[] = "i.user_id = ?";
    $params[] = $user_id;
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