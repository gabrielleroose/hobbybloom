<?php
session_start();
require_once 'db.php';
require_once 'base.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT u.username, u.age, p.hometown, p.bio, p.hobbies, p.profile_color, p.is_private 
    FROM users u 
    LEFT JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$followerStmt = $conn->prepare("SELECT u.id, u.username FROM users u JOIN user_follows f ON u.id = f.follower_id WHERE f.followed_id = ?");
$followerStmt->execute([$userId]);
$followers = $followerStmt->fetchAll(PDO::FETCH_ASSOC);

$followingStmt = $conn->prepare("SELECT u.id, u.username FROM users u JOIN user_follows f ON u.id = f.followed_id WHERE f.follower_id = ?");
$followingStmt->execute([$userId]);
$following = $followingStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Account</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>
<body class="account-body">
    <div class="account-main-container">
        <div class="account-settings">
            <h1 style="color: white;">Account Settings</h1>
            <?php if (isset($_GET['success'])): ?>
                <p style="color: #90ee90; font-weight: bold; text-align: center; background-color: #1f5077; padding: 10px; border-radius: 5px;">Profile updated successfully!</p>
            <?php endif; ?>

            <form action="update_account.php" method="POST">
                <div class="account-username">
                    <label>Username:</label>
                    <input class="username-input" type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; color: #333;">Profile Theme Color:</label>
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                        <input type="color" name="profile_color" value="<?= htmlspecialchars($user['profile_color'] ?? '#1f5077') ?>" style="width: 50px; height: 50px; border: none; padding: 0; cursor: pointer; border-radius: 5px;">
                        <span style="color: #666; font-size: 14px;">Pick a color for your public profile!</span>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; color: #333;">Account Privacy:</label>
                    <select name="is_private" style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px;">
                        <option value="0" <?= ($user['is_private'] == 0) ? 'selected' : '' ?>>Public (Everyone can see my activity)</option>
                        <option value="1" <?= ($user['is_private'] == 1) ? 'selected' : '' ?>>Private (Only followers see my activity)</option>
                    </select>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                    <div style="flex: 1;">
                        <label style="font-weight: bold; color: #333;">Age:</label>
                        <input type="number" name="age" value="<?= htmlspecialchars($user['age'] ?? '') ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-weight: bold; color: #333;">Hometown:</label>
                        <input type="text" name="from" value="<?= htmlspecialchars($user['hometown'] ?? '') ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; color: #333;">Bio:</label>
                    <textarea name="bio" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; color: #333;">My Interests (comma separated):</label>
                    <input type="text" name="selected_hobbies" value="<?= htmlspecialchars($user['hobbies'] ?? '') ?>" placeholder="Cooking, Gaming, Lego" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                </div>

                <button type="submit" class="light-btn" style="background-color: #1f5077; color: white; width: 100%; padding: 12px; font-weight: bold; border-radius: 5px; cursor: pointer;">Save Changes</button>
            </form>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>