<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$user_id    = $_SESSION['user']['id'];
$id         = $data['id'] ?? null;
$title      = trim($data['title'] ?? '');
$date       = $data['date'] ?? '';
$time       = $data['time'] ?: null;
$description= trim($data['description'] ?? '');
$location   = trim($data['location'] ?? '');
$invitees   = $data['invitees'] ?? [];

if (!$id || !$title || !$date) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {

    $conn->beginTransaction();


    $stmt = $conn->prepare("
        UPDATE events
        SET title = ?, event_date = ?, event_time = ?, description = ?, location = ?
        WHERE id = ? AND created_by = ?
    ");

    $stmt->execute([$title, $date, $time, $description, $location, $id, $user_id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Not owner");
    }


    $conn->prepare("DELETE FROM event_invites WHERE event_id = ?")
         ->execute([$id]);

    if (!empty($invitees)) {

        $invite_stmt = $conn->prepare("
            INSERT INTO event_invites (event_id, user_id, status)
            VALUES (?, ?, 'pending')
        ");

        foreach ($invitees as $invitee_id) {
            if ($invitee_id != $user_id) {
                $invite_stmt->execute([$id, $invitee_id]);
            }
        }
    }

    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => 'Update failed']);
}