
<?php
require_once 'db.php';


// form submission handling below 
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $NumOfLessons = $_POST["videoCount"] ?? 0;
    $notes = $_POST["notes"] ?? "";
    $xpLevel = $_POST["xpLevel"] ?? "";
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
</head>
<body>

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
    min="0" 
    max="10" 
    onchange="generateVideoInputs()"
>

<div id="videoInputs"></div><br>
    

    <label>Notes:</label><br>
    <textarea name="notes" rows="4" cols="40"></textarea><br><br>

    <label>Recomended experince level:</label><br>
        <select id="xpLevel" name="xpLevel">
            <option value="Begginer" selected>Begginer</option>
            <option value="Intermidate">Intermidate</option>
            <option value="Expert">Expert</option>
        </select>
        <br><br>
    

    <label>Estimated time of completion:</label>
    <!-- maybe do a set time format if possible -->
    <label for="estimate">Estimated time (minutes):</label>
<input type="number" id="estimate" min="1" required>
<p id="formattedOutput"></p>



    <button type="submit">Create Module</button>
</form>

</body>
</html>

<script>
function generateVideoInputs() {
    const count = document.getElementById("videoCount").value;
    const container = document.getElementById("videoInputs");

    // Clear existing inputs
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


need:  ( module name )
       ( Module Description )
        ( Creator )
        ( number of lessons - way to upload videos -
            the actual lessons )
        ( notes section )
        ( difficult level )
        ( estimated time )

        
