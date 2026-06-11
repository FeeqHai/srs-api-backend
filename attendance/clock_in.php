<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

$id = trim($_POST['id'] ?? '');
$user_id = trim($_POST['user_id'] ?? '');
$checkpoint_id = trim($_POST['checkpoint_id'] ?? '');
$lat = (float)($_POST['latitude'] ?? 0);
$lon = (float)($_POST['longitude'] ?? 0);

if (empty($id) || empty($user_id) || empty($checkpoint_id) || $lat == 0 || $lon == 0) {
    echo json_encode(["status" => "error", "message" => "Missing required attendance data or GPS."]);
    exit();
}

// 1. Fetch user's district zone
$query = "SELECT district_zone FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit();
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit();
}
$user = $res->fetch_assoc();
$district_zone = $user['district_zone'];
$stmt->close();

// 2. Anti-Cheat Validation (QR Payload Parsing)
// Expected Format: SRS_ATTENDANCE|{admin_lat}|{admin_lon}|{timestamp}
$qr_parts = explode('|', $checkpoint_id);
if (count($qr_parts) !== 4 || $qr_parts[0] !== 'SRS_ATTENDANCE') {
    echo json_encode(["status" => "error", "message" => "Unknown Checkpoint: Kod QR tidak sah atau bukan milik KRT ini."]);
    exit();
}

$admin_lat = (float)$qr_parts[1];
$admin_lon = (float)$qr_parts[2];
$qr_time_str = $qr_parts[3];

// A. Time Expiration Validation (Max 60 seconds + 5s buffer for network/clock desync)
$qr_time = strtotime($qr_time_str);
$current_time = time();
$time_diff = abs($current_time - $qr_time); // Use abs() in case phone clock is slightly ahead
if ($time_diff > 65) {
    echo json_encode(["status" => "error", "message" => "Kod QR telah tamat tempoh. Sila minta Admin jana kod QR baharu. (Menghalang tangkapan skrin)"]);
    exit();
}

// B. GPS Distance Validation (Haversine Formula - Max 100 meters)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; // in meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

$distance = calculateDistance($lat, $lon, $admin_lat, $admin_lon);
if ($distance > 100) { // 100 meters tolerance
    echo json_encode(["status" => "error", "message" => "Clock-in ditolak: GPS anda dikesan berada terlalu jauh (" . round($distance) . "m) dari peranti Admin."]);
    exit();
}

// 3. Create table if not exists (for self-healing)
$conn->query("CREATE TABLE IF NOT EXISTS attendance (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    checkpoint_id VARCHAR(50),
    latitude VARCHAR(50),
    longitude VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// 4. Record attendance
$query = "INSERT INTO attendance (id, user_id, checkpoint_id, latitude, longitude) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssss", $id, $user_id, $checkpoint_id, $lat, $lon);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Kehadiran direkodkan berjaya di zon $district_zone."]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal merekodkan kehadiran."]);
}

$stmt->close();
$conn->close();
?>