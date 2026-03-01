<?php
session_start();
require_once 'db.php';

// Only logged-in users can report
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$reporter_id = $_SESSION['user']['id'];
$type        = $input['type'] ?? '';
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
$stmt = $conn->prepare("INSERT INTO reports (reporter_id, $column, reason) VALUES (?, ?, ?)");
$stmt->execute([$reporter_id, $item_id, $reason]);

// Detect localhost
$isLocal = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');

if ($isLocal) {
    // Just log locally
    $logFile = __DIR__ . '/report_test_log.txt';
    file_put_contents(
        $logFile,
        date('Y-m-d H:i:s') . " | Type: $type | Item ID: $item_id | Reporter: $reporter_id | Reason: $reason\n",
        FILE_APPEND
    );
} else {
    // Send email using PHP's mail() function (simpler than PHPMailer)
    $to      = 'MODERATION_EMAIL@gmail.com';
    $subject = 'New Report Submitted';
    $message = "Type: $type\nItem ID: $item_id\nReporter ID: $reporter_id\nReason:\n$reason";
    $headers = 'From: YOUR_EMAIL@gmail.com' . "\r\n";

    // This will attempt to send email; may require server mail setup
    @mail($to, $subject, $message, $headers);
}

// Always return JSON
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'Report submitted to moderation.']);
exit;
