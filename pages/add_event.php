<?php
require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$title = $data['title'];
$date = $data['date'];
$time = $data['time'];
$description = $data['description'];

$sql = "INSERT INTO events (title, event_date, event_time, description)
        VALUES (:title, :event_date, :event_time, :description)";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ':title' => $title,
    ':event_date' => $date,
    ':event_time' => $time,
    ':description' => $description
]);


