<?php
// Turn off standard error display so it doesn't break JSON format
ini_set('display_errors', 0);
require_once 'db.php';

header('Content-Type: application/json');

try {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // Check if data was received
    if (!$data) {
        throw new Exception("No data received from the frontend.");
    }

    // Strict Validation
    if (empty($data['title']) || empty($data['date']) || empty($data['time'])) {
        throw new Exception("Missing required fields: Title, Date, or Time.");
    }

    $sql = "INSERT INTO events (title, event_date, event_time, description)
            VALUES (:title, :event_date, :event_time, :description)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':title'        => $data['title'],
        ':event_date'   => $data['date'],
        ':event_time'   => $data['time'],
        ':description'  => $data['description'] ?? null
    ]);

    echo json_encode(["status" => "success", "message" => "Event saved to database!"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}