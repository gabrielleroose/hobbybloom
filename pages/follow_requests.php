<?php
session_start();
require_once 'db.php';
require_once 'base.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$myId = $_SESSION['user']['id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['follower_id'])) {
    $followerId = (int)$_POST['follower_id'];
    
    if ($_POST['action'] === 'approve') {
        $stmt = $conn->prepare("UPDATE user_follows SET status = 'accepted' WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$followerId, $myId]);
        $message = "Request accepted! ✅";
    } elseif ($_POST['action'] === 'decline') {
        $stmt = $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND followed_id = ?");
        $stmt->execute([$followerId, $myId]);
        $message = "Request declined. ❌";
    }
}

$stmt = $conn->prepare("
    SELECT u.id, u.username, p.profile_color 
    FROM users u 
    JOIN user_follows f ON u.id = f.follower_id 
    JOIN user_profiles p ON u.id = p.user_id
    WHERE f.followed_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC
");
$stmt->execute([$myId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Follow Requests | HobbyBloom</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>
<body class="account-body">
    <div class="account-main-container">
        <div style="background-color: #1f5077; padding: 30px; border-radius: 15px; text-align: center; margin-bottom: 30px;">
            <h1 style="color: white; margin: 0;">Follow Requests</h1>
            <p style="color: #eee;">Manage who can see your private profile.</p>
        </div>

        <?php if ($message): ?>
            <div style="background: rgba(255,255,255,0.2); color: white; padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div style="width: 100%; max-width: 600px; margin: 0 auto;">
            <?php if (empty($requests)): ?>
                <div style="background: white; padding: 40px; border-radius: 15px; text-align: center; color: #666;">
                    <p>No pending follow requests at the moment.</p>
                    <a href="account.php" style="color: #1f5077; text-decoration: none; font-weight: bold;">Return to Settings</a>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $r): 
                    $color = $r['profile_color'] ?? '#1f5077';
                ?>
                    <div style="background: white; padding: 20px; border-radius: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= htmlspecialchars($color) ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                <?= strtoupper(substr($r['username'], 0, 1)) ?>
                            </div>
                            <a href="profile.php?id=<?= $r['id'] ?>" style="text-decoration: none; color: #333; font-weight: bold;">
                                @<?= htmlspecialchars($r['username']) ?>
                            </a>
                        </div>
                        
                        <form method="POST" style="display: flex; gap: 10px;">
                            <input type="hidden" name="follower_id" value="<?= $r['id'] ?>">
                            <button type="submit" name="action" value="approve" style="background: #27ae60; color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-weight: bold;">Accept</button>
                            <button type="submit" name="action" value="decline" style="background: #e74c3c; color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-weight: bold;">Decline</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>