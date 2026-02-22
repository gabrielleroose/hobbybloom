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

require_once __DIR__ . '/../vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/../credentials.json');
$client->addScope(Google_Service_Calendar::CALENDAR);

$accessToken = json_decode(file_get_contents(__DIR__ . '/../token.json'), true);
$client->setAccessToken($accessToken);

$service = new Google_Service_Calendar($client);

$startDateTime = $date . 'T' . $time . ':00';
$endDateTime = $date . 'T' . $time . ':00';

$event = new Google_Service_Calendar_Event([
    'summary' => $title,
    'description' => $description,
    'start' => [
        'dateTime' => $startDateTime,
        'timeZone' => 'America/Indiana/Indianapolis',
    ],
    'end' => [
        'dateTime' => $endDateTime,
        'timeZone' => 'America/Indiana/Indianapolis',
    ],
]);

$calendarId = 'primary'; // or your specific calendar ID
$service->events->insert($calendarId, $event);



