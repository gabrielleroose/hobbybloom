<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}


define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET')); 
$isLocal = in_array($_SERVER['HTTP_HOST'], ['localhost:8000', 'localhost:3000']);
define('GOOGLE_REDIRECT_URI', $isLocal 
    ? 'http://localhost:8000/pages/Google_calendar_callback.php'
    : 'https://cgi.luddy.indiana.edu/~team18/pages/Google_calendar_callback.php'
);


$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id'             => GOOGLE_CLIENT_ID,
    'redirect_uri'          => GOOGLE_REDIRECT_URI,
    'response_type'         => 'code',
    'scope'                 => 'https://www.googleapis.com/auth/calendar',
    'access_type'           => 'offline',
    'prompt'                => 'consent',   
    'state'                 => $state,
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;
