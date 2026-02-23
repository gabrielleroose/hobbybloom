<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$circleId = $_GET['id'] ?? null;
$error = "";
$success = false;

// Fetch the circle to make sure it exists AND the current user owns it
$stmt = $conn->prepare("SELECT * FROM circle WHERE circle_id = ? AND uid = ?");
$stmt->execute([$circleId, $userId]);
$circle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$circle) {
    die("<h2 style='color:white; text-align:center;'>Circle not found or you do not have permission to edit it.</h2>");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $description = trim($_POST["description"]);
    $color = $_POST["color"] ?? '#1f5077';

    if (empty($description)) {
        $error = "Description cannot be empty.";
    } else {
        try {
            $upd = $conn->prepare("UPDATE circle SET description = ?, color = ? WHERE circle_id = ? AND uid = ?");
            $upd->execute([$description, $color, $circleId, $userId]);
            $success = true;
            // Update the local variable so the form shows the new data instantly
            $circle['description'] = $description;
            $circle['color'] = $color;
        } catch (PDOException $e) {
            $error = "Something went wrong saving your changes.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Circle</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>
<body>
<?php include 'base.php'; ?>

<div class="page-container">
    <div style="background-color: <?= htmlspecialchars($circle['color']) ?>; padding: 30px; border-radius: 15px; text-align: center; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h1 style="color: white; margin: 0; font-size: 32px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">Edit: <?= htmlspecialchars($circle['name']) ?></h1>
    </div>

    <section class="form" style="max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <?php if ($error): ?>
            <p style="color: red; text-align: center; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p style="color: green; text-align: center; font-weight: bold;">Circle updated successfully!</p>
            <div style="text-align: center; margin-top: 20px;">
                <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" class="light-btn" style="text-decoration: none; color: #333; background-color: #a8d0e6;">Back to Circle</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Circle Name (Locked):</label>
                    <input type="text" value="<?= htmlspecialchars($circle['name']) ?>" disabled style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; background-color: #f5f5f5;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Description:</label>
                    <textarea name="description" rows="4" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box;"><?= htmlspecialchars($circle['description']) ?></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px; color: #333;">Circle Theme Color:</label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="color" name="color" value="<?= htmlspecialchars($circle['color']) ?>" style="width: 50px; height: 50px; border: none; padding: 0; cursor: pointer; border-radius: 5px;">
                    </div>
                </div>

                <div style="text-align: center; display: flex; gap: 15px; justify-content: center;">
                    <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" class="light-btn" style="background-color: #ccc; color: #333; text-decoration: none; padding: 10px 30px; border-radius: 5px;">Cancel</a>
                    <button type="submit" class="light-btn" style="background-color: #1f5077; color: white; border: none; padding: 10px 30px; border-radius: 5px;">Save Changes</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>