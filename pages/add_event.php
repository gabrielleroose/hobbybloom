<?php
require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$title = $data['title'];
$date = $data['date'];

$stmt = $conn->prepare("
    INSERT INTO events (title, start, end)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $title,
    $date . " 09:00:00",
    $date . " 10:00:00"
]);
