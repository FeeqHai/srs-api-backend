<?php
$host = "localhost";
$user = "root";
$password = ""; // Default XAMPP installation utilizes an empty string
$database = "srs_db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]));
}
?>