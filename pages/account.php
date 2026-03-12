<?php
session_start();
require_once 'db.php';
require_once 'base.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];

$reqStmt = $conn->prepare("SELECT COUNT(*) FROM user_follows WHERE followed_id = ? AND status = 'pending'");
$reqStmt->execute([$userId]);
$reqCount = (int)$reqStmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT u.username, u.age, p.hometown, p.bio, p.hobbies, p.profile_color, p.is_private 
    FROM users u 
    LEFT JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$followerStmt = $conn->prepare("
    SELECT u.id, u.username 
    FROM users u 
    JOIN user_follows f ON u.id = f.follower_id 
    WHERE f.followed_id = ? AND f.status = 'accepted'
");
$followerStmt->execute([$userId]);
$followers = $followerStmt->fetchAll(PDO::FETCH_ASSOC);

$followingStmt = $conn->prepare("
    SELECT u.id, u.username 
    FROM users u 
    JOIN user_follows f ON u.id = f.followed_id 
    WHERE f.follower_id = ? AND f.status = 'accepted'
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
            <h1>Account Settings</h1>
            
            <?php if (isset($_GET['success'])): ?>
                <p style="color: #90ee90; font-weight: bold; text-align: center; background-color: #1f5077; padding: 10px; border-radius: 5px;">
                    <?php 
                        switch($_GET['success']) {
                            case 'unfollowed': echo "Successfully unfollowed user."; break;
                            case 'removed': echo "Follower removed."; break;
                            case 'requested': echo "Follow request sent!"; break;
                            case 'followed': echo "You are now following this user!"; break;
                            default: echo "Profile updated successfully!";
                        }
                    ?>
                </p>
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

                <div class="account-privacy">
                    <label style="font-weight: bold; color: #333;">Account Privacy:</label>
                    <select name="is_private" class="account-privacy-input">
                        <option value="0" <?= ($user['is_private'] == 0) ? 'selected' : '' ?>>Public (everyone can see my activity)</option>
                        <option value="1" <?= ($user['is_private'] == 1) ? 'selected' : '' ?>>Private (only followers see my activity)</option>
                    </select>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                    <div style="flex: 1;">
                        <label style="font-weight: bold; color: #333;">Age:</label>
                        <input class="account-input age" type="number" name="age" value="<?= htmlspecialchars($user['age'] ?? '') ?>" >
                    </div>
                    <div style="flex: 1;">
                        <label style="font-weight: bold; color: #333;">Hometown:</label>
                        <input class="account-input hometown" type="text" name="from" value="<?= htmlspecialchars($user['hometown'] ?? '') ?>" >
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; color: #333;">Bio:</label>
                    <textarea class="account-input" name="bio" rows="3"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="font-weight: bold; color: #333;">My Interests (comma separated):</label>
                    <input class="account-input" type="text" name="selected_hobbies" value="<?= htmlspecialchars($user['hobbies'] ?? '') ?>" placeholder="Cooking, Gaming, Lego">
                </div>

                <div class="account-save">
                    <button type="submit" class="account-save-button">Save Changes</button>
                </div>
            </form>

            <hr style="margin: 40px 0; border: 0; border-top: 1px solid rgba(255,255,255,0.2);">

            <div style="display: flex; gap: 40px; margin-bottom: 40px;">
                <div style="flex: 1;">
                    <h3 style="color: white; border-bottom: 2px solid white; padding-bottom: 5px; display: flex; justify-content: space-between; align-items: center;">
                        Followers (<?= count($followers) ?>)
                        
                        <?php if ($user['is_private'] == 1): ?>
                            <a href="follow_requests.php" style="background-color: #ffd700; color: #153853; padding: 6px 12px; border-radius: 20px; text-decoration: none; font-size: 13px; font-weight: 800; box-shadow: 0 2px 5px rgba(0,0,0,0.2); display: inline-block;">
                                Manage Requests <?= ($reqCount > 0) ? "($reqCount)" : "" ?>
                            </a>
                        <?php endif; ?>
                    </h3>
                    <ul style="list-style: none; padding: 0; margin-top: 10px;">
                        <?php if (empty($followers)): ?>
                            <li style="color: #333; font-style: italic;">No followers yet.</li>
                        <?php else: ?>
                            <?php foreach ($followers as $f): ?>
                                <li style="padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
                                    <a href="profile.php?id=<?= $f['id'] ?>" style="color: #1f5077; text-decoration: none; font-weight: bold;"><?= htmlspecialchars($f['username']) ?></a>
                                    
                                    <form action="circle_action.php" method="POST" style="margin: 0;" onsubmit="return confirm('Remove this follower? They will no longer see your private activity.');">
                                        <input type="hidden" name="action" value="toggle_follow">
                                        <input type="hidden" name="action_type" value="remove_follower">
                                        <input type="hidden" name="target_id" value="<?= $f['id'] ?>">
                                        <input type="hidden" name="hobby" value="account_redirect">
                                        <button type="submit" style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                                            Remove
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div style="flex: 1;">
                    <h3 style="color: white; border-bottom: 2px solid white; padding-bottom: 5px;">Following (<?= count($following) ?>)</h3>
                    <ul style="list-style: none; padding: 0; margin-top: 10px;">
                        <?php if (empty($following)): ?>
                            <li style="color: #333; font-style: italic;">You aren't following anyone yet.</li>
                        <?php else: ?>
                            <?php foreach ($following as $f): ?>
                                <li style="padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
                                    <a href="profile.php?id=<?= $f['id'] ?>" style="color: #1f5077; text-decoration: none; font-weight: bold;"><?= htmlspecialchars($f['username']) ?></a>
                                    
                                    <form action="circle_action.php" method="POST" style="margin: 0;" onsubmit="return confirm('Unfollow <?= htmlspecialchars($f['username']) ?>?');">
                                        <input type="hidden" name="action" value="toggle_follow">
                                        <input type="hidden" name="target_id" value="<?= $f['id'] ?>">
                                        <input type="hidden" name="hobby" value="account_redirect">
                                        <button type="submit" style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                                            Unfollow
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <hr style="margin: 40px 0; border: 0; border-top: 1px solid rgba(255,255,255,0.2);">

            <div class="account-delete">
                <h3>Danger Zone</h3>
                <p>Once you delete your account, there is no going back. Please be certain.</p>
                <form action="delete_account.php" method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.');">
                    <button type="submit">Delete Account</button>
                </form>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>