<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}


define('GOOGLE_CLIENT_ID',     '1011869688630-kl05vvf13cg6u6d1tlo9rnj0l4kj7rvn.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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'); 

$user_id = $_SESSION['user']['id'];


$stmt = $conn->prepare("SELECT gc_access_token, gc_refresh_token, gc_token_expires FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$tokenRow = $stmt->fetch();

if (empty($tokenRow['gc_access_token'])) {
    echo json_encode(['success' => false, 'error' => 'not_connected']);
    exit;
}

$accessToken  = $tokenRow['gc_access_token'];
$refreshToken = $tokenRow['gc_refresh_token'];
$expiresAt    = $tokenRow['gc_token_expires'];


if (time() >= $expiresAt - 60) {
    if (!$refreshToken) {
        echo json_encode(['success' => false, 'error' => 'token_expired_no_refresh']);
        exit;
    }

    $refreshResponse = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'client_id'     => GOOGLE_CLIENT_ID,
                'client_secret' => GOOGLE_CLIENT_SECRET,
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
            ]),
        ]
    ]));

    $refreshData = json_decode($refreshResponse, true);

    if (empty($refreshData['access_token'])) {
        echo json_encode(['success' => false, 'error' => 'refresh_failed']);
        exit;
    }

    $accessToken = $refreshData['access_token'];
    $expiresAt   = time() + ($refreshData['expires_in'] ?? 3600);

    $conn->prepare("UPDATE users SET gc_access_token = ?, gc_token_expires = ? WHERE id = ?")
         ->execute([$accessToken, $expiresAt, $user_id]);
}


function gcal_request($method, $url, $accessToken, $body = null) {
    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ];

    $opts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
        ]
    ];

    if ($body !== null) {
        $opts['http']['content'] = json_encode($body);
    }

    $result = file_get_contents($url, false, stream_context_create($opts));
    return $result ? json_decode($result, true) : null;
}


$siteEvents = $conn->prepare("
    SELECT id, title, event_date, event_time, description, location, gc_event_id
    FROM events
    WHERE created_by = ?
");
$siteEvents->execute([$user_id]);
$events = $siteEvents->fetchAll();



$pushed = 0;
$updated = 0;

foreach ($events as $event) {
    // Build start datetime
    if (!empty($event['event_time'])) {
        $startDateTime = $event['event_date'] . 'T' . $event['event_time'];
        $endDateTime   = date('Y-m-d\TH:i:s', strtotime($startDateTime) + 3600); 
        $startObj = ['dateTime' => $startDateTime, 'timeZone' => 'UTC'];
        $endObj   = ['dateTime' => $endDateTime,   'timeZone' => 'UTC'];
    } else {
        $startObj = ['date' => $event['event_date']];
        $endObj   = ['date' => $event['event_date']];
    }

    $gcBody = [
        'summary'     => $event['title'],
        'description' => $event['description'] ?? '',
        'location'    => $event['location']    ?? '',
        'start'       => $startObj,
        'end'         => $endObj,
    ];

    if (!empty($event['gc_event_id'])) {

        $result = gcal_request(
            'PUT',
            'https://www.googleapis.com/calendar/v3/calendars/primary/events/' . urlencode($event['gc_event_id']),
            $accessToken,
            $gcBody
        );
        if (!empty($result['id'])) $updated++;
    } else {
 
        $result = gcal_request(
            'POST',
            'https://www.googleapis.com/calendar/v3/calendars/primary/events',
            $accessToken,
            $gcBody
        );

        if (!empty($result['id'])) {

            $conn->prepare("UPDATE events SET gc_event_id = ? WHERE id = ?")
                 ->execute([$result['id'], $event['id']]);
            $pushed++;
        }
    }
}


$timeMin = urlencode(date('Y-m-d\T00:00:00\Z'));
$timeMax = urlencode(date('Y-m-d\T23:59:59\Z', strtotime('+60 days')));

$gcEvents = gcal_request(
    'GET',
    "https://www.googleapis.com/calendar/v3/calendars/primary/events?timeMin={$timeMin}&timeMax={$timeMax}&singleEvents=true&orderBy=startTime",
    $accessToken
);

$pulled = [];

if (!empty($gcEvents['items'])) {
 
    $ownGcIds = $conn->prepare("SELECT gc_event_id FROM events WHERE created_by = ? AND gc_event_id IS NOT NULL");
    $ownGcIds->execute([$user_id]);
    $ownIds = array_column($ownGcIds->fetchAll(), 'gc_event_id');

    foreach ($gcEvents['items'] as $gEvent) {
        if (in_array($gEvent['id'], $ownIds)) continue; 

        $start = $gEvent['start']['dateTime'] ?? $gEvent['start']['date'] ?? null;
        if (!$start) continue;

        $pulled[] = [
            'id'      => 'gc_' . $gEvent['id'],
            'title'   => ($gEvent['summary'] ?? 'Google Event') . ' 📅',
            'start'   => $start,
            'allDay'  => isset($gEvent['start']['date']),
            'color'   => '#4285f4',
            'extendedProps' => [
                'description' => $gEvent['description'] ?? '',
                'location'    => $gEvent['location']    ?? '',
                'isOwner'     => false,
                'isGoogleEvent' => true,
            ]
        ];
    }
}

echo json_encode([
    'success' => true,
    'pushed'  => $pushed,
    'updated' => $updated,
    'pulled'  => $pulled,
]);


