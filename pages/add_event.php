<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['id'];


$title = trim($data['title'] ?? '');
$date = $data['date'] ?? '';
$time = $data['time'] ?? null;
$description = trim($data['description'] ?? '');
$location = trim($data['location'] ?? '');

if (!$title || !$date) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO events (title, event_date, event_time, description, location, created_by)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->execute([$title, $date, $time, $description, $location, $user_id]);

echo json_encode(['success' => true]);
