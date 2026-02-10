<?php
require_once 'base.php';

$stmt = $conn->query("SELECT id, title, start, end FROM events");

$events = [];

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $events[] = $row;
}

echo json_encode($events);
