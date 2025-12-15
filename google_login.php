<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

use Google\Client as Google_Client;

/* ---------- READ TOKEN ---------- */
$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? null;

if (!$token) {
    http_response_code(400);
    echo json_encode(['error' => 'No token provided']);
    exit;
}

/* ---------- VERIFY GOOGLE TOKEN ---------- */
$client = new Google_Client([
    'client_id' => 'YOUR_WEB_CLIENT_ID'
]);

$payload = $client->verifyIdToken($token);

if (!$payload) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid Google token']);
    exit;
}

/* ---------- DB CONNECTION ---------- */
$pdo = new PDO(
    "mysql:host=db.luddy.indiana.edu;dbname=i494f25_team18;charset=utf8mb4",
    "i494f25_team18",
    "berms2227penes",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/* ---------- FIND OR CREATE USER ---------- */
$stmt = $pdo->prepare("SELECT id FROM users WHERE google_id = ?");
$stmt->execute([$payload['sub']]);
$user = $stmt->fetch();

if (!$user) {
    $stmt = $pdo->prepare("
        INSERT INTO users (google_id, email, name)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $payload['sub'],
        $payload['email'],
        $payload['name']
    ]);

    $userId = $pdo->lastInsertId();
} else {
    $userId = $user['id'];
}

/* ---------- LOG LOGIN EVENT ---------- */
$stmt = $pdo->prepare("
    INSERT INTO login_events (user_id, ip_address, user_agent)
    VALUES (?, ?, ?)
");
$stmt->execute([
    $userId,
    $_SERVER['REMOTE_ADDR'] ?? null,
    $_SERVER['HTTP_USER_AGENT'] ?? null
]);

/* ---------- SESSION ---------- */
session_regenerate_id(true);

$_SESSION['user'] = [
    'id'        => $userId,
    'google_id' => $payload['sub'],
    'email'     => $payload['email'],
    'name'      => $payload['name']
];

http_response_code(200);
echo json_encode(['status' => 'logged_in']);
