<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

require_once 'db.php';


$success = false;
$error = "";

$googleId = $_SESSION['google_id'] ?? null;

if (!$googleId) {                   //checking if google id present, sending back to index.php if not.
    header('Location: index.php');
    exit;
}

// form submission handling below 
//strtolower used on xpLevel to fit DB constraints (actually need to update db constraints such that CHECK xpLevel in ["beginner", "intermediate", "expert"]
// if ($_SERVER["REQUEST_METHOD"] === "POST") {

//     $name = trim($_POST["name"]);
//     $description = trim($_POST["description"]);
//     $NumOfLessons = $_POST["videoCount"] ?? 0;
//     $notes = $_POST["notes"] ?? "";
//     $xpLevel = strtolower($_POST["xpLevel"]) ?? "";
//     $compTime = $_POST["estimate"] ?? 0;


//     if (empty($name)) {
//         $error = "Module name is required.";
//     } else {
//         try {
//             $pdo = new PDO(
//                 "mysql:host=$host;dbname=$dbname;charset=utf8",
//                 $username,
//                 $password
//             );
//             $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//             $sql = "INSERT INTO module
//             (name, description, created_by, number_of_lessons, notes, xpLevel, compTime)
//             VALUES
//             (:name, :description, :created_by, :Number_of_lessons, :notes, :xpLevel, :compTime)";


//             $stmt = $pdo->prepare($sql);
//             $stmt->execute([
//                 ":name" => $name,
//                 ":description" => $description,
//                 ":created_by" => $_SESSION['user_id'],
//                 ":Number_of_lessons" => $NumOfLessons,
//                 ":notes" => $notes,
//                 ":xpLevel" => $xpLevel,
//                 ":compTime" => $compTime,
//             ]);


//     $module_id = $pdo->lastInsertId();




//     if (!empty($_POST['videos'])) {

//         $videoSQL = "
//             INSERT INTO module_videos
//             (module_id, video_url, lesson_number)
//             VALUES (?, ?, ?)
//         ";

//         $videoStmt = $pdo->prepare($videoSQL);

//         foreach ($_POST['videos'] as $index => $url) {

//             $url = trim($url);
//             if ($url !== "") {
//                 $videoStmt->execute([
//                     $module_id,
//                     $url,
//                     $index + 1
//                 ]);
//             }
//         }
//     }


//             $success = true;

//         } catch (PDOException $e) {
//             $error = "Something went wrong saving the module.";
//         }
//     }
// }

$user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
$stmt = $conn->prepare($user_id_sql);
$stmt->execute([':gid' => $googleId]);

$user_id = $stmt->fetchColumn();

$module_id = NULL; //null until changed a few lines below.
if (isset($_GET['module_edit'])) {

    $module_id =  (int) $_GET['module_edit']; //changes global variable so we can set value of edit button, pull it in to form_processing.php

    $module_info_sql = "SELECT m.id, m.cid, m.name, m.description, m.rating, m.exp_level, m.num_lessons, m.est_comp_time, msp.msid FROM module as m
        LEFT JOIN module_stage_progress AS msp ON msp.mid = m.id
        WHERE m.id = ? AND m.cid = ?";
    $stmt = $conn->prepare($module_info_sql);
    $stmt->execute([$module_id, $user_id]);
    $module_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$module_info) {
        header("Location: dashboard.php");
        exit("Invalid Module Information.");
       
    }


    $module_stage_info = "SELECT ms.id, ms.stage_num, ms.title, msv.video_url FROM module_stage AS ms 
    JOIN module AS m ON ms.mid = m.id 
    LEFT JOIN module_stage_videos AS msv ON ms.id = msv.msid 
    WHERE ms.mid = ?";

    $stmt = $conn->prepare($module_stage_info);
    $stmt->execute([$module_id]);
    $module_stage_info = $stmt->fetchAll();
    

    $module_stage_questions_info = []; //initialize array outside of loop, stops from data being overwritten
    foreach ($module_stage_info as $stage) {

        $module_stage_questions_sql = "SELECT msq.id, msq.question_text, msq.order_num FROM module_stage_questions AS msq 
        JOIN module_stage AS ms ON msq.msid = ms.id 
        WHERE msq.msid = ?";

        $stmt = $conn->prepare($module_stage_questions_sql);
        $stmt->execute([$stage['id']]);
        $module_stage_questions_results = $stmt->fetchAll();

        $module_stage_questions_info[] = [
            'id' => $stage['id'],
            'stage_num' => $stage['stage_num'],
            'title' => $stage['title'],
            'question' => $module_stage_questions_results,
            'video_url' => $stage['video_url']
        ];
    }


    foreach ($module_stage_questions_info as &$stage) {
        foreach ($stage['question'] as &$question) {
            $module_stage_questions_answers_sql = "SELECT msqa.id, msqa.answer, msqa.is_correct, msqa.ans_num FROM module_stage_questions_answers AS msqa 
            JOIN module_stage_questions AS msq ON msqa.msqid = msq.id 
            WHERE msqa.msqid = ?";

            $stmt = $conn->prepare($module_stage_questions_answers_sql);
            $stmt->execute([$question['id']]);
            $module_stage_questions_answers_results = $stmt->fetchAll();


            $question['answers'] = $module_stage_questions_answers_results;
            
                $user_answers_sql = "SELECT msqua.id, msqua.msqaid FROM module_stage_questions_user_answers AS msqua WHERE msqua.uid = ? AND msqua.msqaid IN 
                                        (SELECT msqa.id FROM module_stage_questions_answers AS msqa WHERE msqa.msqid = ?);
                    ";
                    $stmt = $conn->prepare($user_answers_sql);
                    $stmt->execute([$user_id, $question['id']]);
                    $question['user_answers'] = $stmt->fetchAll();

        }
    }

}

