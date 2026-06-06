<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Method Not Allowed."]);
    exit();
}

$id = trim($_POST['id'] ?? '');

if (empty($id)) {
    echo json_encode(["status" => "error", "message" => "Incident ID required."]);
    exit();
}

$query = "DELETE FROM incidents WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database prep failed."]);
    exit();
}

$stmt->bind_param("s", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Record deleted."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Record not found."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Database execution failed."]);
}

$stmt->close();
$conn->close();
?>