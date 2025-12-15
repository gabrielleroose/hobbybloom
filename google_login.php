<?php
require 'vendor/autoload.php';
session_start();

use Google\Client as Google_Client;

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? null;

if (!$token) {
    http_response_code(400);
    exit;
}

$client = new Google_Client([
    'client_id' => '1011869688630-kl05vvf13cg6u6d1tlo9rnj0l4kj7rvn.apps.googleusercontent.com'
]);


$payload = $client->verifyIdToken($token);

if ($payload) {
    $_SESSION['user'] = [
        'google_id' => $payload['sub'],
        'email' => $payload['email'],
        'name' => $payload['name']
    ];
    http_response_code(200);
} else {
    http_response_code(401);
}
