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


    foreach($module_stage_questions_info as &$stage) {
        foreach ($stage['question'] as &$question) {
                $module_stage_questions_answers_sql = "SELECT msqa.id, msqa.answer, msqa.is_correct, msqa.ans_num FROM module_stage_questions_answers AS msqa JOIN module_stage_questions AS msq ON msqa.msqid = msq.id WHERE msqa.msqid = ?";
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
           

?>

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
                            <input type="text" name="name" required><br><br>

                            <label>Description:</label><br>
                            <textarea name="description" rows="4" cols="40"></textarea><br><br>

                            <label>Number of videos:</label><br>
                            <input 
                            type="number"
                            id="videoCount"
                            name="videoCount"
                            min="0"
                            max="5"
                            onchange="generateVideoInputs()">

    <div id="videoInputs"></div><br>

<div>
    <label>Number of lessons:</label><br>
    <input 
    type="number"
    id="stage_num"
    name="stage_num"
    min="0"
    max="5"
    >
</div>
    
<div id="stagesContainer"></div> <!-- this is where the contents of the javascript below are loaded. -->



    

                            <label>Notes:</label><br>
                            <textarea name="notes" rows="4" cols="40"></textarea><br><br>

                            <label>Recomended experience level:</label><br>
                                <select id="exp_level" name="exp_level">
                                    <option value="beginner" selected>Beginner</option>
                                    <option value="intermediate">Intermediate</option>
                                    <option value="expert">Expert</option>
                                </select>
                                <br><br>
                            

                            <label>Estimated time of completion:</label>
                            <!-- maybe do a set time format if possible -->
                            <label for="estimate">Estimated time (minutes):</label>
                            <input
                            type="number"
                            id="estimate"
                            name="estimate"
                            min="1"
                            required
                            >

                        <p id="formattedOutput"></p>



                            <button type="submit">Create Module</button>
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
    //ensures all content is loaded before attempting to load JS- ensures necessary values are present.
    document.addEventListener("DOMContentLoaded", function () {

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

    <input type="hidden"
           name="stages[${i}][answers][4][is_correct]"
           value="1">
`;

                stageDiv.innerHTML = `
                <div class="stage_number"> 
                        <h3>Stage ${i}</h3>
                    </div>

                    <br><br>

        <div class="stage_title">
            <label>Stage Title:</label><br>
            <input type="text" name="stages[${i}][title]" required />
        </div>

        <br><br>

        <div class="stage_questions">
            <!-- question -->
            <label>Question:</label><br>
            <input type="text" name="stages[${i}][question]" id="${i}" required> <!--NOTICE: this is an array. stages>stage_num>question. PHP processing is gonna be FUN. -->
        </div>
                <br><br>

                    <div class="stage_image">
                        <!-- img upload -->
                        <label>Upload Image:</label><br>
                        <input type="file" 
                        name="stages[${i}][image]">
                    </div>

                    <br><br>

        <div class="stage_answers">
            <strong>False Answers:</strong><br>
            ${answersHTML}
        </div>
      `;

      
      
      stagesContainer.appendChild(stageDiv);

    
      
      
    } //end 1st for loop
  });

});
</script>

<?php 


        


        
