<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

$id = trim($_POST['id'] ?? '');
$user_id = trim($_POST['user_id'] ?? '');
$checkpoint_id = trim($_POST['checkpoint_id'] ?? 'Unknown Checkpoint');
$latitude = trim($_POST['latitude'] ?? '');
$longitude = trim($_POST['longitude'] ?? '');

if (empty($id) || empty($user_id) || empty($latitude) || empty($longitude)) {
    echo json_encode(["status" => "error", "message" => "ID, User ID, and GPS coordinates are mandatory."]);
    exit();
}

// THE TEMPORAL LOCK: Prevent duplicate scans for the same shift (12-hour window)
$lock_query = "SELECT id FROM attendance_logs WHERE user_id = ? AND checkpoint_id = ? AND scanned_at >= NOW() - INTERVAL 12 HOUR";
$lock_stmt = $conn->prepare($lock_query);
$lock_stmt->bind_param("ss", $user_id, $checkpoint_id);
$lock_stmt->execute();
$lock_result = $lock_stmt->get_result();

if ($lock_result->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Shift Lockout: Checkpoint already secured for the current active shift."]);
    $lock_stmt->close();
    $conn->close();
    exit();
}
$lock_stmt->close();

// Proceed with Data Insertion
$query = "INSERT INTO attendance_logs (id, user_id, checkpoint_id, latitude, longitude) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssss", $id, $user_id, $checkpoint_id, $latitude, $longitude);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Clock-in successfully recorded."]);
} else {
    echo json_encode(["status" => "error", "message" => "Database insertion failed."]);
}

$stmt->close();
$conn->close();
?>