$circleId = $_GET['circle_id'] ?? null;


$user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
$stmt = $conn->prepare($user_id_sql);
$stmt->execute([':gid' => $googleId]);

$user_id = $stmt->fetchColumn();

if (isset($_POST['module_edit'])) {

    $module_id = $_POST['module_edit'];
    $module_info_sql = "SELECT m.id, m.cid, m.name, m.description, m.rating, m.exp_level, m.num_lessons, msp.msid FROM module as m
        LEFT JOIN module_stage_progress AS msp ON msp.mid = m.id
        WHERE m.id = ?";
    $stmt = $conn->prepare($module_info_sql);
    $stmt->execute([$module_id]);
    $module_info = $stmt->fetch(PDO::FETCH_ASSOC);





    $module_stage_info = "SELECT ms.id, ms.stage_num, ms.title FROM module_stage AS ms JOIN module AS m ON ms.mid = m.id WHERE ms.mid = ?";
    $stmt = $conn->prepare($module_stage_info);
    $stmt->execute([$module_id]);
    $module_stage_info = $stmt->fetchAll();

    $module_stage_questions_info = []; //initialize array outside of loop, stops from data being overwritten
    foreach ($module_stage_info as $stage) {

        $module_stage_questions_sql = "SELECT msq.id, msq.question_text, msq.order_num FROM module_stage_questions AS msq JOIN module_stage AS ms ON msq.msid = ms.id WHERE msq.msid = ?";
        $stmt = $conn->prepare($module_stage_questions_sql);
        $stmt->execute([$stage['id']]);
        $module_stage_questions_results = $stmt->fetchAll();

        $module_stage_questions_info[] = [
            'id' => $stage['id'],
            'stage_num' => $stage['stage_num'],
            'title' => $stage['title'],
            'question' => $module_stage_questions_results
        ];
    }


    foreach ($module_stage_questions_info as &$stage) {
        foreach ($stage['question'] as &$question) {
            $module_stage_questions_answers_sql = "SELECT msqa.id, msqa.answer, msqa.is_correct, msqa.ans_num FROM module_stage_questions_answers AS msqa JOIN module_stage_questions AS msq ON msqa.msqid = msq.id WHERE msqa.msqid = ?";
            $stmt = $conn->prepare($module_stage_questions_answers_sql);
            $stmt->execute([$question['id']]);
            $module_stage_questions_answers_results = $stmt->fetchAll();


            $question['answers'] = $module_stage_questions_answers_results;

//             $user_answers_sql = "SELECT msqua.id, msqua.msqaid FROM module_stage_questions_user_answers AS msqua WHERE msqua.uid = ? AND msqua.msqaid IN 
//                                     (SELECT msqa.id FROM module_stage_questions_answers AS msqa WHERE msqa.msqid = ?);
// ";
//             $stmt = $conn->prepare($user_answers_sql);
//             $stmt->execute([$user_id, $question['id']]);
//             $question['user_answers'] = $stmt->fetchAll();


        }
    }




}


?>

<form action="save_module.php" method="POST">
    <input type="hidden" name="circle_id" value="<?= htmlspecialchars($circleId) ?>">
</form>


<form action="save_module.php" method="POST">
    <input type="hidden" name="circle_id" value="<?= htmlspecialchars($circleId) ?>">
</form>


<!DOCTYPE html>
<html>

<head>
    <title>Create Module</title>
    <meta charset="UTF-8">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>

