<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Email and password are required."]);
    exit();
}

$query = "SELECT id, full_name, password_hash, role, account_status FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Check account status before verifying password
    if ($user['account_status'] === 'pending') {
        echo json_encode(["status" => "error", "message" => "Akaun sedang menunggu pengesahan Ketua KRT."]);
        exit();
    } else if ($user['account_status'] === 'inactive') {
        echo json_encode(["status" => "error", "message" => "Akaun telah dinyahaktifkan."]);
        exit();
    }
    
    // The bcrypt validation mechanism
    if (password_verify($password, $user['password_hash'])) {
        echo json_encode([
            "status" => "success",
            "message" => "Authentication successful.",
            "data" => [
                "id" => $user['id'],
                "full_name" => $user['full_name'],
                "role" => $user['role']
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid credentials."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "User not found."]);
}

$stmt->close();
$conn->close();
?>