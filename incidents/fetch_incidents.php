<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$query = "SELECT * FROM incidents ORDER BY created_at DESC";
$result = $conn->query($query);

$incidents = [];
while ($row = $result->fetch_assoc()) {
    $incidents[] = $row;
}

echo json_encode(["status" => "success", "data" => $incidents]);
$conn->close();
?>