<body class="create-body">
    <?php include 'base.php'; ?>

    <div class="create-module-main">

        <h2>Create a Module</h2>

        <?php if (!empty($error)): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p style="color:green;">Module created successfully!</p>
        <?php endif; ?>

        <form method="POST" action="form_processing.php" enctype="multipart/form-data">
            <label>Module Name:</label><br>
            <input type="text" name="name" 
            value="<?= htmlspecialchars($module_info['name'] ?? '') ?>"
            required><br><br>

            <label>Description:</label><br>
            <textarea name="description" rows="4" cols="40"
            value="<?= htmlspecialchars($module_info['description'] ?? '') ?>"></textarea><br><br>

            <label>Number of videos:</label><br>
            <input type="number" id="videoCount" name="videoCount" min="0" max="5" onchange="generateVideoInputs()">

            <div id="videoInputs"></div><br>

            <div>
                <label>Number of lessons:</label><br>
                <input type="number" id="stage_num" name="stage_num" min="0" max="5">
            </div>

            <div id="stagesContainer"></div> <!-- this is where the contents of the javascript below are loaded. -->
            
                <!-- THIS PHP DYNAMICALLY GENERATES THE USER'S STAGES IF EDITING FORM. OTHEWRWISE, THE <div> ABOVE IS USED FOR A NEW FORM -->
            <?php if (!empty($module_stage_questions_info)): ?>
                <?php foreach ($module_stage_questions_info as $stageIndex => $stage): ?>
                    <div class="stage-block">
                        <h3>Stage <?= $stage['stage_num'] ?></h3>
                        <label>Stage Title:</label>
                        <input type="text" name="stages[<?= $stage['stage_num'] ?>][title]" 
                            value="<?= htmlspecialchars($stage['title']) ?>" required><br><br>

                        <?php foreach ($stage['question'] as $questionIndex => $question): ?>
                            <div class="question-block">
                                <label>Question:</label>
                                <input type="text" name="stages[<?= $stage['stage_num'] ?>][question]" 
                                    value="<?= htmlspecialchars($question['question_text']) ?>" required><br><br>

                                <?php foreach ($question['answers'] as $answer): ?>
                                    <input type="text" name="stages[<?= $stage['stage_num'] ?>][answers][<?= $answer['ans_num'] ?>][text]" 
                                        value="<?= htmlspecialchars($answer['answer']) ?>" required>

                                    <input type="hidden" name="stages[<?= $stage['stage_num'] ?>][answers][<?= $answer['ans_num'] ?>][is_correct]" 
                                        value="<?= $answer['is_correct'] ?>"><br>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            


            <label>Notes:</label><br>
            <textarea name="notes" rows="4" cols="40"></textarea><br><br>

            <label>Recomended experience level:</label><br>
            <select id="exp_level" name="exp_level">
                <option value="beginner" <?= (isset($module_info['exp_level']) && $module_info['exp_level'] === 'beginner') ? 'selected' : '' ?>>Beginner</option> 
                <option value="intermediate" <?= (isset($module_info['exp_level']) && $module_info['exp_level'] === 'intermediate') ? 'selected' : '' ?>>Intermediate</option>
                <option value="expert" <?= (isset($module_info['exp_level']) && $module_info['exp_level'] === 'expert') ? 'selected' : '' ?>>Expert</option>
            </select>
            <br><br>


            <label>Estimated time of completion:</label>
            <!-- maybe do a set time format if possible -->
            <label for="estimate">Estimated time (minutes):</label>
            <input type="number" id="estimate" name="estimate" min="1"  value="<?= htmlspecialchars($module_info['test_comp_time'] ?? '') ?>" required>

            <p id="formattedOutput"></p>

            <?php if (!$module_id): ?>
                <button type="submit" name="create_module">Create Module</button>
            <?php else: ?>
                <input type="hidden" name="module_id" value="<?= htmlspecialchars($module_id) ?>">
                <button type="submit" name="edit_module">Confirm Module Changes</button>
            <?php endif ?>
            
            </div>



            
        </form>
    </div>

</body>
<?php include __DIR__ . '/../includes/footer.php'; ?>

</html>

<script>
    function generateVideoInputs() {
        const count = document.getElementById("videoCount").value;
        const container = document.getElementById("videoInputs");

        // clear existing inputs
        container.innerHTML = "";

        for (let i = 1; i <= count; i++) {
            const input = document.createElement("input");
            input.type = "url";
            input.name = "videos[]";
            input.placeholder = "Video " + i + " link";
            input.required = true;

            container.appendChild(input);
            container.appendChild(document.createElement("br"));
        }
    }
</script>


