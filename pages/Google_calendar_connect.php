<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}


define('GOOGLE_CLIENT_ID',     '1011869688630-kl05vvf13cg6u6d1tlo9rnj0l4kj7rvn.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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'); 
$isLocal = ($_SERVER['HTTP_HOST'] === 'localhost:8000');
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
