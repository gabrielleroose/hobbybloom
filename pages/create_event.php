<?php
require 'calendar_init.php';

$event = new Google_Service_Calendar_Event([
    'summary' => 'Test From My App',
    'start' => [
        'dateTime' => '2026-02-10T14:00:00-05:00'
    ],
    'end' => [
        'dateTime' => '2026-02-10T15:00:00-05:00'
    ]
]);

$calendarService->events->insert('primary', $event);

echo "Event Created!";
