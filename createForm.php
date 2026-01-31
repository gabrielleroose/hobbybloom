<?php
session_start();

$host = "localhost";
$dbname = "your_database_name";
$username = "db_user";
$password = "db_password";


if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // false login for test
}

$success = false;
$error = "";

// form submission 
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

    <button type="submit">Create Module</button>
</form>

</body>
</html>
