<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

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

// Extract user details from JWT
$jwt_user_id = $decoded['user_id'];

// Decode incoming JSON payload
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Malformed JSON payload"]);
    exit();
}

$user_id = $data['user_id'] ?? '';
$latitude = $data['latitude'] ?? '';
$longitude = $data['longitude'] ?? '';

// Double check payload matches JWT user
if (empty($user_id) || $user_id !== $jwt_user_id) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Forbidden: User ID mismatch"]);
    exit();
}

if (empty($latitude) || empty($longitude)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Coordinates are required."]);
    exit();
}

// Database Insertion using UUID()
$query = "INSERT INTO incidents (id, user_id, title, description, category, latitude, longitude, status, location_name, reported_to) VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit();
}

$title = 'KECEMASAN / SOS DIHANTAR';
$description = 'Peronda ini memerlukan bantuan SEGERA di koordinat ini.';
$category = 'Kecemasan';
$status = 'Pending';
$location_name = 'Lokasi GPS Semasa';
$reported_to = 'Polis';

$stmt->bind_param("sssssssss", $user_id, $title, $description, $category, $latitude, $longitude, $status, $location_name, $reported_to);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "SOS alert recorded successfully."
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to record SOS alert: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
