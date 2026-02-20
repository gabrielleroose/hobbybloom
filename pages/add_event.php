<?php
require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$title = $data['title'];
$date = $data['date'];
$time = $data['time'];
$description = $data['description'];


$stmt = $conn->prepare(
    "INSERT INTO events (title, date, time, description)
     VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $title, $date, $time, $description);
    $stmt->execute();
    

$stmt->execute([
    $title,
    $date . " 09:00:00",
    $date . " 10:00:00"
]);
