<?php
session_start();
require_once 'db.php';

require_once 'base.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$circleId = $_GET['id'] ?? null;
$error = "";
$success = false;

$stmt = $conn->prepare("SELECT * FROM circle WHERE circle_id = ? AND uid = ?");
$stmt->execute([$circleId, $userId]);
$circle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$circle) {
    die("<div style='text-align:center; padding: 50px;'><h2 style='color:#1f5077;'>Circle not found or you do not have permission to edit it.</h2></div>");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'delete_circle') {
    try {
        $delMsg = $conn->prepare("DELETE FROM circle_messages WHERE hobby_name = ?");
        $delMsg->execute([$circle['name']]);

        $delCircle = $conn->prepare("DELETE FROM circle WHERE circle_id = ? AND uid = ?");
        $delCircle->execute([$circleId, $userId]);

        header("Location: dashboard.php?success=circle_deleted");
        exit();
    } catch (PDOException $e) {
        $error = "Something went wrong while trying to delete the circle.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['action'])) {
    $description = trim($_POST["description"]);
    $color = $_POST["color"] ?? '#1f5077';
    $category = $_POST["category"] ?? 'General';

    if (empty($description)) {
        $error = "Description cannot be empty.";
    } else {
        try {
            $upd = $conn->prepare("UPDATE circle SET description = ?, color = ?, category = ? WHERE circle_id = ? AND uid = ?");
            $upd->execute([$description, $color, $category, $circleId, $userId]);
            $success = true;
            
            $circle['description'] = $description;
            $circle['color'] = $color;
            $circle['category'] = $category;
        } catch (PDOException $e) {
            $error = "Something went wrong saving your changes.";
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

    .edit-circle-wrapper {
        flex: 1;
        padding: 40px 20px;
    }

    footer {
        margin-top: auto;
    }
</style>

<div class="edit-circle-wrapper">
    <div style="background-color: <?= htmlspecialchars($circle['color']) ?>; padding: 30px; border-radius: 15px; text-align: center; margin: 0 auto 30px auto; max-width: 600px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h1 style="color: white; margin: 0; font-size: 32px; text-shadow: 1px 1px 3px rgba(0,0,0,0.4);">
            Edit: <?= htmlspecialchars($circle['name']) ?>
        </h1>
    </div>

    <section class="form" style="max-width: 600px; margin: 0 auto; background-color: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        
        <?php if ($error): ?>
            <p style="color: red; text-align: center; font-weight: bold; margin-bottom: 20px;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p style="color: green; text-align: center; font-weight: bold; font-size: 1.2rem;">✨ Circle updated successfully!</p>
            <div style="text-align: center; margin-top: 25px;">
                <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" class="light-btn" style="text-decoration: none; color: white; background-color: #1f5077; padding: 12px 30px; border-radius: 30px; font-weight: bold;">
                    Back to Circle →
                </a>
            </div>
        <?php else: ?>
            <form method="POST">
                
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #1f5077;">Circle Name (Locked):</label>
                    <input type="text" value="<?= htmlspecialchars($circle['name']) ?>" disabled style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #ddd; background-color: #f0f0f0; color: #666; font-weight: bold; box-sizing: border-box;">
                    <p style="font-size: 0.85rem; color: #888; margin-top: 5px; font-style: italic;">Circle names cannot be changed once created.</p>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #1f5077;">Category:</label>
                    <select name="category" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #ccc; background-color: white; box-sizing: border-box;">
                        <option value="General" <?= $circle['category'] == 'General' ? 'selected' : '' ?>>General</option>
                        <option value="Arts" <?= $circle['category'] == 'Arts' ? 'selected' : '' ?>>Arts</option>
                        <option value="Technical" <?= $circle['category'] == 'Technical' ? 'selected' : '' ?>>Technical</option>
                        <option value="Wellness" <?= $circle['category'] == 'Wellness' ? 'selected' : '' ?>>Wellness</option>
                    </select>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #1f5077;">Description:</label>
                    <textarea name="description" rows="4" required style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #ccc; box-sizing: border-box; resize: vertical;"><?= htmlspecialchars($circle['description']) ?></textarea>
                </div>

                <div style="margin-bottom: 35px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #1f5077;">Circle Theme Color:</label>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <input type="color" name="color" value="<?= htmlspecialchars($circle['color']) ?>" style="width: 60px; height: 50px; border: none; padding: 0; cursor: pointer; border-radius: 8px;">
                        <span style="color: #666; font-size: 14px;">Update the primary color for this group.</span>
                    </div>
                </div>

                <div style="text-align: center; display: flex; gap: 15px; justify-content: center; margin-bottom: 30px;">
                    <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" style="background-color: #e0e0e0; color: #555; text-decoration: none; padding: 12px 30px; border-radius: 30px; font-weight: bold; transition: 0.3s;">Cancel</a>
                    <button type="submit" style="background-color: #1f5077; color: white; border: none; padding: 12px 30px; border-radius: 30px; font-weight: bold; cursor: pointer; transition: 0.3s;">Save Changes</button>
                </div>
            </form>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

            <div style="text-align: center; background-color: rgba(255, 0, 0, 0.05); padding: 25px; border-radius: 10px; border: 1px dashed #ff4d4d;">
                <h3 style="color: #d9534f; margin-top: 0;">Danger Zone</h3>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">Once you delete a circle, there is no going back. All chat history will be permanently deleted.</p>
                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete this circle? This action cannot be undone.');">
                    <input type="hidden" name="action" value="delete_circle">
                    <button type="submit" style="background-color: #d9534f; color: white; border: none; padding: 10px 20px; border-radius: 30px; font-weight: bold; cursor: pointer; transition: 0.3s;">
                        Delete Circle
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>