<?php
require_once 'base.php';

$stmt = $conn->query("SELECT id, title, start, end FROM events");

$events[] = [
    'title' => $row['title'],
    'start' => $row['date'] . 'T' . $row['time'],
    'extendedProps' => [
        'description' => $row['description']
    ]
];


while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $events[] = $row;
}

echo json_encode($events);
