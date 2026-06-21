<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../config/db_connect.php';
require_once '../config/jwt_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

$token = get_bearer_token();
$decoded = $token ? verify_jwt($token) : false;
if (!$decoded) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
    exit();
}
if (!in_array(($decoded['role'] ?? ''), ['ketua_krt', 'super_admin'], true)) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Ketua KRT or Super Admin access required."]);
    exit();
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);
if ($data) {
    $_POST = array_merge($_POST, $data);
}
$attendance_id = trim($_POST['attendance_id'] ?? '');
if ($attendance_id === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Attendance ID is required."]);
    exit();
}

$conn->begin_transaction();
try {
    $attendance_query = "SELECT return_requested_at, return_latitude, return_longitude
        FROM attendance WHERE id = ? AND return_status = 'pending_acknowledgement' FOR UPDATE";
    $attendance_stmt = $conn->prepare($attendance_query);
    $attendance_stmt->bind_param("s", $attendance_id);
    $attendance_stmt->execute();
    $attendance_result = $attendance_stmt->get_result();
    if ($attendance_result->num_rows === 0) {
        throw new Exception('Pemulangan tidak lagi menunggu pengesahan.');
    }
    $attendance = $attendance_result->fetch_assoc();
    $attendance_stmt->close();

    $complete_query = "UPDATE attendance SET
        clock_out_time = return_requested_at,
        clock_out_latitude = return_latitude,
        clock_out_longitude = return_longitude,
        return_status = 'completed',
        return_acknowledged_at = NOW(),
        return_acknowledged_by = ?
        WHERE id = ?";
    $complete_stmt = $conn->prepare($complete_query);
    $complete_stmt->bind_param("ss", $decoded['user_id'], $attendance_id);
    if (!$complete_stmt->execute()) {
        throw new Exception('Gagal melengkapkan shif.');
    }
    $complete_stmt->close();

    $equipment_query = "UPDATE equipment_logs SET returned_at = ? WHERE attendance_id = ? AND returned_at IS NULL";
    $equipment_stmt = $conn->prepare($equipment_query);
    $equipment_stmt->bind_param("ss", $attendance['return_requested_at'], $attendance_id);
    if (!$equipment_stmt->execute()) {
        throw new Exception('Gagal merekod pemulangan peralatan.');
    }
    $equipment_stmt->close();

    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Pemulangan peronda telah disahkan."]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>
