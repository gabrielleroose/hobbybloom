<?php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/twig.php';

// get user's google_id
$googleId = $_SESSION['google_id'] ?? null;

// check if they're logged in
if (!$googleId) {
    header('Location: index.php');
    exit;
}

try {
    $conn->beginTransaction();

    // get user_id
    $user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
    $stmt = $conn->prepare($user_id_sql);
    $stmt->execute([':gid' => $googleId]);
    $user_id = $stmt->fetchColumn();

    if (!$user_id) {
        throw new Exception("User not found in database.");
    }

    //grab form data
    $module_name   = $_POST['name'] ?? null;
    $cid           = (int) $user_id;
    $exp_level     = $_POST['exp_level'] ?? null;
    $mod_description = $_POST['description'] ?? null;
    $num_lessons = !empty($_POST['stages']) ? (int)($_POST['stage_num']) : 0;
    $est_comp_time   = $_POST['estimate'] ?? null;
    $notes           = $_POST['notes'] ?? null;

    $module_id = isset($_POST['module_id']) ? (int)$_POST['module_id'] : null;
    $is_edit = $module_id > 0;

    if (!empty($_POST['videos'])) { //different implementation from videos unique video filtering seen on module.php. this handles the issue of duplicates before they even reach the db, also ensures they're the correct format
    $module_videos = $_POST['videos'] ?? []; // video array
    $module_videos = array_filter(array_map('trim', $module_videos));
    $unique_module_videos = array_unique($module_videos);
    
}
        
    

    if ($is_edit) {
    // update module info
    $delete_sql = "DELETE FROM module_stage WHERE mid = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->execute([$module_id]);

    if (!empty($_POST['stages'])) {
        foreach ($_POST['stages'] as $stage_num => $stage_data) {
            // insert stage
            $stmt = $conn->prepare("INSERT INTO module_stage (mid, stage_num, title) VALUES (:mid, :stage_num, :title)");
            $stmt->execute([
                ':mid' => $module_id,
                ':stage_num' => $stage_num,
                ':title' => $stage_data['title']
            ]);
            $msid = $conn->lastInsertId();

            // insert video if exists
            $video_url = $stage_data['video_url'] ?? null;
            if (!empty($video_url)) {
                $stmt = $conn->prepare("INSERT INTO module_stage_videos (msid, video_url, lesson_number) VALUES (?, ?, ?)");
                $stmt->execute([$msid, $video_url, $stage_num]);
            }

            // insert question
            $question = $stage_data['question'] ?? '';
            $stmt = $conn->prepare("INSERT INTO module_stage_questions (msid, question_text, order_num) VALUES (?, ?, ?)");
            $stmt->execute([$msid, $question, $stage_num]);
            $msqid = $conn->lastInsertId();

            // insert answers
            $ans_num = 1;
            foreach ($stage_data['answers'] as $answer) {
                $stmt = $conn->prepare("INSERT INTO module_stage_questions_answers (msqid, answer, is_correct, ans_num) VALUES (?, ?, ?, ?)");
                $stmt->execute([$msqid, $answer['text'], $answer['is_correct'], $ans_num]);
                $ans_num++;
            }
        }
    }
}   else {

        $module_insert_sql = " 
                INSERT INTO module 
                (name, cid, description, exp_level, num_lessons, est_comp_time, notes)
                VALUES 
                (:module_name, :cid, :mod_description, :exp_level, :num_lessons, :est_comp_time, :notes)
            ";

        $stmt = $conn->prepare($module_insert_sql);
        $stmt->execute([
            ':module_name'   => $module_name,
            ':cid'           => $cid,
            ':mod_description' => $mod_description,
            ':exp_level'    => $exp_level,
            ':num_lessons'  => $num_lessons,
            ':est_comp_time'=> $est_comp_time,
            ':notes'        => $notes 
        ]);

        
    

    $mid = $conn->lastInsertId();
    
    foreach ($unique_module_videos as $index=>$unique_url) {

        if ($unique_url && preg_match('/(youtu\.be\/|v=|shorts\/)([A-Za-z0-9_-]+)/', $unique_url, $matches)) {
            $video_id = $matches[2];
            $video_id = preg_replace('/[^A-Za-z0-9_-]/', '', $matches[2]);
            $embed = "https://www.youtube.com/embed/" . $video_id;
            $insert_video_url_sql = "INSERT INTO module_stage_videos (video_url, mid) VALUES (?, ?)";

            $stmt = $conn->prepare($insert_video_url_sql);
            $stmt = $stmt->execute([$embed, $mid]);

                        }
            }


    if (!empty($_POST['stages'])) {
        foreach ($_POST['stages'] as $stage_num => $stage_data) {
            // insert stage
            $module_stages_insert_sql = "
                INSERT INTO module_stage (mid, stage_num, title)
                VALUES (:mid, :stage_num, :title)
            ";
            $stmt = $conn->prepare($module_stages_insert_sql);
            $stmt->execute([
                ':mid'       => $mid,
                ':stage_num' => $stage_num,
                ':title'     => $stage_data['title']
            ]);
            $msid = $conn->lastInsertId();

            $video_url = $stage_data['video_url'] ?? null;

            if (!empty($video_url)) {

                $video_sql = "
                    INSERT INTO module_stage_videos (msid, video_url, lesson_number)
                    VALUES (?, ?, ?)
                ";

                $stmt = $conn->prepare($video_sql);
                $stmt->execute([$msid, $video_url, $stage_num]);

            }

            // insert question
            $question = $stage_data['question'] ?? '';

            $module_stage_question_sql = "
                INSERT INTO module_stage_questions 
                (msid, question_text, order_num)
                VALUES (?, ?, ?)
            ";
            $stmt = $conn->prepare($module_stage_question_sql);
            $stmt->execute([$msid, $question, $stage_num]);
            $msqid = $conn->lastInsertId();

            // insert answers
            $ans_num = 1;

            foreach ($stage_data['answers'] as $answer) { 

                $stmt = $conn->prepare("
                    INSERT INTO module_stage_questions_answers
                    (msqid, answer, is_correct, ans_num)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $msqid,
                    $answer['text'],
                    $answer['is_correct'],
                    $ans_num
                ]);
                $ans_num++;
            }
        }
    }
}

 




    $conn->commit();
    header("Location: modules_display.php");
    exit;
    
//end try {}, begin catch{}
} catch (Exception $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}