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
$title      = trim($data['title'] ?? '');
$date       = $data['date'] ?? '';
$time       = $data['time'] ?: null;
$description= trim($data['description'] ?? '');
$location   = trim($data['location'] ?? '');
$invitees   = $data['invitees'] ?? [];

if (!$title || !$date) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {

    $conn->beginTransaction();


    $stmt = $conn->prepare("
        INSERT INTO events (title, event_date, event_time, description, location, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $date, $time, $description, $location, $user_id]);

    $event_id = $conn->lastInsertId();


    if (!empty($invitees)) {

        $invite_stmt = $conn->prepare("
            INSERT INTO event_invites (event_id, user_id, status)
            VALUES (?, ?, 'pending')
        ");

        foreach ($invitees as $uid) {
            if ($uid != $user_id) {
                $invite_stmt->execute([$event_id, $uid]);
            }
        }
    }

    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error']);
}