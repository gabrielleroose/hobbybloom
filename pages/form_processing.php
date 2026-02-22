<?php

session_start();
require_once 'db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/twig.php';

//get user's Google ID from session

$googleId = $_SESSION['google_id'] ?? null;

//check if they're logged in. if not, return to index.php
if (!$googleId) {
    header('Location: index.php');
    exit;
}

$pdo->beginTransaction();

$user_id_sql = "SELECT id FROM users WHERE google_id = :gid";

$stmt = $pdo->prepare($user_id_sql);
$stmt->execute([':gid' => $googleId]);

$user_id = $stmt->fetchColumn();




$module_name = htmlspecialchars($_POST['name'] ?? []);
$cid = $user_id;
$rating = htmlspecialchars($_POST['rating'] ?? []);
$exp_level = htmlspecialchars($_POST['username'] ?? []);
$mod_description = htmlspecialchars($_POST['description'] ?? []);
$num_lessons = ($_POST['stage_num'] ?? []);
$est_comp_time = htmlspecialchars($_POST['estimate'] ?? []);
$notes = htmlspecialchars($_POST['notes'] ?? []);

$module_insert_sql = "INSERT INTO module (name, cid, description, rating, exp_level, num_lessons, est_comp_time, notes)
                        VALUES (:module_name, :cid, :mod_description, :rating, :exp_level, :num_lessons, :est_comp_time, :notes)";

$stmt = $pdo->prepare($module_insert_sql);
$stmt->execute([':module_name' => $module_name, ':cid' => $cid, ':mod_description'=>$mod_description, ':rating'=>$rating, ':exp_level'=>$exp_level, ":num_lessons"=>$num_lessons, ':est_comp_time'=>$est_comp_time, ':notes'=>$notes]);









foreach ($_POST['stages'] as $stage_num => $stage_data) {

    
    $correct_answer = null;
    $false_answers = [];


    foreach ($stage_data['question'] as $question) {
        $stmt = $pdo->prepare("INSERT INTO module_stage_questions (msid, question_text, order_num) VALUES ($msid, $question, $stage_num)");
    }



    foreach ($stage_data['answers'] as $answer) {

        $text = $answer['text'];
        $is_correct = $answer['is_correct']; //checks hidden input value of answersHTML div. 

        if ($is_correct == 1) {
            $correct_answer = $text; //assigns $text of answer to one of two things: a $correctAns variable, or an array of the false answers.
        } else {
            $false_answers[] = $text;
                }

            }

    foreach ($false_answers as $false_answer) {

        $stmt = $pdo->prepare("INSERT INTO module_stage_questions_answers (msqid, answer, is_correct, ans_num) VALUES (?, ?, 0, ?)");
        $stmt->execute([question id, $false_answer, ]);

}
        }
    
?>