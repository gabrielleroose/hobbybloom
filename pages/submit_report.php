<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Only logged-in users can report
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$reporter_id = $_SESSION['user']['id'];
$type        = $input['type']   ?? '';
$item_id     = $input['item_id'] ?? null;
$reason      = trim($input['reason'] ?? '');

if (!$type || !$item_id || empty($reason)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing report data']);
    exit;
}

// Map type to DB column
$column = match($type) {
    'circle' => 'circle_id',
    'module' => 'module_id',
    'user'   => 'reported_user_id',
    default  => null,
};

if (!$column) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid report type']);
    exit;
}

// Save to database
try {
    $stmt = $conn->prepare("INSERT INTO reports (reporter_id, $column, reason) VALUES (?, ?, ?)");
    $stmt->execute([$reporter_id, $item_id, $reason]);
} catch (Exception $e) {
    error_log('Report database error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Could not save your report. Please try again.']);
    exit;
}

// Detect localhost
$isLocal = (
    $_SERVER['SERVER_NAME'] === 'localhost' ||
    ($_SERVER['SERVER_ADDR'] ?? '') === '127.0.0.1' ||
    str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost')
);

if ($isLocal) {
    // Local dev: write to the server log instead of sending email
    error_log("[report] Type: $type | Item ID: $item_id | Reporter: $reporter_id | Reason: $reason");
} elseif (!getenv('RESEND_API_KEY')) {
    // Report is already saved in the database; just skip the email
    error_log('RESEND_API_KEY not set; report saved but no email sent.');
} else {
    // Send email via Resend API (key lives in Railway env vars, never in code)
    $resendApiKey = getenv('RESEND_API_KEY');

    $emailBody = "A new report has been submitted.\n\nType: $type\nItem ID: $item_id\nReporter ID: $reporter_id\nReason:\n$reason";

    $payload = json_encode([
        'from'    => 'HobbyBloom <onboarding@resend.dev>',
        'to'      => ['HobbyBloomadm@gmail.com'],
        'subject' => 'New Report Submitted',
        'text'    => $emailBody
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $resendApiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode(['status' => 'error', 'message' => 'Email failed: ' . $curlError]);
        exit;
    }

    $resendResponse = json_decode($response, true);
    if (isset($resendResponse['statusCode']) && $resendResponse['statusCode'] >= 400) {
        echo json_encode(['status' => 'error', 'message' => 'Email API error: ' . ($resendResponse['message'] ?? 'unknown')]);
        exit;
    }
}

echo json_encode(['status' => 'success', 'message' => 'Report submitted successfully.']);
exit;