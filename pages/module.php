<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/twig.php';

require_once __DIR__ . '/base.php';

$googleId = $_SESSION['google_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en" class="begin-module-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Begin Module | HobbyBloom</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>
<body class="begin-module-body">
<div class = "begin-module-page">
<div class="begin-module-container">
    <?php
    if (!$googleId) {
        header('Location: index.php');
        exit;
    }

    try {
        $user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
        $stmt = $conn->prepare($user_id_sql);
        $stmt->execute([':gid' => $googleId]);
        $user_id = $stmt->fetchColumn();

        if (!$user_id) {
            throw new Exception("User session not found. Please log in again.");
        }

        $mod_id = $_REQUEST['module_id'] ?? null;
        if (!$mod_id) {
            throw new Exception("No module selected.");
        }

        $mod_stage_sql = "SELECT id, title, stage_num FROM module_stage WHERE mid = :mid ORDER BY stage_num ASC";
        $stmt = $conn->prepare($mod_stage_sql);
        $stmt->execute(['mid' => $mod_id]);
        $mod_stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($mod_stages as $stage) {
            $stage_id = $stage['id'];
            
            $q_sql = "SELECT id, question_text FROM module_stage_questions WHERE msid = ?";
            $q_stmt = $conn->prepare($q_sql);
            $q_stmt->execute([$stage_id]);
            $questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $hidden = ($stage['stage_num'] == 1) ? "" : "hidden";
            echo "<div class='stage $hidden' id='stage_" . $stage['stage_num'] . "'>";
            
            echo "<div class='stage_title'>";
            echo $stage['title'];
            echo "<br><br>";
            
            $video_url = $stage['video_url'];

            //extracts id, attempts to discern common youtube patterns.
            if (preg_match('/(youtu\.be\/|v=|shorts\/)([A-Za-z0-9_-]+)/', $video_url, $matches)) {
                $video_id = $matches[2];
                $video_url = "https://www.youtube.com/embed/" . $video_id;
                }
            echo '<iframe width="560" height="315"
            src="' . htmlspecialchars($video_url) . '"
            title="YouTube video player"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen>
            </iframe>';

            echo "</div><br><br>";
            
            
            

            foreach($questions as $question) {
                $question_id = $question['id'];
                echo "<p><strong>Question:</strong> " . htmlspecialchars($question['question_text']) . "</p>";

                $a_sql = "SELECT id, answer, is_correct FROM module_stage_questions_answers WHERE msqid = ?";
                $a_stmt = $conn->prepare($a_sql);
                $a_stmt->execute([$question_id]);
                $answers = $a_stmt->fetchAll(PDO::FETCH_ASSOC); 

                shuffle($answers);

                echo "<div class='answers-list'>";
                foreach ($answers as $answer) {
                    echo "<div class='module_answer'>";
                    echo "<input type='radio' name='question_" . $question_id . "' id='answer_" . $answer['id'] . "' value='" . $answer['id'] . "'>";
                    echo "<label for='answer_" . $answer['id'] . "'>" . htmlspecialchars($answer['answer']) . "</label>";
                    echo "</div>"; 
                }
                echo "</div>";
                echo "<br><button class='submit-stage' data-stage='" . $stage['stage_num'] . "'>Submit Answer</button>";
            }
            echo "</div>";
        }

    } catch (Exception $e) {
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
    ?>

    <?php if (isset($user_id)): ?>
    <button id="reportModuleBtn" 
        style="margin-top: 30px; background:#ff4d4d; color:white; border:none; padding:12px 24px; border-radius:20px; cursor:pointer; font-weight:bold;">
        Report This Module
    </button>
    <?php endif; ?>
</div>

</div>


    <?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="../js/module.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const reportBtn = document.getElementById('reportModuleBtn');
        if (!reportBtn) return;

        reportBtn.addEventListener('click', function() {
            const reason = prompt("Why are you reporting this module?");
            if (!reason || reason.trim() === "") return;

            fetch('submit_report.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({
                    type: 'module',
                    item_id: <?= json_encode($mod_id) ?>,
                    reason: reason.trim()
                })
            })
            .then(res => res.json())
            .then(data => alert(data.message || "Report submitted."))
            .catch(err => alert("Error submitting report."));
        });
    });
</script>
</body>
</html>