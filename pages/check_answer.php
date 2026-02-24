<?php
require 'db.php';
header('Content-Type: application/json');


$data = json_decode(file_get_contents('php://input'), true); //reads incoming json data
$answer_id = $data['answer_id'] ?? null; //assigns null value if answer_id not found, same with stage_num
$stage_num = $data['stage_num'] ?? null;

if (!$answer_id) {
    echo json_encode(['correct' => false]); //checks if answer id exists.
    exit;
}

// Fetch the answer's correctness from the database
$stmt = $conn->prepare("SELECT is_correct FROM module_stage_questions_answers WHERE id = ?");
$stmt->execute([$answer_id]);
$is_correct = $stmt->fetchColumn();

if ($is_correct === false) {
    echo json_encode(['correct' => false]);
} else {
    echo json_encode(['correct' => $is_correct == 1]);
}