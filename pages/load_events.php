<?php
ini_set('display_errors', 0);
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($conn)) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, title, event_date, event_time, description FROM events";
$stmt = $conn->query($sql);

$events = [];

while ($row = $stmt->fetch()) {
    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'start' => $row['event_date'] . 'T' . ($row['event_time'] ?? '00:00:00'),
        'extendedProps' => [
            'description' => $row['description']
        ]
    ];
}

echo json_encode($events);


