<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT e.id, e.title, e.event_date, e.event_time, e.description, e.location, e.created_by
    FROM events e
    LEFT JOIN event_invites ei ON ei.event_id = e.id AND ei.user_id = ?
    WHERE e.created_by = ? OR ei.status = 'accepted'
");
$stmt->execute([$user_id, $user_id]);

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
        'extendedProps' => [
            'description' => $row['description'],
            'location' => $row['location'],
            'creator' => $row['created_by']
        ]
    ];
}

echo json_encode($events);
