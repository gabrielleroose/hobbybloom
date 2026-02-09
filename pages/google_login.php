<?php
require_once 'base.php'; 

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? null;

if (!$token) {
    http_response_code(400); 
    exit('No token provided');
}

$parts = explode('.', $token);
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

if ($payload && isset($payload['sub'])) {
    $googleId = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE google_id = ?");
    $stmt->execute([$googleId]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        $userId = $existingUser['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO users (google_id, email, username) VALUES (?, ?, ?)");
        $stmt->execute([$googleId, $email, $name]);
        $userId = $conn->lastInsertId();
    }

    $_SESSION['user'] = [
        'id' => $userId, 
        'email' => $email,
        'name' => $name
    ];

    echo "Login successful!";
} else {
    http_response_code(401); 
    exit('Invalid token');
}
?>





this will let us connect the callander with a button
<a href="connect_calendar.php">
    Connect Google Calendar
</a>
