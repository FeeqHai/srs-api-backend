<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["status" => "error", "message" => "Method Not Allowed."]);
    exit();
}

$user_id = $_GET['user_id'] ?? null;

if ($user_id) {
    $query = "SELECT i.*, u.full_name FROM incidents i JOIN users u ON i.user_id = u.id WHERE i.user_id = ? ORDER BY i.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT i.*, u.full_name FROM incidents i JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC";
    $result = $conn->query($query);
}

$incidents = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $incidents[] = $row;
    }
}

echo json_encode(["status" => "success", "data" => $incidents]);

$conn->close();
?>