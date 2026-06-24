<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$data        = json_decode(file_get_contents('php://input'), true);
$follower_id = $_SESSION['user']['id'];
$followed_id = (int)($data['followed_id'] ?? 0);

if (!$followed_id || $followed_id === $follower_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid user']);
    exit;
}

try {
    $check = $conn->prepare("SELECT 1 FROM user_follows WHERE follower_id = ? AND followed_id = ?");
    $check->execute([$follower_id, $followed_id]);

    if ($check->fetch()) {
        $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND followed_id = ?")
             ->execute([$follower_id, $followed_id]);
        echo json_encode(['success' => true, 'action' => 'unfollowed']);
    } else {
        $privStmt = $conn->prepare("SELECT is_private FROM user_profiles WHERE user_id = ?");
        $privStmt->execute([$followed_id]);
        $isPrivate = $privStmt->fetchColumn();

        $status = ($isPrivate == 1) ? 'pending' : 'accepted';
        $conn->prepare("INSERT INTO user_follows (follower_id, followed_id, status) VALUES (?, ?, ?)")
             ->execute([$follower_id, $followed_id, $status]);
        echo json_encode(['success' => true, 'action' => 'followed', 'status' => $status]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}