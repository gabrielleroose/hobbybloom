<?php
session_start();
require_once 'db.php';
require_once 'base.php';
 
if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}
 
$error   = "";
$success = false;
$name    = "";
 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name        = trim($_POST["name"]);
    $description = trim($_POST["description"]);
    $color       = $_POST["color"]    ?? '#1f5077';
    $category    = $_POST["category"] ?? 'General';
    $userId      = $_SESSION['user']['id'];
 
    if (empty($name) || empty($description)) {
        $error = "Please fill out all fields.";
    } else if (empty(extractEmoji($name))) {
        $error = "Oops! You forgot your icon. Please start your circle name with an emoji (e.g., 📸 Photography).";
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
                $newHobbies   = implode(', ', $hobbiesArr);
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
    <title>Create Circle | HobbyBloom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/nav.css" rel="stylesheet">
</head>
<body class="create-circle-body">
 
<div class="cc-page-wrap">
<div class="cc-wrap">

 
    <!-- Form card -->
    <div class="cc-card">

        <!-- Header -->
    <div class="cc-heading">
        <div class="cm-eyebrow">Circle Builder</div>
        <h1 class="cc-title">Create a New Circle</h1>
        <p class="cc-subtitle">Start a community around your favourite hobby!</p>
    </div>
 
        <?php if ($error): ?>
            <div class="cm-message cm-message-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
 
        <?php if ($success): ?>
            <div class="cm-message cm-message-success">✨ Circle created successfully!</div>
            <div class="cc-success-actions">
                <a href="circle_detail.php?hobby=<?= urlencode($name) ?>" class="cc-go-btn">
                    Go to your new Circle →
                </a>
            </div>
 
        <?php else: ?>
 
            <form method="POST" class="cc-form">
 
                <!-- Circle name -->
                <div class="cm-field">
                    <label class="create-circle-label cm-show">Circle / Hobby Name</label>
                    <input class="create-circle-input" type="text" name="name"
                           placeholder="e.g. 📸 Photography" required>
 
                    <!-- Emoji hint -->
                    <div class="cc-hint-box">
                        <span class="cc-hint-icon">💡</span>
                        <div>
                            <strong class="cc-hint-title">Emoji required</strong>
                            <span class="cc-hint-text">
                                Start the name with an emoji so it displays beautifully across the app.
                            </span>
                        </div>
                    </div>
                </div>
 
                <!-- Description -->
                <div class="cm-field">
                    <label class="create-circle-label cm-show">Description</label>
                    <textarea class="create-circle-input" name="description" rows="4"
                              placeholder="What is this circle about?" required></textarea>
                </div>
 
                <!-- Category -->
                <div class="cm-field">
                    <label class="create-circle-label cm-show">Category</label>
                    <select class="create-circle-input" name="category">
                        <option value="General">General / Other</option>
                        <option value="Arts">Arts &amp; Creativity</option>
                        <option value="Technical">Technical &amp; Learning</option>
                        <option value="Wellness">Wellness &amp; Lifestyle</option>
                    </select>
                </div>
 
                <!-- Theme colour -->
                <div class="cm-field">
                    <label class="create-circle-label cm-show">Circle Theme Color</label>
                    <div class="ac-color-row">
                        <input type="color" name="color" value="#1f5077" class="ac-color-input">
                        <span class="ac-color-hint">Pick a primary colour for your circle's icon.</span>
                    </div>
                </div>
 
                <div class="cc-submit-wrap">
                    <button type="submit" class="cc-submit-btn">Create Circle</button>
                </div>
 
            </form>
 
        <?php endif; ?>
 
    </div>
 
</div>
</div>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 
</body>
</html>