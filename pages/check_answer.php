<?php
require 'db.php';
header('Content-Type: application/json');

session_start();
$googleId = $_SESSION['google_id'] ?? null;

$user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
$stmt = $conn->prepare($user_id_sql);
$stmt->execute([':gid' => $googleId]);

$user_id = $stmt->fetchColumn();
 


$data = json_decode(file_get_contents('php://input'), true) ?? []; //reads incoming json data
$answer_id = isset($data['answer_id']) ? (int)$data['answer_id'] : null;
$stage_num = isset($data['stage_num']) ? (int)$data['stage_num'] : null;
$module_id = isset($data['module_id']) ? (int)$data['module_id'] : null;
$no_quiz = $data['no_quiz'] ?? false;

$is_final_stage = $data['is_final_stage'] ?? false;


if ($no_quiz && $module_id) {

    $init_sql = "INSERT IGNORE INTO module_user_completion (mid, uid, is_complete)
                 VALUES (?, ?, 0)";
    $init = $conn->prepare($init_sql);
    $init->execute([$module_id, $user_id]);

    $complete_sql = "
        UPDATE module_user_completion
        SET is_complete = 1
        WHERE uid = ? AND mid = ?
    ";

    $complete = $conn->prepare($complete_sql);
    $complete->execute([$user_id, $module_id]);

    echo json_encode([
        'correct' => true,
        'completed' => true
    ]);
    exit;
}

if (!$answer_id && !$no_quiz) {
    echo json_encode(['correct' => false]); //checks if answer id exists.
    exit;
}

// checks if answer is correct
$is_correct_sql = "SELECT is_correct FROM module_stage_questions_answers WHERE id = ?";
$stmt = $conn->prepare($is_correct_sql);
$stmt->execute([$answer_id]);
$is_correct = $stmt->fetchColumn();

$correct = ($is_correct == 1);
$completed = false;

if ($correct && $module_id) {

    $stmt = $conn->prepare("
        SELECT MAX(stage_num) 
        FROM module_stage 
        WHERE mid = ?
    ");

    $stmt->execute([$module_id]);
    $last_stage = (int) $stmt->fetchColumn();

    if ($stage_num == $last_stage) {

        $init_sql = "INSERT IGNORE INTO module_user_completion (mid, uid, is_complete) VALUES (?, ?, 0)";
        $init = $conn->prepare($init_sql);
        $init->execute([$module_id, $user_id]);

        $complete_sql = "
        UPDATE module_user_completion
        SET is_complete = 1
        WHERE uid = ? AND mid = ?
    ";

        $complete = $conn->prepare($complete_sql);
        $complete->execute([$user_id, $module_id]);

        $completed = true;
    }
}



// return correctness
echo json_encode(['correct' => $correct, 'completed' => $completed ]);