<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

$id = trim($_POST['id'] ?? '');
$incident_id = trim($_POST['incident_id'] ?? '');
$text_content = trim($_POST['text_content'] ?? '');
$category = trim($_POST['category'] ?? '');
$title = trim($_POST['title'] ?? '');

if (empty($id) || empty($incident_id) || empty($text_content)) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit();
}

$uploaded_urls = [];
$host = $_SERVER['HTTP_HOST'];
$upload_dir = '../uploads/addendums/';
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
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png'];
    if (in_array($file_ext, $allowed_ext)) {
        $file_name = uniqid() . '_' . $index . '.' . $file_ext;
        $destination = $upload_dir . $file_name;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $uploaded_urls[] = "http://$host/srs_api/uploads/addendums/" . $file_name;
        }
    }
}

$image_url = empty($uploaded_urls) ? null : json_encode($uploaded_urls);

$query = "INSERT INTO incident_addendums (id, incident_id, text_content, image_url, category, title) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit();
}

$stmt->bind_param("ssssss", $id, $incident_id, $text_content, $image_url, $category, $title);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Addendum added successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to add addendum."]);
}

$stmt->close();
$conn->close();
?>
