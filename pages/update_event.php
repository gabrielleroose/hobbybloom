<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['id'];

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$title = trim($data['title'] ?? '');
$date = $data['date'] ?? '';
$time = $data['time'] ?? null;
$description = trim($data['description'] ?? '');
$location = trim($data['location'] ?? '');
$invitees = $data['invitees'] ?? []; // array of user_ids

if (!$id || !$title || !$date) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Update event if user owns it
$stmt = $conn->prepare("
    UPDATE events
    SET title = ?, event_date = ?, event_time = ?, description = ?, location = ?
    WHERE id = ? AND created_by = ?
");
$stmt->execute([$title, $date, $time, $description, $location, $id, $user_id]);

// Remove old invites
$stmtDel = $conn->prepare("DELETE FROM event_invites WHERE event_id = ?");
$stmtDel->execute([$id]);

// Add new invites
if (!empty($invitees)) {
    $stmtInvite = $conn->prepare("INSERT INTO event_invites (event_id, user_id) VALUES (?, ?)");
    foreach ($invitees as $invitee_id) {
        $stmtInvite->execute([$id, $invitee_id]);
    }
}

echo json_encode(['success' => true]);
