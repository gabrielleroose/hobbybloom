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
    $color = $_POST["color"] ?? '#1f5077';
    $category = $_POST["category"] ?? 'General';
    $userId = $_SESSION['user']['id'];

    if (empty($name) || empty($description)) {
        $error = "Please fill out all fields.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO circle (name, description, uid, color, category) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $userId, $color, $category]);
            
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
<body class="create-circle-body">

<?php include 'base.php'; ?>

<div class="create-circle-main-container">
    <div class="create-circle-top">
        <h1 class="create-module-heading">Create a New Circle</h1>
        <p>Start a community around your favorite hobby!</p>
    </div>

    <section class="create-circle-form">
        
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
                    <label class="create-circle-label" >Circle / Hobby Name:</label>
                    <input class="create-circle-input" type="text" name="name" placeholder="Circle/Hobby Name: e.g., Photography">
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="create-circle-label" >Description:</label>
                    <textarea class="create-circle-input" name="description" rows="4" placeholder="Description of Circle: What is this circle about?"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="create-circle-label">Category:</label>
                    <select class="create-circle-input" name="category">
                        <option value="General">General / Other</option>
                        <option value="Arts">Arts & Creativity</option>
                        <option value="Technical">Technical & Learning</option>
                        <option value="Wellness">Wellness & Lifestyle</option>
                    </select>
                </div>

                <div class="circle-theme-color" style="margin-bottom: 20px;">
                    <label class="create-circle-label theme">Circle Theme Color:</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="color" name="color" value="#1f5077" style="width: 50px; height: 50px; border: none; padding: 0; cursor: pointer; border-radius: 5px;">
                        <span style="color: #666; font-size: 14px;">Pick a primary color for this group!</span>
                    </div>
                </div>

                <div style="text-align: center;">
                    <button class="create-module-button" type="submit" class="light-btn" >Create Circle</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>