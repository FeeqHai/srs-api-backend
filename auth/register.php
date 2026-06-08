<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Sistem menolak request ini."]);
    exit();
}

$id = trim($_POST['id'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$ic_number = trim($_POST['ic_number'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$district_zone = trim($_POST['district_zone'] ?? '');

// Strict Validation
if (empty($id) || empty($full_name) || empty($ic_number) || empty($phone_number) || empty($email) || empty($password) || empty($district_zone)) {
    echo json_encode(["status" => "error", "message" => "Borang tidak lengkap. Sila isi semua butiran."]);
    exit();
}

// Security: Hash the password before storage
$password_hash = password_hash($password, PASSWORD_BCRYPT);
$default_role = 'peronda';
$default_status = 'pending';

$query = "INSERT INTO users (id, full_name, ic_number, phone_number, email, password_hash, district_zone, role, account_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Ralat pelayan: " . $conn->error]);
    exit();
}

$stmt->bind_param("sssssssss", $id, $full_name, $ic_number, $phone_number, $email, $password_hash, $district_zone, $default_role, $default_status);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Pendaftaran berjaya. Akaun sedang menunggu pengesahan Ketua KRT."]);
} else {
    // Catch duplicate IC or Email errors
    if ($conn->errno === 1062) {
        echo json_encode(["status" => "error", "message" => "No. Kad Pengenalan atau E-mel telah didaftarkan dalam sistem."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Ralat pangkalan data."]);
    }
}

$stmt->close();
$conn->close();
?>