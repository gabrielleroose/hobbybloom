<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success'=>false,'error'=>'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$user_id     = $_SESSION['user']['id'];
$id          = $data['id'] ?? null;
$title       = trim($data['title'] ?? '');
$date        = $data['date'] ?? '';
$time        = $data['time'] ?: null;
$description = trim($data['description'] ?? '');
$location    = trim($data['location'] ?? '');

$invitees = $data['invitees'] ?? [];
$circles  = $data['circles'] ?? [];

if (!$id || !$title || !$date) {
    echo json_encode(['success'=>false,'error'=>'Missing required fields']);
    exit;
}

try {
    $conn->beginTransaction();

    // Update event
    $stmt = $conn->prepare("
        UPDATE events
        SET title = ?, event_date = ?, event_time = ?, description = ?, location = ?
        WHERE id = ? AND created_by = ?
    ");
    $stmt->execute([$title, $date, $time, $description, $location, $id, $user_id]);
    if($stmt->rowCount() === 0) throw new Exception("Not owner");

    // Remove all old invites
    $conn->prepare("DELETE FROM event_invites WHERE event_id = ?")->execute([$id]);

    $allInvites = [];

    // Individual invites
    foreach($invitees as $uid){
        if($uid != $user_id) $allInvites[] = $uid;
    }

    // Circle invites
    if(!empty($circles)){
        $circleStmt = $conn->prepare("SELECT user_id FROM circle_members WHERE circle_id = ?");
        foreach($circles as $circle_id){
            $circleStmt->execute([$circle_id]);
            $members = $circleStmt->fetchAll(PDO::FETCH_COLUMN);
            foreach($members as $member_id){
                if($member_id != $user_id) $allInvites[] = $member_id;
            }
        }
    }

    $allInvites = array_unique($allInvites);

    // Insert new invites
    if(!empty($allInvites)){
        $inviteStmt = $conn->prepare("INSERT INTO event_invites (event_id, user_id, status) VALUES (?, ?, 'pending')");
        foreach($allInvites as $uid){
            $inviteStmt->execute([$id, $uid]);
        }
    }

    $conn->commit();
    echo json_encode(['success'=>true]);

} catch(Exception $e){
    $conn->rollBack();
    echo json_encode(['success'=>false,'error'=>'Update failed']);
}