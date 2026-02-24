<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user']['id'];

// Fetch events user created OR is invited to and hasn't declined
$stmt = $conn->prepare("
    SELECT e.id, e.title, e.event_date, e.event_time, e.description, e.location,
           ei.status AS invite_status, e.created_by
    FROM events e
    LEFT JOIN event_invites ei 
        ON e.id = ei.event_id AND ei.user_id = ?
    WHERE e.created_by = ? OR (ei.user_id = ? AND (ei.status IS NULL OR ei.status != 'declined'))
");
$stmt->execute([$user_id, $user_id, $user_id]);

$events = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $date = $row['event_date'];
    $time = $row['event_time'];

    // Handle all-day events (no time) or timed events
    $start = $date;
    if (!empty($time)) {
        // Ensure time is in HH:MM:SS format
        if (strlen($time) <= 5) $time .= ':00';
        $start .= 'T' . $time;
    }

    $status = $row['invite_status'] ?? null;

    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'start' => $start,
        'allDay' => empty($time),
        'extendedProps' => [
            'description' => $row['description'],
            'location' => $row['location'],
            'status' => $status,
            'isOwner' => $row['created_by'] == $user_id
        ]
    ];
}

echo json_encode($events);