<script>
    document.getElementById("estimate").addEventListener("input", function () {
        const minutes = parseInt(this.value, 10);
        const output = document.getElementById("formattedOutput");

        if (isNaN(minutes) || minutes <= 0) {
            output.textContent = "";
            return;
        }

        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;

        let formatted = "";
        if (hours > 0) formatted += `${hours} hour${hours !== 1 ? "s" : ""} `;
        if (remainingMinutes > 0) formatted += `${remainingMinutes} minute${remainingMinutes !== 1 ? "s" : ""}`;

        output.textContent = `Estimated time: ${formatted}`;
    });
</script>

<script>
    const enableStages = document.getElementById("enable_stages");
    const stageSection = document.getElementById("stages_section");

        const stageSelect = document.getElementById("stage_num");
        const stagesContainer = document.getElementById("stagesContainer");


        stageSelect.addEventListener("input", function () {
            const stageCount = this.valueAsNumber;

            stagesContainer.innerHTML = ""; //wipes data on change. 

            if (isNaN(stageCount) || stageCount < 0 || stageCount > 5) { //checks if stageCount is a number, below 0, or above 5 (limit).
                stagesContainer.innerHTML = "";
                return;
            }




            for (let i = 1; i <= stageCount; i++) { //begin 1st for loop

                const stageDiv = document.createElement("div"); //loops through values from i=1 to stageCount.

                let answersHTML = "";

                // second inner loop specifically for answers, since there's 4 multiple choice answers per question.
                for (let a = 1; a <= 3; a++) { //necessary to add hidden input type to track correct answer 
                    answersHTML += `
        <input type="text"
               class="stage_questions_false"
               name="stages[${i}][answers][${a}][text]"
               placeholder="False Answer ${a}" required>

        <input type="hidden" 
               name="stages[${i}][answers][${a}][is_correct]"
               value="0">

        <br>
    `;
                }

                // correct answer (note the [4] index to indicate position of correct input. gonna have to use a function to randomize the order of questions on module.php)
                answersHTML += `
    <input type="text"
           class="stage_questions_correct"
           name="stages[${i}][answers][4][text]"
           placeholder="Correct Answer" required>

<script>
    document.getElementById("estimate").addEventListener("input", function () { 
        const minutes = parseInt(this.value, 10);
        const output = document.getElementById("formattedOutput");

        if (isNaN(minutes) || minutes <= 0) {
            output.textContent = "";
            return;
        }

        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;

        let formatted = "";
        if (hours > 0) formatted += `${hours} hour${hours !== 1 ? "s" : ""} `;
        if (remainingMinutes > 0) formatted += `${remainingMinutes} minute${remainingMinutes !== 1 ? "s" : ""}`;

        output.textContent = `Estimated time: ${formatted}`;
    });

</script>

<script>
   document.addEventListener("DOMContentLoaded", function () {

    const stageSelect = document.getElementById("stage_num");
    const stagesContainer = document.getElementById("stages_container"); 

    function generateStages(stageCount, data = null) {

        stagesContainer.innerHTML = "";

        for (let i = 1; i <= stageCount; i++) {

            const stageData = data ? data[i - 1] : null; //necessary to subtract 1 because I formed the loop weird
            const questionData = stageData?.question?.[0] ?? null;
            const answersData = questionData?.answers ?? []; //effectively the same way you'd access mod_stage_question_info[question][answers]

            let answersHTML = ""; 

            for (let a = 1; a <= 4; a++) { 

                const answerObj = answersData[a - 1] ?? null; 

                const answerText = answerObj ? answerObj.answer : ""; //mod_stage_question_info[question][answers][answer]
                const isCorrect = answerObj ? answerObj.is_correct : (a === 4 ? 1 : 0);


                //shortened from previous iteration; now handles correct and false answer values within one <input> tag
                answersHTML += `
                    <input type="text"
                        name="stages[${i}][answers][${a}][text]"
                        value="${answerText}"
                        placeholder="${a === 4 ? 'Correct Answer' : 'False Answer ' + a}"
                        required>

                    <input type="hidden"
                        name="stages[${i}][answers][${a}][is_correct]" 
                        value="${isCorrect}">
                    <br><br>
                `;
            }


            const stageDiv = document.createElement("div");

            const videoHTML = generateStageVideoInput(i, stageData?.video_url ?? ""); 

            stageDiv.innerHTML = `    
                <h3>Stage ${i}</h3>

                <label>Stage Title:</label><br> 
                <input type="text"
                    name="stages[${i}][title]"
                    value="${stageData?.title ?? ''}"
                    required><br><br>

                <label>Question:</label><br>
                <input type="text"
                    name="stages[${i}][question]"
                    value="${questionData?.question_text ?? ''}"
                    required><br><br>



                stagesContainer.appendChild(stageDiv);




            } //end 1st for loop
        });

    });
</script>

<?php






