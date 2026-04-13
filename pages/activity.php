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
    SELECT u.first_name, p.is_private 
    FROM users u 
    JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($userData)) {
    header("Location: index.php?onboarding=1");
    exit();
}

$isPrivate = (int)$userData['is_private'];
$currentTab = $_GET['tab'] ?? 'friends';

$reqStmt = $conn->prepare("SELECT COUNT(*) FROM user_follows WHERE followed_id = ? AND status = 'pending'");
$reqStmt->execute([$userId]);
$reqCount = (int)$reqStmt->fetchColumn();

$achievementSQL = "
    SELECT DISTINCT 'achievement' AS activity_type, u.id AS user_id, u.username, p.profile_color, 0 AS target_id, 
    CASE 
        WHEN (SELECT COUNT(*) FROM log WHERE uid = u.id AND complete = 1) >= 5 THEN 'Module Master'
        WHEN (SELECT COUNT(*) FROM circle WHERE uid = u.id) >= 3 THEN 'Circle Architect'
        WHEN p.login_streak >= 7 THEN 'Firestarter'
        WHEN (SELECT COUNT(*) FROM user_follows WHERE follower_id = u.id) >= 5 THEN 'Social Butterfly'
        WHEN (SELECT COUNT(*) FROM circle WHERE uid = u.id) >= 1 THEN 'Community Leader'
        WHEN (SELECT COUNT(*) FROM log WHERE uid = u.id AND complete = 1) >= 1 THEN 'First Steps'
        ELSE 'Hobbyist'
    END AS target_name, 
    '' AS extra_info, NOW() AS activity_date, 1 AS status";

$hasEarnedBadge = "AND (
    (SELECT COUNT(*) FROM log WHERE uid = u.id AND complete = 1) >= 1 OR 
    p.login_streak >= 7 OR 
    (SELECT COUNT(*) FROM user_follows WHERE follower_id = u.id) >= 5 OR 
    (SELECT COUNT(*) FROM circle WHERE uid = u.id) >= 1 OR
    (SELECT COUNT(*) FROM circle WHERE uid = u.id) >= 3
)";

