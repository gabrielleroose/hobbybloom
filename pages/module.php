<?php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

$module_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM modules
    WHERE id = :id
");
$stmt->execute([":id" => $module_id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT *
    FROM module_videos
    WHERE module_id = :id
    ORDER BY lesson_number
");
$stmt->execute([":id" => $module_id]);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beginner Cooking</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>

<body class="module-body">

    <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1); //debugging/error messages
    error_reporting(E_ALL);

    session_start(); // NOTE: session_start(); allows access to $_SESSION variable, which can store data persistantly across pages.
    require_once __DIR__ . '/../vendor/autoload.php';

    require_once __DIR__ . '/../config/db.php'; //necessary to connect to db.

    require_once __DIR__ . '/../config/twig.php'; //necessary to load twig
    include 'base.php';

    $googleId = $_SESSION['google_id'] ?? null;

// check if they're logged in
if (!$googleId) {
    header('Location: index.php');
    exit;
}

try {

    $conn->beginTransaction();


    $user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
    $stmt = $conn->prepare($user_id_sql);
    $stmt->execute([':gid' => $googleId]);

    $user_id = $stmt->fetchColumn();

    if (!$user_id) {
        throw new Exception("User not found in database.");
    }

    // get mod_id
    $mod_id = $_POST['module_id'];

    $mod_stage_sql = "SELECT ms.id FROM module_stage AS ms JOIN module AS m ON ms.mid = m.id WHERE ms.mid = :mid";
    $stmt = $conn->prepare($mod_stage_sql);
    $stmt->execute(['mid' => $mod_id]);
    
    $mod_stage_ids = $stmt->fetchAll(); //gets an array of all module stage nums where module_stage.module_id = module.id, ensuring user receives relevant info.
     
    $module_stage_info = [];

    foreach ($mod_stage_ids as $stage) {
            $stage_id = $stage['id'];

        $module_stage_questions_sql = "SELECT msq.id, msq.question_text FROM module_stage_questions AS msq JOIN module_stage AS ms ON msq.msid = ms.id WHERE msq.msid = ?";
        $stmt = $conn->prepare($module_stage_questions_sql);
        $stmt->execute([$stage_id]);
        $module_stage_questions = $stmt->fetchAll();
        

        foreach($module_stage_questions as $question){

            $question_id = $question['id'];  // question id
            $question_text = $question['question_text']; //question text

            $module_stage_questions_answers_sql = "SELECT answer, is_correct from module_stage_questions_answers AS msqia JOIN module_stage_questions AS msq ON msqia.msqid = msq.id WHERE msqia.msqid = ?";
            $stmt = $conn->prepare($module_stage_questions_answers_sql);
            $stmt->execute([$question_id]);
            
            $module_stage_answers = $stmt->fetchAll();

            foreach ($module_stage_answers as $answer) {

                $module_stage_info[$stage_id]['questions'][$question_id]['answers'][] = 
                ['answer' => $answer['answer'], 
                'is_correct' => $answer['is_correct']];
           
            }
        }
    }
    
    echo '<pre>'; 
    print_r($module_stage_info);
    echo '</pre>';

    $conn->commit();
    

} catch (Exception $e) {

    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}


    
?>