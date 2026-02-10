<?php
require_once 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? null;

if (!$token) {
    echo json_encode(['status' => 'error', 'message' => 'No token provided']);
    exit;
}

$parts = explode('.', $token);
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

if ($payload && isset($payload['sub'])) {
    $googleId = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'];

    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            $userId = $existingUser['id'];
            

            $stmtProfile = $conn->prepare("SELECT profile_id FROM user_profiles WHERE user_id = ?");
            $stmtProfile->execute([$userId]);
            $hasProfile = $stmtProfile->fetch();

            $redirectLocation = $hasProfile ? 'dashboard.php' : 'index.php';

        } else {
            $stmt = $conn->prepare("INSERT INTO users (google_id, email, username) VALUES (?, ?, ?)");
            $stmt->execute([$googleId, $email, $name]);
            $userId = $conn->lastInsertId();
            
            $redirectLocation = 'index.php';
        }


        $_SESSION['user'] = [
            'id' => $userId, 
            'email' => $email,
            'name' => $name
        ];

        $_SESSION['google_id'] = $googleId;

        echo json_encode([
            'status' => 'success', 
            'redirect' => $redirectLocation
        ]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
}
?>