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
    SELECT id, title, event_date, event_time, description, location
    FROM events
    WHERE created_by = ?
");

$stmt->execute([$user_id]);

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
            'location' => $row['location']
        ]
    ];
}

echo json_encode($events);
