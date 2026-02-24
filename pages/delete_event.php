<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['error' => 'Missing event ID']);
    exit;
}

$stmt = $conn->prepare("
    DELETE FROM events
    WHERE id = ? AND created_by = ?
");

$stmt->execute([$id, $user_id]);



echo json_encode(['success' => true]);
