<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);

// Check authentication
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$event_id = $data['id'] ?? null;
$status = $data['status'] ?? null;

if (!$event_id || !in_array($status, ['accepted','declined'])) {
    echo json_encode(['error' => 'Missing or invalid parameters']);
    exit;
}

// Check if user is actually invited
$stmt = $conn->prepare("SELECT * FROM event_invites WHERE event_id = ? AND user_id = ?");
$stmt->execute([$event_id, $user_id]);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invite) {
    echo json_encode(['error' => 'You are not invited to this event']);
    exit;
}

// Update invite status
$update = $conn->prepare("UPDATE event_invites SET status = ? WHERE id = ?");
$update->execute([$status, $invite['id']]);

echo json_encode(['success' => true, 'status' => $status]);