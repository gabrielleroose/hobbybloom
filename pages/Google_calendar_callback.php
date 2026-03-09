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
    ? 'http://localhost:8000/pages/google_calendar_callback.php'
    : 'https://cgi.luddy.indiana.edu/~team18/pages/google_calendar_callback.php'
);


if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    die('Invalid state. Possible CSRF attack.');
}
unset($_SESSION['oauth_state']);

if (isset($_GET['error'])) {
    header('Location: calendar.php?gc_error=' . urlencode($_GET['error']));
    exit;
}

$code = $_GET['code'] ?? null;
if (!$code) {
    header('Location: calendar.php?gc_error=no_code');
    exit;
}


$response = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'code'          => $code,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ]),
    ]
]));

if (!$response) {
    header('Location: calendar.php?gc_error=token_exchange_failed');
    exit;
}

$tokens = json_decode($response, true);

if (empty($tokens['access_token'])) {
    header('Location: calendar.php?gc_error=no_access_token');
    exit;
}

$accessToken  = $tokens['access_token'];
$refreshToken = $tokens['refresh_token'] ?? null;
$expiresIn    = $tokens['expires_in']    ?? 3600;
$expiresAt    = time() + $expiresIn;


try {
    $stmt = $conn->prepare("
        UPDATE users
        SET gc_access_token  = ?,
            gc_refresh_token = COALESCE(?, gc_refresh_token),
            gc_token_expires = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $accessToken,
        $refreshToken,
        $expiresAt,
        $_SESSION['user']['id']
    ]);
} catch (Exception $e) {
    header('Location: calendar.php?gc_error=db_failed');
    exit;
}

header('Location: calendar.php?gc_connected=1');
exit;
