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
$state_id = trim($_POST['state_id'] ?? '');
$district_id = trim($_POST['district_id'] ?? '');
$zone_id = trim($_POST['zone_id'] ?? '');

// Strict Validation
if (empty($id) || empty($full_name) || empty($ic_number) || empty($phone_number) || empty($email) || empty($password) || empty($state_id) || empty($district_id) || empty($zone_id)) {
    echo json_encode(["status" => "error", "message" => "Borang tidak lengkap. Sila isi semua butiran."]);
    exit();
}

// Do not trust client-side dropdown values: the selected zone must belong to
// the supplied district, and that district must belong to the supplied state.
$location_query = "SELECT z.name AS zone_name
                   FROM zones z
                   JOIN districts d ON d.id = z.district_id
                   JOIN states st ON st.id = d.state_id
                   WHERE z.id = ?
                     AND d.id = ?
                     AND st.id = ?
                     AND z.is_active = 1
                     AND d.is_active = 1
                     AND st.is_active = 1";
$location_stmt = $conn->prepare($location_query);
if (!$location_stmt) {
    echo json_encode(["status" => "error", "message" => "Ralat pelayan semasa menyemak lokasi."]);
    exit();
}
$location_stmt->bind_param("sss", $zone_id, $district_id, $state_id);
$location_stmt->execute();
$location = $location_stmt->get_result()->fetch_assoc();
$location_stmt->close();

if (!$location) {
    echo json_encode(["status" => "error", "message" => "Pilihan negeri, daerah dan zon tidak sah."]);
    exit();
}

$district_zone = $location['zone_name'];

// Security: Hash the password before storage
$password_hash = password_hash($password, PASSWORD_BCRYPT);
$default_role = 'peronda';
$default_status = 'pending';

$query = "INSERT INTO users (id, full_name, ic_number, phone_number, email, password_hash, district_zone, state_id, district_id, zone_id, role, account_status)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Ralat pelayan: " . $conn->error]);
    exit();
}

$stmt->bind_param("ssssssssssss", $id, $full_name, $ic_number, $phone_number, $email, $password_hash, $district_zone, $state_id, $district_id, $zone_id, $default_role, $default_status);

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
