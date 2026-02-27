<?php
session_start();
require_once 'db.php';
require_once 'base.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$currentTab = $_GET['tab'] ?? 'friends';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['follow_id'])) {
    $toFollow = $_POST['follow_id'];
    $stmt = $conn->prepare("INSERT IGNORE INTO user_follows (follower_id, followed_id) VALUES (?, ?)");
    $stmt->execute([$userId, $toFollow]);
    header("Location: activity.php?tab=" . $currentTab . "&success=followed");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_event_id'])) {
    $eventId = $_POST['join_event_id'];
    $stmt = $conn->prepare("INSERT IGNORE INTO user_events (uid, eid) VALUES (?, ?)");
    $stmt->execute([$userId, $eventId]);
    header("Location: activity.php?tab=" . $currentTab . "&success=joined");
    exit();
}

if ($currentTab === 'global') {
    $feedStmt = $conn->prepare("
        SELECT DISTINCT 'module_progress' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, l.last_visited AS activity_date, l.complete AS status,
        (SELECT COUNT(*) FROM user_follows WHERE follower_id = ? AND followed_id = u.id) AS is_following
        FROM log l JOIN users u ON l.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN module m ON l.mid = m.id WHERE p.is_private = 0
        UNION
        SELECT DISTINCT 'event' AS activity_type, u.id AS user_id, u.username, p.profile_color, e.id AS target_id, e.title AS target_name, e.location AS extra_info, e.created_at AS activity_date, 1 AS status,
        (SELECT COUNT(*) FROM user_follows WHERE follower_id = ? AND followed_id = u.id) AS is_following
        FROM events e JOIN users u ON e.created_by = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE p.is_private = 0 AND e.created_at >= NOW() - INTERVAL 1 DAY
        UNION
        SELECT DISTINCT 'circle' AS activity_type, u.id AS user_id, u.username, p.profile_color, c.circle_id AS target_id, c.name AS target_name, c.color AS extra_info, c.created_at AS activity_date, 1 AS status,
        (SELECT COUNT(*) FROM user_follows WHERE follower_id = ? AND followed_id = u.id) AS is_following
        FROM circle c JOIN users u ON c.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE p.is_private = 0
        UNION
        SELECT DISTINCT 'module_created' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, m.created_at AS activity_date, 1 AS status,
        (SELECT COUNT(*) FROM user_follows WHERE follower_id = ? AND followed_id = u.id) AS is_following
        FROM module m JOIN users u ON m.cid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE p.is_private = 0
        ORDER BY activity_date DESC LIMIT 50
    ");
    $feedStmt->execute([$userId, $userId, $userId, $userId]);

} elseif ($currentTab === 'followers') {
    $feedStmt = $conn->prepare("
        SELECT DISTINCT 'follower_list' AS activity_type, u.id AS user_id, u.username, p.profile_color, 
        (SELECT COUNT(*) FROM user_follows WHERE follower_id = ? AND followed_id = u.id) AS is_following, 
        uf.created_at AS activity_date, '' AS target_name, '' AS target_id, '' AS extra_info, 1 AS status 
        FROM user_follows uf JOIN users u ON uf.follower_id = u.id LEFT JOIN user_profiles p ON u.id = p.user_id 
        WHERE uf.followed_id = ? ORDER BY uf.created_at DESC
    ");
    $feedStmt->execute([$userId, $userId]);

} elseif ($currentTab === 'me') {
    $feedStmt = $conn->prepare("
        SELECT DISTINCT 'module_progress' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, l.last_visited AS activity_date, l.complete AS status, 0 AS is_following
        FROM log l JOIN users u ON l.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN module m ON l.mid = m.id WHERE l.uid = ?
        UNION
        SELECT DISTINCT 'circle' AS activity_type, u.id AS user_id, u.username, p.profile_color, c.circle_id AS target_id, c.name AS target_name, c.color AS extra_info, c.created_at AS activity_date, 1 AS status, 0 AS is_following
        FROM circle c JOIN users u ON c.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE c.uid = ?
        UNION
        SELECT DISTINCT 'module_created' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, m.created_at AS activity_date, 1 AS status, 0 AS is_following
        FROM module m JOIN users u ON m.cid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE m.cid = ?
        ORDER BY activity_date DESC LIMIT 50
    ");
    $feedStmt->execute([$userId, $userId, $userId]);

} else {
    $feedStmt = $conn->prepare("
        SELECT DISTINCT 'module_progress' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, l.last_visited AS activity_date, l.complete AS status, 1 AS is_following
        FROM log l JOIN users u ON l.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN module m ON l.mid = m.id JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ?
        UNION
        SELECT DISTINCT 'event' AS activity_type, u.id AS user_id, u.username, p.profile_color, e.id AS target_id, e.title AS target_name, e.location AS extra_info, e.created_at AS activity_date, 1 AS status, 1 AS is_following
        FROM events e JOIN users u ON e.created_by = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ?
        UNION
        SELECT DISTINCT 'circle' AS activity_type, u.id AS user_id, u.username, p.profile_color, c.circle_id AS target_id, c.name AS target_name, c.color AS extra_info, c.created_at AS activity_date, 1 AS status, 1 AS is_following
        FROM circle c JOIN users u ON c.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ?
        UNION
        SELECT DISTINCT 'module_created' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, m.created_at AS activity_date, 1 AS status, 1 AS is_following
        FROM module m JOIN users u ON m.cid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ?
        ORDER BY activity_date DESC LIMIT 50
    ");
    $feedStmt->execute([$userId, $userId, $userId, $userId]);
}
$activities = $feedStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Feed</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
    <style>
        .activity-feed-list { display: flex; flex-direction: column; gap: 15px; }
        .activity-feed-item { background-color: white; border-radius: 10px; padding: 15px 20px; display: flex; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .activity-avatar { width: 50px; height: 50px; border-radius: 50%; margin-right: 15px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; text-decoration: none; }
        .activity-content { flex-grow: 1; color: #333; }
        .activity-content a { text-decoration: none; font-weight: bold; color: #1f5077; }
        .activity-date { color: #999; font-size: 14px; margin-left: 15px; }
        .tab-container { display: flex; justify-content: center; gap: 8px; margin-bottom: 30px; }
        .tab-btn { padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: bold; color: #1f5077; background-color: rgba(255,255,255,0.6); font-size: 13px; }
        .tab-btn.active { background-color: #1f5077; color: white; }
        .action-btn { background-color: #1f5077; color: white; border: none; padding: 6px 12px; border-radius: 15px; font-size: 11px; cursor: pointer; transition: 0.3s; }
        .action-btn:hover { opacity: 0.8; }
        .join-btn { background-color: #28a745; }
        .following-label { color: #888; font-size: 11px; font-weight: bold; }
    </style>
</head>
<body class="activity-body">
    <div class="activity-page-container">
        <div class="tab-container">
            <a href="activity.php?tab=friends" class="tab-btn <?= $currentTab === 'friends' ? 'active' : '' ?>">My Friends</a>
            <a href="activity.php?tab=global" class="tab-btn <?= $currentTab === 'global' ? 'active' : '' ?>">Global Activity</a>
            <a href="activity.php?tab=followers" class="tab-btn <?= $currentTab === 'followers' ? 'active' : '' ?>">New Followers</a>
            <a href="activity.php?tab=me" class="tab-btn <?= $currentTab === 'me' ? 'active' : '' ?>">My Activity</a>
        </div>

        <div class="activity-feed-list">
            <?php if (empty($activities)): ?>
                <div style="background-color: white; padding: 40px; border-radius: 10px; text-align: center;"><h3>No activity found.</h3></div>
            <?php else: ?>
                <?php foreach ($activities as $act): 
                    $dateStr = date('M j, Y', strtotime($act['activity_date']));
                    $avatarColor = !empty($act['profile_color']) ? $act['profile_color'] : '#' . substr(md5($act['username']), 0, 6);
                    
                    if ($act['activity_type'] === 'module_progress') {
                        $actionText = ($act['status'] == 1) ? "completed the module" : "started the module";
                    } elseif ($act['activity_type'] === 'module_created') {
                        $actionText = "published a new module";
                    } elseif ($act['activity_type'] === 'circle') {
                        $actionText = "created the circle";
                    } elseif ($act['activity_type'] === 'event') {
                        $actionText = "scheduled the event";
                    } elseif ($act['activity_type'] === 'follow') {
                        $actionText = "started following";
                    } else {
                        $actionText = "is following you!";
                    }
                ?>
                    <div class="activity-feed-item">
                        <a href="profile.php?id=<?= $act['user_id'] ?>" class="activity-avatar" style="background-color: <?= $avatarColor ?>;"><?= strtoupper(substr($act['username'], 0, 1)) ?></a>
                        <div class="activity-content">
                            <a href="profile.php?id=<?= $act['user_id'] ?>">@<?= htmlspecialchars($act['username']) ?></a> 
                            <?= $actionText ?> 
                            <a href="#"><?= htmlspecialchars($act['target_name']) ?></a>
                        </div>
                        
                        <div class="activity-actions" style="margin-left: 15px;">
                            <?php if ($act['user_id'] !== $userId): ?>
                                <?php if ($act['activity_type'] === 'event'): ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="join_event_id" value="<?= $act['target_id'] ?>">
                                        <button type="submit" class="action-btn join-btn">Join Event</button>
                                    </form>
                                <?php elseif ($act['is_following'] > 0): ?>
                                    <span class="following-label">Following ✓</span>
                                <?php else: ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="follow_id" value="<?= $act['user_id'] ?>">
                                        <button type="submit" class="action-btn">Follow</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="activity-date"><?= $dateStr ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>