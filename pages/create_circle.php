<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $userId = $_SESSION['user']['id'];

    if (empty($name) || empty($description)) {
        $error = "Please fill out all fields.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO circle (name, description, uid) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $userId]);
            
            $profStmt = $conn->prepare("SELECT hobbies FROM user_profiles WHERE user_id = ?");
            $profStmt->execute([$userId]);
            $hobbiesStr = $profStmt->fetchColumn();
            $hobbiesArr = $hobbiesStr ? explode(', ', $hobbiesStr) : [];
            
            if (!in_array($name, $hobbiesArr)) {
                $hobbiesArr[] = $name;
                $newHobbies = implode(', ', $hobbiesArr);
                $conn->prepare("UPDATE user_profiles SET hobbies = ? WHERE user_id = ?")->execute([$newHobbies, $userId]);
            }

            $success = true;
        } catch (PDOException $e) {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create a Circle</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>
<body>

<?php include 'base.php'; ?>

<div class="page-container">
    <div style="background-color: #1f5077; padding: 30px; border-radius: 15px; text-align: center; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h1 style="color: white; margin: 0; font-size: 32px;">Create a New Circle</h1>
        <p style="color: #ccc; margin-top: 10px;">Start a community around your favorite hobby!</p>
    </div>

    <section class="form" style="max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        
        <?php if ($error): ?>
            <p style="color: red; text-align: center; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p style="color: green; text-align: center; font-weight: bold;">Circle created successfully!</p>
            <div style="text-align: center; margin-top: 20px;">
                <a href="circle_detail.php?hobby=<?= urlencode($name) ?>" class="light-btn" style="text-decoration: none; color: #333; background-color: #a8d0e6;">Go to Circle</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Circle / Hobby Name:</label>
                    <input type="text" name="name" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box;" placeholder="e.g., Photography">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Description:</label>
                    <textarea name="description" rows="4" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box;" placeholder="What is this circle about?"></textarea>
                </div>

                <div style="text-align: center;">
                    <button type="submit" class="light-btn" style="background-color: #1f5077; color: white; border: none; font-size: 16px; padding: 10px 30px;">Create Circle</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>