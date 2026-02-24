<?php
require 'db.php';
header('Content-Type: application/json');

session_start();
$googleId = $_SESSION['google_id'] ?? null;

$user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
$stmt = $conn->prepare($user_id_sql);
$stmt->execute([':gid' => $googleId]);

$user_id = $stmt->fetchColumn();
 


$data = json_decode(file_get_contents('php://input'), true); //reads incoming json data
$answer_id = $data['answer_id'] ?? null; //assigns null value if answer_id not found, same with stage_num
$stage_num = $data['stage_num'] ?? null;

if (!$answer_id) {
    echo json_encode(['correct' => false]); //checks if answer id exists.
    exit;
}

// checks if answer is correct
$stmt = $conn->prepare("SELECT is_correct FROM module_stage_questions_answers WHERE id = ?");
$stmt->execute([$answer_id]);
$is_correct = $stmt->fetchColumn();

if ($is_correct !== false) { //checks if $is_correct is empty
    $insert = $conn->prepare("
        INSERT INTO module_stage_questions_user_answers (uid, msqaid) 
        VALUES (?, ?)
    ");
    $insert->execute([$user_id, $answer_id]);
}

// return correctness
echo json_encode(['correct' => $is_correct == 1]);
?>