<?php
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? null;

if (!$token) {
    http_response_code(400);
    exit('No token provided');
}

// Decode the JWT payload (simplified, DOES NOT check signature!)
// For local testing only
$parts = explode('.', $token);
if (count($parts) !== 3) {
    http_response_code(401);
    exit('Invalid token format');
}

$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

if ($payload && isset($payload['sub'])) {
    $_SESSION['user'] = [
        'google_id' => $payload['sub'],
        'email' => $payload['email'],
        'name' => $payload['name']
    ];
    http_response_code(200);
    echo "Login successful!";
} else {
    http_response_code(401);
    exit('Invalid token');
}