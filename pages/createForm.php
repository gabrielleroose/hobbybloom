<?php
session_start();

// Chnage below to real data base info
$host = "localhost";
$dbname = "your_database_name";
$username = "db_user";
$password = "db_password";


if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // made up login for test
}

$success = false;
$error = "";

// form submission handling below
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $description = trim($_POST["description"]);

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

            $sql = "INSERT INTO module (name, description, created_by)
                    VALUES (:name, :description, :created_by)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":name" => $name,
                ":description" => $description,
                ":created_by" => $_SESSION['user_id']
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
    <!-- figure out how to add a working number system that the system can count to make spots for each lesson -->

    <label>Notes:</label><br>
    <textarea name="description" rows="4" cols="40"></textarea><br><br>

    <label>Recomended experince level:</label><br>
        <select id="xpLevel" name="xpLevel">
            <option value="Begginer">Begginer</option>
            <option value="Intermidate" selected>Intermidate</option>
            <option value="Expert">Expert</option>
        </select>
        <br><br>
    <!-- add drop down with levels -->

    <label>Estimated time of completion:</label>
    <!-- maybe do a set time format if possible -->
    <input type="text" name="name" required><br><br>


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

need:  ( module name )
       ( Module Description )
        ( Creator )
        ( number of lessons - way to upload videos -
            the actual lessons )
        ( notes section )
        ( difficult level )
        ( estimated time )