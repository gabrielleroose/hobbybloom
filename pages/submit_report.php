<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only logged-in users can report
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Decode JSON payload
$input = json_decode(file_get_contents('php://input'), true);

$reporter_id = $_SESSION['user']['id'];
$reason      = trim($input['reason'] ?? '');
$type        = $input['type'] ?? '';
$item_id     = $input['item_id'] ?? null;

if (!$reporter_id || !$type || !$item_id || empty($reason)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing report data']);
    exit;
}

// Map type to DB column
$column = match($type) {
    'circle' => 'circle_id',
    'module' => 'module_id',
    'user'   => 'user_id',
    default  => null,
};

if (!$column) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid report type']);
    exit;
}

// Save to database
$stmt = $conn->prepare("INSERT INTO reports (reporter_id, $column, reason) VALUES (?, ?, ?)");
$stmt->execute([$reporter_id, $item_id, $reason]);

// Check if running locally
$isLocal = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');

if ($isLocal) {
    // Log report locally
    file_put_contents(
        __DIR__ . '/report_test_log.txt',
        date('Y-m-d H:i:s') . " | Type: $type | Item ID: $item_id | Reporter: $reporter_id | Reason: $reason\n",
        FILE_APPEND
    );
} else {
    // Send email via PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'YOUR_EMAIL@gmail.com';        // <-- update
        $mail->Password   = 'YOUR_APP_PASSWORD';           // <-- update
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('YOUR_EMAIL@gmail.com', 'HobbyBloom Reports');  // <-- update
        $mail->addAddress('MODERATION_EMAIL@gmail.com');               // <-- update

        $mail->Subject = 'New Report Submitted';
        $mail->Body    = "Type: $type\nItem ID: $item_id\nReporter ID: $reporter_id\nReason:\n$reason";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }
}

echo json_encode(['status' => 'success']);