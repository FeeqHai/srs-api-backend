<?php
// Handle CORS for Web Browsers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

date_default_timezone_set(getenv('SRS_TIMEZONE') ?: 'Asia/Kuala_Lumpur');

// On hosting, set these as Apache environment variables or edit the fallback
// values here. The defaults keep local XAMPP working.
$host = getenv('SRS_DB_HOST') ?: "localhost";
$user = getenv('SRS_DB_USER') ?: "root";
$password = getenv('SRS_DB_PASSWORD') ?: ""; // Default XAMPP installation utilizes an empty string
$database = getenv('SRS_DB_NAME') ?: "srs_db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]));
}

$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '+08:00'");
?>