if ($currentTab === 'global') {
    $feedStmt = $conn->prepare("
        SELECT DISTINCT 'module_progress' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, l.last_visited AS activity_date, l.complete AS status,
        (SELECT status FROM user_follows WHERE follower_id = ? AND followed_id = u.id LIMIT 1) AS follow_status
        FROM log l JOIN users u ON l.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN module m ON l.mid = m.id WHERE p.is_private = 0
        UNION
        SELECT DISTINCT 'event' AS activity_type, u.id AS user_id, u.username, p.profile_color, e.id AS target_id, e.title AS target_name, e.location AS extra_info, e.created_at AS activity_date, 1 AS status,
        (SELECT status FROM user_follows WHERE follower_id = ? AND followed_id = u.id LIMIT 1) AS follow_status
        FROM events e JOIN users u ON e.created_by = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE p.is_private = 0 AND e.created_at >= NOW() - INTERVAL 1 DAY
        UNION
        SELECT DISTINCT 'circle' AS activity_type, u.id AS user_id, u.username, p.profile_color, c.circle_id AS target_id, c.name AS target_name, c.color AS extra_info, c.created_at AS activity_date, 1 AS status,
        (SELECT status FROM user_follows WHERE follower_id = ? AND followed_id = u.id LIMIT 1) AS follow_status
        FROM circle c JOIN users u ON c.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE p.is_private = 0
        UNION
        SELECT DISTINCT 'module_created' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, m.created_at AS activity_date, 1 AS status,
        (SELECT status FROM user_follows WHERE follower_id = ? AND followed_id = u.id LIMIT 1) AS follow_status
        FROM module m JOIN users u ON m.cid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE p.is_private = 0
        UNION
        $achievementSQL, (SELECT status FROM user_follows WHERE follower_id = ? AND followed_id = u.id LIMIT 1) AS follow_status
        FROM users u JOIN user_profiles p ON u.id = p.user_id WHERE p.is_private = 0 $hasEarnedBadge
        ORDER BY activity_date DESC LIMIT 50
    ");
    $feedStmt->execute([$userId, $userId, $userId, $userId, $userId]);
} elseif ($currentTab === 'followers') {
    $feedStmt = $conn->prepare("
        SELECT DISTINCT 'follower_list' AS activity_type, u.id AS user_id, u.username, p.profile_color, 
        (SELECT status FROM user_follows WHERE follower_id = ? AND followed_id = u.id LIMIT 1) AS follow_status, 
        uf.created_at AS activity_date, '' AS target_name, '' AS target_id, '' AS extra_info, 1 AS status 
        FROM user_follows uf JOIN users u ON uf.follower_id = u.id LEFT JOIN user_profiles p ON u.id = p.user_id 
        WHERE uf.followed_id = ? AND uf.status = 'accepted' ORDER BY uf.created_at DESC
    ");
    $feedStmt->execute([$userId, $userId]);
} elseif ($currentTab === 'me') {
    $feedStmt = $conn->prepare("
        SELECT DISTINCT 'module_progress' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, l.last_visited AS activity_date, l.complete AS status, NULL AS follow_status
        FROM log l JOIN users u ON l.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN module m ON l.mid = m.id WHERE l.uid = ?
        UNION
        SELECT DISTINCT 'circle' AS activity_type, u.id AS user_id, u.username, p.profile_color, c.circle_id AS target_id, c.name AS target_name, c.color AS extra_info, c.created_at AS activity_date, 1 AS status, NULL AS follow_status
        FROM circle c JOIN users u ON c.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE c.uid = ?
        UNION
        SELECT DISTINCT 'module_created' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, m.created_at AS activity_date, 1 AS status, NULL AS follow_status
        FROM module m JOIN users u ON m.cid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE m.cid = ?
        UNION
        $achievementSQL, NULL AS follow_status
        FROM users u JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ? $hasEarnedBadge
        ORDER BY activity_date DESC LIMIT 50
    ");
    $feedStmt->execute([$userId, $userId, $userId, $userId]);
} else {
    $feedStmt = $conn->prepare("
        SELECT DISTINCT 'module_progress' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, l.last_visited AS activity_date, l.complete AS status, 'accepted' AS follow_status
        FROM log l JOIN users u ON l.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN module m ON l.mid = m.id 
        JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ? AND uf.status = 'accepted'
        UNION
        SELECT DISTINCT 'event' AS activity_type, u.id AS user_id, u.username, p.profile_color, e.id AS target_id, e.title AS target_name, e.location AS extra_info, e.created_at AS activity_date, 1 AS status, 'accepted' AS follow_status
        FROM events e JOIN users u ON e.created_by = u.id LEFT JOIN user_profiles p ON u.id = p.user_id 
        JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ? AND uf.status = 'accepted'
        UNION
        SELECT DISTINCT 'circle' AS activity_type, u.id AS user_id, u.username, p.profile_color, c.circle_id AS target_id, c.name AS target_name, c.color AS extra_info, c.created_at AS activity_date, 1 AS status, 'accepted' AS follow_status
        FROM circle c JOIN users u ON c.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id 
        JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ? AND uf.status = 'accepted'
        UNION
        SELECT DISTINCT 'module_created' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, m.created_at AS activity_date, 1 AS status, 'accepted' AS follow_status
        FROM module m JOIN users u ON m.cid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id 
        JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ? AND uf.status = 'accepted'
        UNION
        $achievementSQL, 'accepted' AS follow_status
        FROM users u JOIN user_profiles p ON u.id = p.user_id 
        JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ? AND uf.status = 'accepted' $hasEarnedBadge
        ORDER BY activity_date DESC LIMIT 50
    ");
    $feedStmt->execute([$userId, $userId, $userId, $userId, $userId]);
}
$activities = $feedStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    body { 
        background-color: #BDC29D;
        margin: 0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .activity-page-container { 
        flex: 1; 
        padding: 40px 20px;
        width: 100%;
        box-sizing: border-box;
    }

    .activity-feed-list { display: flex; flex-direction: column; gap: 15px; max-width: 900px; margin: 0 auto; }
    .activity-feed-item { 
        background-color: white; 
        border-radius: 12px; 
        padding: 20px; 
        display: flex; 
        align-items: center; 
        justify-content: space-between; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
    }
    .achievement-item { border-left: 5px solid #ffd700; background-color: #fffdf2; }
    .activity-main-content { display: flex; align-items: center; flex: 1; }
    .activity-avatar { width: 45px; height: 45px; border-radius: 50%; margin-right: 15px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; text-decoration: none; }
    .activity-text { color: #333; line-height: 1.4; }
    .activity-text a { text-decoration: none; font-weight: 700; color: #1f5077; }
    .activity-date { color: #aaa; font-size: 0.85rem; margin-left: 15px; }
    
    .glass-tab-container { display: flex; justify-content: center; margin: 20px 0 35px 0; }
    .glass-tabs { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 40px; padding: 5px; display: flex; gap: 5px; }
    .tab-btn { padding: 10px 20px; border-radius: 35px; text-decoration: none; font-weight: 600; color: #1f5077; font-size: 0.85rem; }
    .tab-btn.active { background-color: #1f5077; color: white; }
    
    .action-btn { background-color: #1f5077; color: white; border: none; padding: 8px 16px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; cursor: pointer; }

    footer {
        position: relative !important;
        margin-top: auto;
    }
</style>

<div class="activity-page-container">
    
    <?php if ($currentTab === 'followers' && $isPrivate === 1): ?>
        <div style="text-align: center; margin-bottom: 25px;">
            <a href="follow_requests.php" style="background: #ffd700; color: #1f5077; padding: 12px 24px; border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 0.9rem; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                📬 View Pending Follow Requests <?= ($reqCount > 0) ? "($reqCount)" : "" ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="glass-tab-container">
        <div class="glass-tabs">
            <a href="activity.php?tab=friends" class="tab-btn <?= $currentTab === 'friends' ? 'active' : '' ?>">My Friends</a>
            <a href="activity.php?tab=global" class="tab-btn <?= $currentTab === 'global' ? 'active' : '' ?>">Global Activity</a>
            <a href="activity.php?tab=followers" class="tab-btn <?= $currentTab === 'followers' ? 'active' : '' ?>">New Followers</a>
            <a href="activity.php?tab=me" class="tab-btn <?= $currentTab === 'me' ? 'active' : '' ?>">My Activity</a>
        </div>
    </div>

    <div class="activity-feed-list">
        <?php if (empty($activities)): ?>
            <div style="text-align: center; color: #666; padding: 50px;">
                <p>No activity to show here yet! ✨</p>
            </div>
        <?php endif; ?>

        <?php foreach ($activities as $act): 
            $dateStr = date('M j, Y', strtotime($act['activity_date']));
            $avatarColor = !empty($act['profile_color']) ? $act['profile_color'] : '#' . substr(md5($act['username']), 0, 6);
            
            $targetLink = "#";
            $isAchievement = ($act['activity_type'] === 'achievement');

            if ($act['activity_type'] === 'module_progress' || $act['activity_type'] === 'module_created') {
                $actionText = ($act['activity_type'] === 'module_created') ? "published module" : (($act['status'] == 1) ? "completed module" : "started module");
                $targetLink = "module.php?id=" . $act['target_id'];
            } elseif ($act['activity_type'] === 'circle') {
                $actionText = "created the circle";
                $targetLink = "circle_detail.php?hobby=" . urlencode($act['target_name']);
            } elseif ($act['activity_type'] === 'event') {
                $actionText = "scheduled the event";
                $targetLink = "calendar.php";
            } elseif ($act['activity_type'] === 'follower_list') {
                $actionText = "started following you";
            } elseif ($isAchievement) {
                $actionText = "earned the badge";
                $targetLink = "profile.php?id=" . $act['user_id'];
            }
        ?>
            <div class="activity-feed-item <?= $isAchievement ? 'achievement-item' : '' ?>">
                <div class="activity-main-content">
                    <a href="profile.php?id=<?= $act['user_id'] ?>" class="activity-avatar" style="background-color: <?= $avatarColor ?>;"><?= strtoupper(substr($act['username'], 0, 1)) ?></a>
                    <div class="activity-text">
                        <a href="profile.php?id=<?= $act['user_id'] ?>">@<?= htmlspecialchars($act['username']) ?></a> 
                        <span style="color: #666;"> <?= $actionText ?> </span>
                        <?php if ($act['target_name']): ?>
                            <a href="<?= $targetLink ?>" style="<?= $isAchievement ? 'color: #d4af37; font-weight: bold;' : '' ?>">
                                <?= ($isAchievement ? '🏆 ' : '') . htmlspecialchars($act['target_name']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="activity-actions" style="display: flex; align-items: center; gap: 10px;">
                    <?php if ($act['user_id'] !== $userId): ?>
                        <form action="circle_action.php" method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="toggle_follow">
                            <input type="hidden" name="target_id" value="<?= $act['user_id'] ?>">
                            <input type="hidden" name="hobby" value="activity_redirect"> 
                            
                            <?php 
                                $fStatus = $act['follow_status'] ?? null; 
                                $btnText = '+ Follow';
                                $btnClass = '';

                                if ($fStatus === 'accepted') {
                                    $btnText = 'Following ✓';
                                    $btnClass = 'unfollow-btn';
                                } elseif ($fStatus === 'pending') {
                                    $btnText = 'Requested...';
                                    $btnClass = 'unfollow-btn';
                                }
                            ?>

                            <button type="submit" class="action-btn <?= $btnClass ?>">
                                <?= $btnText ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <div class="activity-date"><?= $dateStr ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>