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
    header("Location: activity.php?tab=followers");
    exit();
}

if ($currentTab === 'me') {
    $feedStmt = $conn->prepare("
        SELECT DISTINCT 'module_progress' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, l.last_visited AS activity_date, l.complete AS status
        FROM log l JOIN users u ON l.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN module m ON l.mid = m.id WHERE l.uid = ?
        UNION
        SELECT DISTINCT 'event' AS activity_type, u.id AS user_id, u.username, p.profile_color, e.id AS target_id, e.title AS target_name, e.location AS extra_info, e.created_at AS activity_date, 1 AS status
        FROM events e JOIN users u ON e.created_by = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE e.created_by = ?
        UNION
        SELECT DISTINCT 'circle' AS activity_type, u.id AS user_id, u.username, p.profile_color, c.circle_id AS target_id, c.name AS target_name, c.color AS extra_info, c.created_at AS activity_date, 1 AS status
        FROM circle c JOIN users u ON c.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE c.uid = ?
        UNION
        SELECT DISTINCT 'module_created' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, m.created_at AS activity_date, 1 AS status
        FROM module m JOIN users u ON m.cid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE m.cid = ?
        UNION
        SELECT DISTINCT 'follow' AS activity_type, u1.id AS user_id, u1.username, p.profile_color, u2.id AS target_id, u2.username AS target_name, '' AS extra_info, uf.created_at AS activity_date, 1 AS status
        FROM user_follows uf JOIN users u1 ON uf.follower_id = u1.id LEFT JOIN user_profiles p ON u1.id = p.user_id JOIN users u2 ON uf.followed_id = u2.id WHERE uf.follower_id = ?
        ORDER BY activity_date DESC LIMIT 50
    ");
    $feedStmt->execute([$userId, $userId, $userId, $userId, $userId]);
} elseif ($currentTab === 'followers') {
    $feedStmt = $conn->prepare("
        SELECT DISTINCT 'follower_list' AS activity_type, u.id AS user_id, u.username, p.profile_color, 
               (SELECT COUNT(*) FROM user_follows WHERE follower_id = ? AND followed_id = u.id) AS am_i_following,
               uf.created_at AS activity_date, '' AS target_name, '' AS target_id, '' AS extra_info, 1 AS status
        FROM user_follows uf
        JOIN users u ON uf.follower_id = u.id
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE uf.followed_id = ?
        ORDER BY uf.created_at DESC
    ");
    $feedStmt->execute([$userId, $userId]);
} else {
    $feedStmt = $conn->prepare("
        SELECT DISTINCT 'module_progress' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, l.last_visited AS activity_date, l.complete AS status
        FROM log l JOIN users u ON l.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN module m ON l.mid = m.id JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ?
        UNION
        SELECT DISTINCT 'event' AS activity_type, u.id AS user_id, u.username, p.profile_color, e.id AS target_id, e.title AS target_name, e.location AS extra_info, e.created_at AS activity_date, 1 AS status
        FROM events e JOIN users u ON e.created_by = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ?
        UNION
        SELECT DISTINCT 'circle' AS activity_type, u.id AS user_id, u.username, p.profile_color, c.circle_id AS target_id, c.name AS target_name, c.color AS extra_info, c.created_at AS activity_date, 1 AS status
        FROM circle c JOIN users u ON c.uid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ?
        UNION
        SELECT DISTINCT 'module_created' AS activity_type, u.id AS user_id, u.username, p.profile_color, m.id AS target_id, m.name AS target_name, m.exp_level AS extra_info, m.created_at AS activity_date, 1 AS status
        FROM module m JOIN users u ON m.cid = u.id LEFT JOIN user_profiles p ON u.id = p.user_id JOIN user_follows uf ON u.id = uf.followed_id WHERE uf.follower_id = ?
        UNION
        SELECT DISTINCT 'follow' AS activity_type, u1.id AS user_id, u1.username, p.profile_color, u2.id AS target_id, u2.username AS target_name, '' AS extra_info, uf_act.created_at AS activity_date, 1 AS status
        FROM user_follows uf_act JOIN users u1 ON uf_act.follower_id = u1.id LEFT JOIN user_profiles p ON u1.id = p.user_id JOIN users u2 ON uf_act.followed_id = u2.id JOIN user_follows uf ON u1.id = uf.followed_id WHERE uf.follower_id = ?
        ORDER BY activity_date DESC LIMIT 50
    ");
    $feedStmt->execute([$userId, $userId, $userId, $userId, $userId]);
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
        .tab-container { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
        .tab-btn { padding: 10px 25px; border-radius: 25px; text-decoration: none; font-weight: bold; color: #1f5077; background-color: rgba(255,255,255,0.6); }
        .tab-btn.active { background-color: #1f5077; color: white; }
        .follow-back-btn { background-color: #1f5077; color: white; border: none; padding: 6px 12px; border-radius: 15px; font-size: 12px; cursor: pointer; }
    </style>
</head>
<body class="activity-body">
    <div class="activity-page-container">
        <div class="tab-container">
            <a href="activity.php?tab=friends" class="tab-btn <?= $currentTab === 'friends' ? 'active' : '' ?>">My Friends</a>
            <a href="activity.php?tab=followers" class="tab-btn <?= $currentTab === 'followers' ? 'active' : '' ?>">New Followers</a>
            <a href="activity.php?tab=me" class="tab-btn <?= $currentTab === 'me' ? 'active' : '' ?>">My Activity</a>
        </div>

        <div class="activity-feed-list">
            <?php if (empty($activities)): ?>
                <div style="background-color: white; padding: 40px; border-radius: 10px; text-align: center;">
                    <h3>No activity found.</h3>
                </div>
            <?php else: ?>
                <?php foreach ($activities as $act): 
                    $dateStr = date('M j, Y', strtotime($act['activity_date']));
                    $avatarColor = !empty($act['profile_color']) ? $act['profile_color'] : '#' . substr(md5($act['username']), 0, 6);
                ?>
                    <div class="activity-feed-item">
                        <a href="profile.php?id=<?= $act['user_id'] ?>" class="activity-avatar" style="background-color: <?= $avatarColor ?>;">
                            <?= strtoupper(substr($act['username'], 0, 1)) ?>
                        </a>
                        <div class="activity-content">
                            <a href="profile.php?id=<?= $act['user_id'] ?>">@<?= htmlspecialchars($act['username']) ?></a> 
                            <?php if ($act['activity_type'] === 'follower_list'): ?>
                                is following you!
                            <?php else: ?>
                                <?= ($act['activity_type'] === 'follow') ? 'started following' : 'performed an action' ?>
                                <?php if (!empty($act['target_name'])): ?>
                                    <a href="#"><?= htmlspecialchars($act['target_name']) ?></a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($currentTab === 'followers'): ?>
                            <div style="margin-left: 15px;">
                                <?php if ($act['am_i_following'] > 0): ?>
                                    <span style="color: #888; font-size: 12px; font-weight: bold;">Friends ✓</span>
                                <?php else: ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="follow_id" value="<?= $act['user_id'] ?>">
                                        <button type="submit" class="follow-back-btn">Follow Back</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="activity-date"><?= $dateStr ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>