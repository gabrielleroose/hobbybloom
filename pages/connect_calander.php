<?php
require 'vendor/autoload.php';
session_start();

$client = new Google_Client();
$client->setAuthConfig('credentials.json');

$client->addScope(Google_Service_Calendar::CALENDAR);

$client->setRedirectUri('http://localhost/calendar_callback.php');

header('Location: ' . $client->createAuthUrl());
exit();
