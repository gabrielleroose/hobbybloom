


<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

require_once 'db.php';


$success = false;
$error = "";


// form submission handling below 
//strtolower used on xpLevel to fit DB constraints (actually need to update db constraints such that CHECK xpLevel in ["beginner", "intermediate", "expert"]
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $NumOfLessons = $_POST["videoCount"] ?? 0;
    $notes = $_POST["notes"] ?? "";
    $xpLevel = strtolower($_POST["xpLevel"]) ?? "";
    $compTime = $_POST["estimate"] ?? 0;


    if (empty($name)) {
        $error = "Module name is required.";
    } else {
        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8",
                $username,
                $password
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "INSERT INTO module
            (name, description, created_by, number_of_lessons, notes, xpLevel, compTime)
            VALUES
            (:name, :description, :created_by, :Number_of_lessons, :notes, :xpLevel, :compTime)";


            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":name" => $name,
                ":description" => $description,
                ":created_by" => $_SESSION['user_id'],
                ":Number_of_lessons" => $NumOfLessons,
                ":notes" => $notes,
                ":xpLevel" => $xpLevel,
                ":compTime" => $compTime,
            ]);


    $module_id = $pdo->lastInsertId();

    
        

    if (!empty($_POST['videos'])) {

        $videoSQL = "
            INSERT INTO module_videos
            (module_id, video_url, lesson_number)
            VALUES (?, ?, ?)
        ";

        $videoStmt = $pdo->prepare($videoSQL);

        foreach ($_POST['videos'] as $index => $url) {

            $url = trim($url);
            if ($url !== "") {
                $videoStmt->execute([
                    $module_id,
                    $url,
                    $index + 1
                ]);
            }
        }
    }


            $success = true;

        } catch (PDOException $e) {
            $error = "Something went wrong saving the module.";
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
<body>
<?php include 'base.php'; ?>
<?php include 'base.php'; ?>

<h2>Create a Module</h2>

<?php if (!empty($error)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green;">Module created successfully!</p>
<?php endif; ?>

<form method="POST">
    <label>Module Name:</label><br>
    <input type="text" name="name" required><br><br>

    <label>Description:</label><br>
    <textarea name="description" rows="4" cols="40"></textarea><br><br>

    <label>Number of lessons:</label><br>
    <input 
    type="number"
    id="videoCount"
    name="videoCount"
    min="0"
    max="5"
    onchange="generateVideoInputs()">

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
    
<div id="stagesContainer"></div>


    
>
<div id="stagesContainer"></div>

<div id="videoInputs"></div><br>
    

    <label>Notes:</label><br>
    <textarea name="notes" rows="4" cols="40"></textarea><br><br>

    <label>Recomended experince level:</label><br>
        <select id="xpLevel" name="xpLevel">
            <option value="Beginner" selected>Beginner</option>
            <option value="Intermidate">Intermidate</option>
            <option value="Expert">Expert</option>
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
    //ensures all content is loaded before attempting to load JS- ensures necessary values are present
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

    for (let i = 1; i <= stageCount; i++) {
      const stageDiv = document.createElement("div"); //loops through values from i=1 to stageCount;

      stageDiv.innerHTML = ` 
        <h3>Stage ${i}</h3>
        <input type="text" name="stageTitle_${i}" required />
      `;

      stagesContainer.appendChild(stageDiv);
      
    }
  });
});


    

</script>



        


        
