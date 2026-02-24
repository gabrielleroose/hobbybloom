<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['error'=>'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['id'];

$title = trim($data['title'] ?? '');
$date = $data['date'] ?? '';
$time = $data['time'] ?? null;
$description = trim($data['description'] ?? '');
$location = trim($data['location'] ?? '');
$invitees = $data['invitees'] ?? [];

if (!$title || !$date) {
    echo json_encode(['error'=>'Missing required fields']);
    exit;
}

// Insert event
$stmt = $conn->prepare("INSERT INTO events (title,event_date,event_time,description,location,created_by) VALUES (?,?,?,?,?,?)");
$stmt->execute([$title,$date,$time,$description,$location,$user_id]);
$event_id = $conn->lastInsertId();

// Insert invites (skip creator if included accidentally)
if(!empty($invitees)){
    $invite_stmt = $conn->prepare("INSERT INTO event_invites (event_id,user_id,status) VALUES (?,?, 'pending')");
    foreach($invitees as $uid){
        if ($uid != $user_id) {
            $invite_stmt->execute([$event_id,$uid]);
        }
    }
}

// Return event in FullCalendar-friendly format
$start = $date . 'T' . ($time ?: '00:00:00');

echo json_encode([
    'success' => true,
    'event' => [
        'id' => $event_id,
        'title' => $title,
        'start' => $start,
        'extendedProps' => [
            'description' => $description,
            'location' => $location,
            'status' => null,
            'isOwner' => true
        ]
    ]
]);