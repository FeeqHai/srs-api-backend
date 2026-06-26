<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';
require_once '../config/access_control.php';

$actor = get_current_actor($conn);
$user_id = $actor['id'];

$query = "SELECT u.id, u.full_name, u.ic_number, u.phone_number, u.email, u.profile_image_path, u.district_zone,
                 u.state_id, u.district_id, u.zone_id, u.role, u.account_status, u.created_at,
                 st.name AS state_name, d.name AS district_name, z.name AS zone_name
          FROM users u
          LEFT JOIN states st ON st.id = u.state_id
          LEFT JOIN districts d ON d.id = u.district_id
          LEFT JOIN zones z ON z.id = u.zone_id
          WHERE u.id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit();
}

$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    echo json_encode([
        "status" => "success",
        "data" => $user
    ]);
} else {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "User not found."]);
}

$stmt->close();
$conn->close();
?>
