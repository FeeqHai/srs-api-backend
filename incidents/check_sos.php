<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$after = isset($_GET['after']) ? $_GET['after'] : null;
$exclude_user = isset($_GET['exclude_user']) ? $_GET['exclude_user'] : null;

if (!$after) {
    // Fetch SOS from the last 30 minutes for dashboard display
    $query = "SELECT i.*, u.full_name FROM incidents i JOIN users u ON i.user_id = u.id WHERE i.category = 'Kecemasan' AND i.created_at >= NOW() - INTERVAL 30 MINUTE ORDER BY i.created_at DESC";
    if ($exclude_user) {
        $query = "SELECT i.*, u.full_name FROM incidents i JOIN users u ON i.user_id = u.id WHERE i.category = 'Kecemasan' AND i.created_at >= NOW() - INTERVAL 30 MINUTE AND i.user_id != ? ORDER BY i.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $exclude_user);
    } else {
        $stmt = $conn->prepare($query);
    }
} else {
    $query = "SELECT i.*, u.full_name FROM incidents i JOIN users u ON i.user_id = u.id WHERE i.category = 'Kecemasan' AND i.created_at > ?";
    if ($exclude_user) {
        $query .= " AND i.user_id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $after, $exclude_user);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $after);
    }
}

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

$sos_list = [];
while ($row = $result->fetch_assoc()) {
    $sos_list[] = $row;
}

echo json_encode([
    "status" => "success",
    "data" => $sos_list
]);

$stmt->close();
$conn->close();
?>
