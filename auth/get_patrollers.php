<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

// Fetch all users with role 'peronda' or 'ketua_peronda'
$query = "SELECT id, full_name, ic_number, district_zone, phone_number, account_status FROM users WHERE role IN ('peronda', 'ketua_peronda') ORDER BY full_name ASC";
$result = $conn->query($query);

$patrollers = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $patrollers[] = $row;
    }
}

echo json_encode([
    "status" => "success",
    "data" => $patrollers
]);

$conn->close();
?>
