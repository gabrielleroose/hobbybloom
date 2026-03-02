<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT DISTINCT 
        e.id,
        e.title,
        e.event_date,
        e.event_time,
        e.description,
        e.location,
        e.created_by,
        ei.status AS invite_status
    FROM events e
    LEFT JOIN event_invites ei 
        ON e.id = ei.event_id AND ei.user_id = ?
    WHERE 
        e.created_by = ?
        OR (
            ei.user_id = ?
            AND ei.status != 'declined'
        )
");

$stmt->execute([$user_id, $user_id, $user_id]);

$events = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $start = $row['event_date'];
    if (!empty($row['event_time'])) {
        $start .= 'T' . $row['event_time'];
    }

    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'start' => $start,
        'allDay' => empty($row['event_time']),
        'extendedProps' => [
            'description' => $row['description'],
            'location' => $row['location'],
            'status' => $row['invite_status'],
            'isOwner' => $row['created_by'] == $user_id
        ]
    ];
}

echo json_encode($events);