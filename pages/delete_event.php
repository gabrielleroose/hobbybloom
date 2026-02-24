<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['id'];

$data = json_decode(file_get_contents('php://input'), true);
$event_id = $data['id'] ?? null;

if (!$event_id) {
    echo json_encode(['error' => 'Missing event ID']);
    exit;
}

// Delete event only if the current user is the owner
$stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND created_by = ?");
$stmt->execute([$event_id, $user_id]);

// Optionally, delete all invites for this event (cascade in DB may handle this)
$stmtInvites = $conn->prepare("DELETE FROM event_invites WHERE event_id = ?");
$stmtInvites->execute([$event_id]);

echo json_encode(['success' => true, 'id' => $event_id]);
