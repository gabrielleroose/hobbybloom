<?php
require_once 'base.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT u.username, u.email, u.age, p.hometown, p.bio, p.hobbies, p.profile_color 
    FROM users u 
    LEFT JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$followerStmt = $conn->prepare("
    SELECT u.id, u.username FROM users u 
    JOIN user_follows f ON u.id = f.follower_id 
    WHERE f.followed_id = ?
");
$followerStmt->execute([$userId]);
$followers = $followerStmt->fetchAll(PDO::FETCH_ASSOC);

$followingStmt = $conn->prepare("
    SELECT u.id, u.username FROM users u 
    JOIN user_follows f ON u.id = f.followed_id 
    WHERE f.follower_id = ?
");
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
                
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; color: #333;">Username:</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; color: #333;">Profile Theme Color:</label>
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                        <input type="color" name="profile_color" value="<?= htmlspecialchars($user['profile_color'] ?? '#1f5077') ?>" style="width: 50px; height: 50px; border: none; padding: 0; cursor: pointer; border-radius: 5px;">
                        <span style="color: #666; font-size: 14px;">Pick a color for your public profile!</span>
                    </div>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                    <div style="flex: 1;">
                        <label style="font-weight: bold; color: #333;">Age:</label>
                        <input type="number" name="age" value="<?= htmlspecialchars($user['age'] ?? '') ?>" style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="font-weight: bold; color: #333;">Hometown:</label>
                        <input type="text" name="from" value="<?= htmlspecialchars($user['hometown'] ?? '') ?>" style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; color: #333;">Bio:</label>
                    <textarea name="bio" rows="3" style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px;"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; color: #333;">My Interests (comma separated):</label>
                    <input type="text" name="selected_hobbies" value="<?= htmlspecialchars($user['hobbies'] ?? '') ?>" placeholder="Cooking, Gaming, Lego" style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px;">
                </div>

                <button type="submit" class="light-btn" style="background-color: #1f5077; color: white; border: none; width: 100%; padding: 12px; font-weight: bold; border-radius: 5px; cursor: pointer;">Save Changes</button>
            </form>
        </div>

        <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            
            <div style="background-color: #1f5077; color: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 10px;">Following (<?= count($following) ?>)</h3>
                <div style="max-height: 200px; overflow-y: auto; padding-right: 10px;">
                    <?php if (empty($following)): ?>
                        <p style="color: #ccc; font-style: italic; font-size: 14px;">You aren't following anyone yet.</p>
                    <?php else: ?>
                        <?php foreach ($following as $f): ?>
                            <a href="profile.php?id=<?= $f['id'] ?>" style="color: white; text-decoration: none; display: block; margin-bottom: 10px; font-weight: bold;">
                                <span style="background-color: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 15px;">@<?= htmlspecialchars($f['username']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div style="background-color: #1f5077; color: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 10px;">Followers (<?= count($followers) ?>)</h3>
                <div style="max-height: 200px; overflow-y: auto; padding-right: 10px;">
                    <?php if (empty($followers)): ?>
                        <p style="color: #ccc; font-style: italic; font-size: 14px;">No followers yet.</p>
                    <?php else: ?>
                        <?php foreach ($followers as $f): ?>
                            <a href="profile.php?id=<?= $f['id'] ?>" style="color: white; text-decoration: none; display: block; margin-bottom: 10px; font-weight: bold;">
                                <span style="background-color: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 15px;">@<?= htmlspecialchars($f['username']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div style="margin-top: 50px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 30px; text-align: center;">
            <h3 style="color: #ff6b6b; margin-top: 0; font-size: 24px;">Danger Zone</h3>
            <p style="color: #ccc; font-size: 14px; margin-bottom: 20px;">Once you delete your account, there is no going back. Please be certain.</p>
            
            <form action="delete_account.php" method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This will permanently erase your profile, circles, modules, and chat history.');">
                <input type="hidden" name="delete_account" value="1">
                <button type="submit" style="background-color: transparent; border: 2px solid #ff6b6b; color: #ff6b6b; padding: 10px 25px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: all 0.3s;">
                    Delete My Account
                </button>
            </form>
        </div>

    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>