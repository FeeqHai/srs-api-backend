<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Sistem menolak request ini."]);
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

$jwt_user_id = $decoded['user_id'];

// 1. Data Extraction & Sanitization (Strictly aligned with incident_service.dart)
$id = trim($_POST['id'] ?? '');
$reported_by = $jwt_user_id;
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$latitude = trim($_POST['latitude'] ?? '');
$longitude = trim($_POST['longitude'] ?? '');
$location_name = trim($_POST['location_name'] ?? '');
$reported_to = trim($_POST['reported_to'] ?? '');

// 2. Payload Validation
if (empty($id) || empty($reported_by) || empty($title) || empty($description) || empty($category) || empty($latitude) || empty($longitude)) {
    echo json_encode(["status" => "error", "message" => "Ralat pelayan: Medan data tidak lengkap dihantar oleh aplikasi."]);
    exit();
}

// 3. Binary Image Handling
$uploaded_urls = [];
$host = $_SERVER['HTTP_HOST'];
$upload_dir = '../uploads/incidents/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$files_to_process = [];
if (isset($_FILES['images'])) {
    $file_count = count($_FILES['images']['name']);
    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $files_to_process[] = [
                'name' => $_FILES['images']['name'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
            ];
        }
    }
} elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $files_to_process[] = $_FILES['image'];
}

foreach ($files_to_process as $index => $file) {
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $secure_filename = $id . '_' . time() . '_' . $index . '.' . $file_extension;
    $target_filepath = $upload_dir . $secure_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_filepath)) {
        $uploaded_urls[] = "http://$host/srs_api/uploads/incidents/" . $secure_filename;
    }
}

$public_image_url = empty($uploaded_urls) ? null : json_encode($uploaded_urls);

// 5. Database Insertion
// NOTE: Verify your table is named 'incidents' and contains these exact columns
$query = "INSERT INTO incidents (id, user_id, title, description, category, latitude, longitude, image_url, location_name, reported_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Ralat pangkalan data: " . $conn->error]);
    exit();
}

// Bind variables (10 strings = "ssssssssss")
$stmt->bind_param("ssssssssss", $id, $reported_by, $title, $description, $category, $latitude, $longitude, $public_image_url, $location_name, $reported_to);

// 6. Final Evaluation
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Laporan insiden berjaya direkodkan."]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal merekodkan insiden ke dalam sistem."]);
}

$stmt->close();
$conn->close();
?>