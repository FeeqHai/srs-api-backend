<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';
require_once '../config/access_control.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error(405, 'Invalid request method.');
}

$actor = get_current_actor($conn);

$stmt = $conn->prepare(
    'SELECT id, type, title, body, payload_json, is_read, created_at
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 100'
);
if (!$stmt) {
    api_error(500, 'Unable to load notifications.');
}

$stmt->bind_param('s', $actor['id']);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $row['is_read'] = (int)$row['is_read'];
    $payload = json_decode($row['payload_json'] ?? '', true);
    $row['related_id'] = is_array($payload) ? ($payload['related_id'] ?? null) : null;
    unset($row['payload_json']);
    $notifications[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'data' => $notifications
]);
?>
