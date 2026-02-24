<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Check authentication using Google login session structure
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['id'];

// Get the JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Extract fields safely
$id = $data['id'] ?? null;
$title = trim($data['title'] ?? '');
$date = $data['date'] ?? '';
$time = $data['time'] ?? null;
$description = trim($data['description'] ?? '');
$location = trim($data['location'] ?? '');

// Validate required fields
if (!$id || !$title || !$date) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Prepare and execute the update
$stmt = $conn->prepare("
    UPDATE events
    SET title = ?, event_date = ?, event_time = ?, description = ?, location = ?
    WHERE id = ? AND created_by = ?
");

$stmt->execute([$title, $date, $time, $description, $location, $id, $user_id]);

// Check if any row was actually updated
if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Update failed or no changes made']);
}

