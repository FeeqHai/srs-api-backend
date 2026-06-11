<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

if (!isset($_GET['incident_id'])) {
    echo json_encode(["status" => "error", "message" => "incident_id is required."]);
    exit();
}

$incident_id = $_GET['incident_id'];

$query = "SELECT * FROM incident_addendums WHERE incident_id = ? ORDER BY created_at ASC";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit();
}

$stmt->bind_param("s", $incident_id);
$stmt->execute();
$result = $stmt->get_result();

$addendums = [];
while ($row = $result->fetch_assoc()) {
    $addendums[] = $row;
}

echo json_encode([
    "status" => "success",
    "data" => $addendums
]);

$stmt->close();
$conn->close();
?>
