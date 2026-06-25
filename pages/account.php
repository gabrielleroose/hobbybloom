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
    <title>My Account | HobbyBloom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/nav.css" rel="stylesheet">
</head>
<body class="account-body">
 
<div class="account-main-container">
<div class="account-settings">
 
    <!-- Header -->
    <div class="ac-header">
        <div class="ac-eyebrow">Settings</div>
        <h1 class="ac-title">Account Settings</h1>
    </div>
 
    <!-- Success message -->
    <?php if (isset($_GET['success'])): ?>
        <div class="ac-success">
            <?php
                switch ($_GET['success']) {
                    case 'unfollowed': echo "Successfully unfollowed user."; break;
                    case 'removed':    echo "Follower removed."; break;
                    case 'requested': echo "Follow request sent!"; break;
                    case 'followed':  echo "You are now following this user!"; break;
                    default:          echo "Profile updated successfully!";
                }
            ?>
        </div>
    <?php endif; ?>
 
    <!-- Settings form -->
    <form action="update_account.php" method="POST" class="ac-form">
 
        <div class="ac-field">
            <label class="ac-section-label">Username</label>
            <input class="username-input" type="text" name="username"
                   value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>
 
        <div class="ac-field">
            <label class="ac-section-label">Profile Theme Color</label>
            <div class="ac-color-row">
                <input type="color" name="profile_color"
                       value="<?= htmlspecialchars($user['profile_color'] ?? '#1f5077') ?>"
                       class="ac-color-input">
                <span class="ac-color-hint">Pick a colour for your public profile</span>
            </div>
        </div>
 
        <div class="ac-field">
            <label class="ac-section-label">Account Privacy</label>
            <select name="is_private" class="account-privacy-input">
                <option value="0" <?= ($user['is_private'] == 0) ? 'selected' : '' ?>>
                    Public (everyone can see my activity)
                </option>
                <option value="1" <?= ($user['is_private'] == 1) ? 'selected' : '' ?>>
                    Private (only followers see my activity)
                </option>
            </select>
        </div>
 
        <div class="ac-row">
            <div class="ac-field">
                <label class="ac-section-label">Age</label>
                <input class="account-input age" type="number" name="age"
                       value="<?= htmlspecialchars($user['age'] ?? '') ?>">
            </div>
            <div class="ac-field">
                <label class="ac-section-label">Hometown</label>
                <input class="account-input hometown" type="text" name="from"
                       value="<?= htmlspecialchars($user['hometown'] ?? '') ?>">
            </div>
        </div>
 
        <div class="ac-field">
            <label class="ac-section-label">Bio</label>
            <textarea class="account-input" name="bio" rows="3"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
        </div>
 
        <div class="ac-field">
            <label class="ac-section-label">My Interests <span class="ac-hint">(comma separated)</span></label>
            <input class="account-input" type="text" name="selected_hobbies"
                   value="<?= htmlspecialchars($user['hobbies'] ?? '') ?>"
                   placeholder="Cooking, Gaming, Lego">
        </div>
 
        <div class="account-save">
            <button type="submit" class="account-save-button">Save Changes</button>
        </div>
 
    </form>
 
    <hr class="ac-divider">
 
    <!-- Followers + Following -->
    <div class="ac-follow-cols">
 
        <div class="ac-follow-col">
            <div class="ac-follow-col-header">
                <span class="ac-section-label">Followers (<?= count($followers) ?>)</span>
                <?php if ($user['is_private'] == 1): ?>
                    <a href="follow_requests.php" class="ac-manage-btn">
                        Manage Requests<?= ($reqCount > 0) ? " ($reqCount)" : "" ?>
                    </a>
                <?php endif; ?>
            </div>
            <ul class="ac-follow-list">
                <?php if (empty($followers)): ?>
                    <li class="ac-follow-empty">No followers yet.</li>
                <?php else: ?>
                    <?php foreach ($followers as $f): ?>
                        <li class="ac-follow-item">
                            <a href="profile.php?id=<?= $f['id'] ?>" class="ac-follow-name">
                                @<?= htmlspecialchars($f['username']) ?>
                            </a>
                            <form action="circle_action.php" method="POST"
                                  onsubmit="return confirm('Remove this follower?');">
                                <input type="hidden" name="action" value="toggle_follow">
                                <input type="hidden" name="action_type" value="remove_follower">
                                <input type="hidden" name="target_id" value="<?= $f['id'] ?>">
                                <input type="hidden" name="hobby" value="account_redirect">
                                <button type="submit" class="ac-unfollow-btn">Remove</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
 
        <div class="ac-follow-col">
            <div class="ac-follow-col-header">
                <span class="ac-section-label">Following (<?= count($following) ?>)</span>
            </div>
            <ul class="ac-follow-list">
                <?php if (empty($following)): ?>
                    <li class="ac-follow-empty">You aren't following anyone yet.</li>
                <?php else: ?>
                    <?php foreach ($following as $f): ?>
                        <li class="ac-follow-item">
                            <a href="profile.php?id=<?= $f['id'] ?>" class="ac-follow-name">
                                @<?= htmlspecialchars($f['username']) ?>
                            </a>
                            <form action="circle_action.php" method="POST"
                                  onsubmit="return confirm('Unfollow <?= htmlspecialchars($f['username']) ?>?');">
                                <input type="hidden" name="action" value="toggle_follow">
                                <input type="hidden" name="target_id" value="<?= $f['id'] ?>">
                                <input type="hidden" name="hobby" value="account_redirect">
                                <button type="submit" class="ac-unfollow-btn">Unfollow</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
 
    </div>
 
    <hr class="ac-divider">
 
    <!-- Danger zone -->
    <div class="account-delete">
        <h3>Danger Zone</h3>
        <p>Once you delete your account, there is no going back. Please be certain.</p>
        <form action="delete_account.php" method="POST"
              onsubmit="return confirm('Are you absolutely sure? This cannot be undone.');">
            <button type="submit">Delete Account</button>
        </form>
    </div>
 
</div>
</div>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
 