<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

// 1. Intercept the client-generated UUID
$id = trim($_POST['id'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($id) || empty($full_name) || empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "All fields (including ID) are required."]);
    exit();
}

// 2. Data Integrity Check: Prevent Duplicate Emails
$check_query = "SELECT id FROM users WHERE email = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("s", $email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email already registered."]);
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

// 3. Cryptographic Generation
// The UUID polyfill is removed. The client's UUID is utilized.
$password_hash = password_hash($password, PASSWORD_BCRYPT);
$role = 'Patroller'; // Default role assignment

// 4. Database Insertion
$query = "INSERT INTO users (id, full_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssss", $id, $full_name, $email, $password_hash, $role);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Registration successful."]);
} else {
    echo json_encode(["status" => "error", "message" => "Database insertion failed."]);
}

$stmt->close();
$conn->close();
?>