<?php
session_start();
require_once 'db.php';

require_once 'base.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = false;
$name = "";

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

<style>
    body {
        background-color: #BDC29D !important;
        margin: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .create-circle-wrapper {
        flex: 1;
        padding: 40px 20px;
    }

    footer {
        margin-top: auto;
    }
</style>

<div class="create-circle-wrapper">
    <div class="create-circle-main-container">
        <div class="create-circle-top">
            <h1 class="create-module-heading">Create a New Circle</h1>
            <p>Start a community around your favorite hobby!</p>
        </div>

        <section class="create-circle-form">
            
            <?php if ($error): ?>
                <p style="color: red; text-align: center; font-weight: bold; margin-bottom: 20px;">
                    <?= htmlspecialchars($error) ?>
                </p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p style="color: green; text-align: center; font-weight: bold; font-size: 1.2rem;">✨ Circle created successfully!</p>
                <div style="text-align: center; margin-top: 25px;">
                    <a href="circle_detail.php?hobby=<?= urlencode($name) ?>" class="light-btn" style="text-decoration: none; color: white; background-color: #1f5077; padding: 12px 30px; border-radius: 30px; font-weight: bold;">
                        Go to your new Circle →
                    </a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div style="margin-bottom: 25px;">
                        <label class="create-circle-label" style="font-weight: bold; color: #1f5077;">Circle / Hobby Name:</label>
                        <input class="create-circle-input" type="text" name="name" placeholder="e.g., 📸 Photography" required>
                        <p style="font-size: 0.85rem; color: #555; margin-top: 8px; font-style: italic;">
                            💡 <strong>Tip:</strong> Start the name with an emoji! It will be used as the icon for your circle.
                        </p>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label class="create-circle-label" style="font-weight: bold; color: #1f5077;">Description:</label>
                        <textarea class="create-circle-input" name="description" rows="4" placeholder="What is this circle about?" required></textarea>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label class="create-circle-label" style="font-weight: bold; color: #1f5077;">Category:</label>
                        <select class="create-circle-input" name="category" style="padding: 10px; border-radius: 10px; border: 1px solid #ccc; width: 100%;">
                            <option value="General">General / Other</option>
                            <option value="Arts">Arts & Creativity</option>
                            <option value="Technical">Technical & Learning</option>
                            <option value="Wellness">Wellness & Lifestyle</option>
                        </select>
                    </div>

                    <div class="circle-theme-color" style="margin-bottom: 35px;">
                        <label class="create-circle-label theme" style="font-weight: bold; color: #1f5077;">Circle Theme Color:</label>
                        <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                            <input type="color" name="color" value="#1f5077" style="width: 60px; height: 50px; border: none; padding: 0; cursor: pointer; border-radius: 8px;">
                            <span style="color: #666; font-size: 14px;">Pick a primary background color for your group's icon.</span>
                        </div>
                    </div>

                    <div style="text-align: center;">
                        <button class="create-module-button" type="submit" style="background-color: #1f5077; color: white; padding: 12px 40px; border: none; border-radius: 30px; font-weight: bold; cursor: pointer; font-size: 1rem; transition: 0.3s;">
                            Create Circle
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>