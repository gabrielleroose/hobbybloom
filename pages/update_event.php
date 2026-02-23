<?php
require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'];
$title = $data['title'];
$date = $data['date'];
$time = $data['time'];
$description = $data['description'];

if (!$id) {
    http_response_code(400);
    echo json_encode(['message' => 'Event ID missing']);
    exit;
}

$stmt = $conn->prepare("
    UPDATE events
    SET title = ?, event_date = ?, event_time = ?, description = ?
    WHERE id = ?
");
$stmt->execute([$title, $date, $time, $description, $id]);

echo json_encode(['success' => true]);
