<?php
require_once 'db.php';
require_once __DIR__ . '/../config/google.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? null;

if (!$token) {
    echo json_encode(['status' => 'error', 'message' => 'No token provided']);
    exit;
}

// Verify the ID token with Google: checks signature, expiry, issuer,
// and that it was issued for HobbyBloom's client ID.
try {
    $googleClient = new Google\Client(['client_id' => GOOGLE_SIGNIN_CLIENT_ID]);
    $payload = $googleClient->verifyIdToken($token);
} catch (Throwable $e) {
    error_log('Google token verification error: ' . $e->getMessage());
    $payload = false;
}

if ($payload && isset($payload['sub']) && !empty($payload['email_verified'])) {
    $googleId = $payload['sub'];
    $email = $payload['email'];
    
    $name = $payload['name'] ?? 'User';
    $firstName = $payload['given_name'] ?? 'New';
    $lastName = $payload['family_name'] ?? 'User';

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
            $stmt = $conn->prepare("INSERT INTO users (google_id, email, username, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$googleId, $email, $name, $firstName, $lastName]);
            $userId = $conn->lastInsertId();
            
            $redirectLocation = 'index.php';
        }

        // New session ID on login prevents session fixation.
        session_regenerate_id(true);

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
        error_log('Login database error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Something went wrong signing you in. Please try again.']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
}
?>