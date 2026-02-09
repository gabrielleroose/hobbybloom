<?php
require 'vendor/autoload.php';
session_start();

$client = new Google_Client();
$client->setAuthConfig('credentials.json');

$client->setRedirectUri('http://localhost/yourproject/calendar_callback.php');

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

$_SESSION['calendar_token'] = $token;

echo "Google Calendar Connected!";
