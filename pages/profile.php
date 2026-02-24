<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/base.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$myId = $_SESSION['user']['id'];
$targetId = $_GET['id'] ?? $myId;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_follow'])) {
    $stmt = $conn->prepare("SELECT 1 FROM user_follows WHERE follower_id = ? AND followed_id = ?");
    $stmt->execute([$myId, $targetId]);
    
    if ($stmt->fetch()) {
        $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND followed_id = ?")->execute([$myId, $targetId]);
    } else {
        $conn->prepare("INSERT INTO user_follows (follower_id, followed_id) VALUES (?, ?)")->execute([$myId, $targetId]);
    }
    header("Location: profile.php?id=" . $targetId);
    exit();
}

$stmt = $conn->prepare("
    SELECT u.username, u.age, p.hometown, p.bio, p.hobbies, p.profile_color 
    FROM users u 
    LEFT JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.id = ?
");
$stmt->execute([$targetId]);
$profileUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileUser) {
    die("<h2 style='color:white; text-align:center;'>User not found.</h2>");
}

$bgColor = $profileUser['profile_color'] ?? '#1f5077';
$hobbiesArr = $profileUser['hobbies'] ? explode(', ', $profileUser['hobbies']) : [];

$isFollowing = false;
if ($myId != $targetId) {
    $fStmt = $conn->prepare("SELECT 1 FROM user_follows WHERE follower_id = ? AND followed_id = ?");
    $fStmt->execute([$myId, $targetId]);
    $isFollowing = $fStmt->fetch() ? true : false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($profileUser['username']) ?>'s Profile</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>
<body class="profile-body">
    <div class="profile-container">
        
        <div style="background-color: <?= htmlspecialchars($bgColor) ?>; padding: 40px; border-radius: 15px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; background-color: white; border-radius: 50%; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: bold; color: <?= htmlspecialchars($bgColor) ?>;">
                <?= strtoupper(substr($profileUser['username'], 0, 1)) ?>
            </div>
            <h1 style="color: white; margin: 0; font-size: 32px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);"><?= htmlspecialchars($profileUser['username']) ?></h1>
            
            <?php if ($myId != $targetId): ?>
                <form method="POST" style="margin-top: 15px;">
                    <input type="hidden" name="toggle_follow" value="1">
                    <?php if ($isFollowing): ?>
                        <button type="submit" style="background-color: white; color: #333; border: none; padding: 8px 20px; border-radius: 20px; font-weight: bold; cursor: pointer;">Following ✓</button>
                    <?php else: ?>
                        <button type="submit" style="background-color: transparent; color: white; border: 2px solid white; padding: 8px 20px; border-radius: 20px; font-weight: bold; cursor: pointer;">+ Follow</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>

        <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: #333;">About Me</h3>
            <p><strong>Age:</strong> <?= htmlspecialchars($profileUser['age'] ?? 'Not specified') ?></p>
            <p><strong>Hometown:</strong> <?= htmlspecialchars($profileUser['hometown'] ?? 'Not specified') ?></p>
            <p><strong>Bio:</strong> <?= htmlspecialchars($profileUser['bio'] ?? 'No bio yet.') ?></p>
        </div>

        <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: #333;">Interests & Circles</h3>
            <?php if (empty($hobbiesArr)): ?>
                <p>No interests added yet.</p>
            <?php else: ?>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php foreach ($hobbiesArr as $hobby): ?>
                        <span style="background-color: #eee; padding: 5px 15px; border-radius: 15px; font-size: 14px; font-weight: bold; color: #555;"><?= htmlspecialchars($hobby) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>