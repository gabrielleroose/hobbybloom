<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'db.php';
require_once 'base.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$currentTab = $_GET['tab'] ?? 'friends';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Feed</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
    <style>
        .activity-feed-list { display: flex; flex-direction: column; gap: 15px; }
        .activity-feed-item {
            background-color: white; border-radius: 10px; padding: 15px 20px;
            display: flex; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s;
        }
        .activity-feed-item:hover { transform: scale(1.02); }
        .activity-avatar {
            width: 50px; height: 50px; border-radius: 50%; margin-right: 15px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 20px; text-decoration: none;
        }
        .activity-content { flex-grow: 1; color: #333; font-size: 16px; }
        .activity-content a.user-link { color: #333; text-decoration: none; font-weight: bold; }
        .activity-content a.target-link { color: #1f5077; text-decoration: none; font-weight: bold; }
        .activity-content a:hover { text-decoration: underline; }
        .activity-date { color: #999; font-size: 14px; white-space: nowrap; margin-left: 15px; }
        
        .feed-badge {
            display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 12px;
            font-weight: bold; margin-left: 10px; color: #333; text-transform: capitalize;
        }
        .badge-beginner { background-color: #a8d0e6; }
        .badge-intermediate { background-color: #ffd700; }
        .badge-expert { background-color: #ff9999; }
        .badge-event { background-color: #e6e6fa; }
        .badge-circle { background-color: #9370db; color: white; }
        .badge-publish { background-color: #90ee90; }
        .badge-follow { background-color: #ffdab9; }

        .tab-container { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
        .tab-btn {
            padding: 10px 25px; border-radius: 25px; text-decoration: none; font-weight: bold;
            font-size: 16px; color: #1f5077; background-color: rgba(255,255,255,0.6); transition: all 0.3s ease;
        }
        .tab-btn:hover { background-color: rgba(255,255,255,0.9); }
        .tab-btn.active { background-color: #1f5077; color: white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="activity-body">

    <div class="activity-page-container">

        <div style="background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 15px; padding: 20px; text-align: center; margin-bottom: 20px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);">
            <h1 style="color: white; margin: 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">Activity Feed</h1>
            <p style="color: #eee; margin-top: 5px; font-style: italic;">See what's happening around HobbyBloom.</p>
        </div>

        <div class="tab-container">
            <a href="activity.php?tab=friends" class="tab-btn <?= $currentTab === 'friends' ? 'active' : '' ?>">My Friends</a>
            <a href="activity.php?tab=me" class="tab-btn <?= $currentTab === 'me' ? 'active' : '' ?>">My Activity</a>
        </div>

        <div class="activity-feed-list">
            <?php if (empty($activities)): ?>
                <div style="background-color: white; padding: 40px; border-radius: 10px; text-align: center; color: #666;">
                    <?php if ($currentTab === 'me'): ?>
                        <h3 style="margin-top: 0;">No activity yet.</h3>
                        <p>Start a module or create a circle to see it here!</p>
                        <a href="modules_display.php" class="light-btn" style="display: inline-block; margin-top: 10px; text-decoration: none; background-color: #a8d0e6; color: #333;">Browse Modules</a>
                    <?php else: ?>
                        <h3 style="margin-top: 0;">It's quiet in here...</h3>
                        <p>None of your friends have recent activity.</p>
                        <a href="circles.php" class="light-btn" style="display: inline-block; margin-top: 10px; text-decoration: none; background-color: #a8d0e6; color: #333;">Find people in Circles</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($activities as $act): 
                    $dateStr = date('M j, Y', strtotime($act['activity_date']));
                    
                    // Uses profile_color if available, otherwise defaults to a color generated from the username
                    $avatarColor = !empty($act['profile_color']) ? $act['profile_color'] : '#' . substr(md5($act['username']), 0, 6);
                    
                    if ($act['activity_type'] === 'module_progress') {
                        $actionText = $act['status'] == 1 ? "completed the module" : "started the module";
                        $targetLink = "module.php?id=" . $act['target_id'];
                        $badgeClass = 'badge-' . strtolower($act['extra_info']);
                        $extraHtml = "<span class='feed-badge {$badgeClass}'>" . htmlspecialchars($act['extra_info']) . "</span>";
                    } elseif ($act['activity_type'] === 'module_created') {
                        $actionText = "published a new module:";
                        $targetLink = "module.php?id=" . $act['target_id'];
                        $extraHtml = "<span class='feed-badge badge-publish'>✨ New Module</span>";
                    } elseif ($act['activity_type'] === 'circle') {
                        $actionText = "created a new circle:";
                        $targetLink = "circle_detail.php?hobby=" . urlencode($act['target_name']);
                        $extraHtml = "<span class='feed-badge badge-circle'>⭕ Circle</span>";
                    } elseif ($act['activity_type'] === 'follow') {
                        $actionText = "started following";
                        $targetLink = "profile.php?id=" . $act['target_id'];
                        $extraHtml = "<span class='feed-badge badge-follow'>🤝 New Connection</span>";
                    } else {
                        $actionText = "scheduled a new calendar event:";
                        $targetLink = "calendar.php";
                        $extraHtml = "<span class='feed-badge badge-event'>📅 Event</span>";
                    }
                ?>
                    <div class="activity-feed-item">
                        <a href="profile.php?id=<?= $act['user_id'] ?>" class="activity-avatar" style="background-color: <?= $avatarColor ?>;">
                            <?= strtoupper(substr($act['username'], 0, 1)) ?>
                        </a>
                        
                        <div class="activity-content">
                            <a href="profile.php?id=<?= $act['user_id'] ?>" class="user-link">
                                <?= $currentTab === 'me' ? 'You' : '@' . htmlspecialchars($act['username']) ?>
                            </a> 
                            <?= $actionText ?> 
                            <a href="<?= $targetLink ?>" class="target-link"><?= htmlspecialchars($act['target_name']) ?></a>
                            <?= $extraHtml ?>
                        </div>

                        <div class="activity-date">
                            <?= $dateStr ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>