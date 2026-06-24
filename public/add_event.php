<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success'=>false,'error'=>'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $_SESSION['user']['id'];
$title = trim($data['title'] ?? '');
$date = $data['date'] ?? '';
$time = $data['time'] ?: null;
$description = trim($data['description'] ?? '');
$location = trim($data['location'] ?? '');

$invitees = $data['invitees'] ?? [];
$circles = $data['circles'] ?? [];

if (!$title || !$date) {
    echo json_encode(['success'=>false,'error'=>'Missing required fields']);
    exit;
}

try {
    $conn->beginTransaction();

    // Create event
    $stmt = $conn->prepare("
        INSERT INTO events (title, event_date, event_time, description, location, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$title, $date, $time, $description, $location, $user_id]);
    $event_id = $conn->lastInsertId();

    $allInvites = [];

    // Add individual invites
    foreach($invitees as $uid){
        if($uid != $user_id) $allInvites[] = $uid;
    }

    // Add circle members
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

    // Insert invites
    if(!empty($allInvites)){
        $inviteStmt = $conn->prepare("INSERT INTO event_invites (event_id, user_id, status) VALUES (?, ?, 'pending')");
        foreach($allInvites as $uid){
            $inviteStmt->execute([$event_id, $uid]);
        }
    }

    $conn->commit();
    echo json_encode(['success'=>true]);

} catch(Exception $e){
    $conn->rollBack();
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}