<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php'; // Ensure this path is correct

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Sistem menolak request ini."]);
    exit();
}

// 1. Data Extraction & Sanitization (Strictly aligned with incident_service.dart)
$id = trim($_POST['id'] ?? '');
$reported_by = trim($_POST['reported_by'] ?? '');
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$latitude = trim($_POST['latitude'] ?? '');
$longitude = trim($_POST['longitude'] ?? '');

// 2. Payload Validation
if (empty($id) || empty($reported_by) || empty($title) || empty($description) || empty($category) || empty($latitude) || empty($longitude)) {
    echo json_encode(["status" => "error", "message" => "Ralat pelayan: Medan data tidak lengkap dihantar oleh aplikasi."]);
    exit();
}

// 3. Binary Image Handling
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "Ralat pelayan: Gambar bukti gagal diterima."]);
    exit();
}

// Define storage architecture
$upload_dir = '../uploads/incidents/';
// Ensure the directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate secure filename to prevent overwriting
$file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$secure_filename = $id . '_' . time() . '.' . $file_extension;
$target_filepath = $upload_dir . $secure_filename;

// 4. Move Binary to Storage
if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_filepath)) {
    echo json_encode(["status" => "error", "message" => "Ralat sistem: Gagal menyimpan fail gambar."]);
    exit();
}

// Generate the public URL to save in the database (Adjust IP to your local XAMPP IP)
$public_image_url = "http://192.168.1.109/srs_api/uploads/incidents/" . $secure_filename;

// 5. Database Insertion
// NOTE: Verify your table is named 'incidents' and contains these exact columns
$query = "INSERT INTO incidents (id, user_id, title, description, category, latitude, longitude, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Ralat pangkalan data: " . $conn->error]);
    exit();
}

// Bind variables (8 strings = "ssssssss")
$stmt->bind_param("ssssssss", $id, $reported_by, $title, $description, $category, $latitude, $longitude, $public_image_url);

// 6. Final Evaluation
if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Laporan insiden berjaya direkodkan."]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal merekodkan insiden ke dalam sistem."]);
}

$stmt->close();
$conn->close();
?>