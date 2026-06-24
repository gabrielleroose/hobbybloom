<?php

session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated'
    ]);
    exit;
}

$user_id = $_SESSION['user']['id'];

$data = json_decode(file_get_contents('php://input'), true);
$event_id = $data['id'] ?? null;

if (!$event_id) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing event ID'
    ]);
    exit;
}

try {

    $conn->beginTransaction();


    $check = $conn->prepare("
        SELECT id
        FROM events
        WHERE id = ? AND created_by = ?
    ");

    $check->execute([$event_id, $user_id]);

    if (!$check->fetch()) {

        $conn->rollBack();

        echo json_encode([
            'success' => false,
            'error' => 'Event not found or permission denied'
        ]);
        exit;
    }


    $stmtInvites = $conn->prepare("
        DELETE FROM event_invites
        WHERE event_id = ?
    ");

    $stmtInvites->execute([$event_id]);


    $stmt = $conn->prepare("
        DELETE FROM events
        WHERE id = ?
    ");

    $stmt->execute([$event_id]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'id' => $event_id
    ]);

} catch (Exception $e) {

    $conn->rollBack();

    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}