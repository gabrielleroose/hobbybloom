<?php
require 'vendor/autoload.php';
session_start();

if (!isset($_SESSION['calendar_token'])) {
    die("Calendar not connected.");
}

$client = new Google_Client();
$client->setAuthConfig('credentials.json');
$client->setAccessToken($_SESSION['calendar_token']);

$calendarService = new Google_Service_Calendar($client);
