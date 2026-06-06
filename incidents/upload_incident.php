<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Method Not Allowed."]);
    exit();
}

$id = $_POST['id'] ?? '';
$user_id = $_POST['user_id'] ?? '';
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$category = $_POST['category'] ?? '';
$latitude = $_POST['latitude'] ?? '';
$longitude = $_POST['longitude'] ?? '';
$status = 'Pending';

if (empty($id) || empty($user_id) || empty($title) || empty($category)) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit();
}

$image_url = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/incidents/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $file_name = $id . '.' . $file_extension;
    $target_path = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
        $image_url = 'uploads/incidents/' . $file_name; 
    }
}

$query = "INSERT INTO incidents (id, user_id, title, description, category, image_url, latitude, longitude, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssssssss", $id, $user_id, $title, $description, $category, $image_url, $latitude, $longitude, $status);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Incident reported successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Database insertion failed."]);
}

$stmt->close();
$conn->close();
?>