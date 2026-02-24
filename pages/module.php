<?php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/twig.php';

// get user's google_id
$googleId = $_SESSION['google_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module Questions</title>
    <link rel="stylesheet" href="../css/style.css">
    
</head>
<body>

<?php

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

    $mod_stage_sql = "SELECT ms.id, ms.title, ms.stage_num FROM module_stage AS ms JOIN module AS m ON ms.mid = m.id WHERE ms.mid = :mid";
    $stmt = $conn->prepare($mod_stage_sql);
    $stmt->execute(['mid' => $mod_id]);
    
    $mod_stages = $stmt->fetchAll(); //gets an array of all module stage nums where module_stage.module_id = module.id, ensuring user receives relevant info
     
    $module_stage_info = [];


    
    foreach ($mod_stages as $stage) {
        $stage_id = $stage['id'];
        $module_stage_questions_sql = "SELECT msq.id, msq.question_text FROM module_stage_questions AS msq JOIN module_stage AS ms ON msq.msid = ms.id WHERE msq.msid = ?";
        $stmt = $conn->prepare($module_stage_questions_sql);
        $stmt->execute([$stage_id]);
        $module_stage_questions = $stmt->fetchAll(); //grabs all selected info, arranges into array as seen in mod_id statements
        
       $hidden = ($stage['stage_num'] == 1) ? "" : "hidden"; //if stage['stage_num' == 1, set $hidden to "". otherwise, set to "hidden"]
        echo "<div class='stage $hidden' id='stage_" . $stage['stage_num'] . "'>";
        
        echo "<div class='stage_title'>";
        echo $stage['title'];
        echo "</div><br><br>";
        
        
           

        foreach($module_stage_questions as $question){

            $question_id = $question['id'];  // question id
            $question_text = $question['question_text']; //question text

            $module_stage_questions_answers_sql = "SELECT msqa.id, answer, is_correct from module_stage_questions_answers AS msqa JOIN module_stage_questions AS msq ON msqa.msqid = msq.id WHERE msqa.msqid = ?";
            $stmt = $conn->prepare($module_stage_questions_answers_sql);
            $stmt->execute([$question_id]);
            
            $module_stage_answers = $stmt->fetchAll(); 
            echo $question['question_text'];

            foreach ($module_stage_answers as $answer) {

                $module_stage_info[$stage_id]['questions'][$question_id]['answers'][] = 
                ['answer' => $answer['answer'], 
                'is_correct' => $answer['is_correct']];


                echo "<div class='module_answer'>";
                echo "<input type='radio' name='question_" . $question_id . "' id='answer_" . $answer['id'] . "' value='" . $answer['id'] . "'>";
                echo "<label for='answer_" . $answer['id'] . "'>" . htmlspecialchars($answer['answer']) . "</label>";
                echo "</div>"; 
                   
           
            }
           echo "<button class='submit-stage' data-stage='" . $stage['stage_num'] . "'>Submit</button>";
        }
        echo "</div>";
    }
    
    $conn->commit();
    

} catch (Exception $e) {

    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

?>

<script src="../js/module.js"></script>

</body>
</html>