<?php
require 'calendar_init.php';

$events = $calendarService->events->listEvents('primary');

foreach ($events->getItems() as $event) {
    echo $event->getSummary() . "<br>";
}
