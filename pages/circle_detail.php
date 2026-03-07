<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/base.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$currentHobby = $_GET['hobby'] ?? 'General';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['chat_message'])) {
    $msg = trim($_POST['chat_message']);
    $ins = $conn->prepare("INSERT INTO circle_messages (hobby_name, user_id, message) VALUES (?, ?, ?)");
    $ins->execute([$currentHobby, $userId, $msg]);
    header("Location: circle_detail.php?hobby=" . urlencode($currentHobby));
    exit();
}

$circleStmt = $conn->prepare("SELECT * FROM circle WHERE name = ?");
$circleStmt->execute([$currentHobby]);
$circleData = $circleStmt->fetch(PDO::FETCH_ASSOC);

$headerColor = $circleData['color'] ?? '#1f5077';
$creatorId = $circleData['uid'] ?? null;
$circleId = $circleData['circle_id'] ?? null;

$stmt = $conn->prepare("SELECT hobbies FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$myHobbiesStr = $stmt->fetchColumn();
$myHobbiesArr = $myHobbiesStr ? explode(', ', $myHobbiesStr) : [];
$isMember = in_array($currentHobby, $myHobbiesArr);

$circleModules = [];
$stmt = $conn->prepare("SELECT id, name, description, exp_level FROM module WHERE name LIKE ? OR description LIKE ?");
$stmt->execute(["%$currentHobby%", "%$currentHobby%"]);
$circleModules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msgStmt = $conn->prepare("
    SELECT cm.id, cm.message, cm.created_at, u.id AS user_id, u.username, p.profile_color
    FROM circle_messages cm
    JOIN users u ON cm.user_id = u.id
    LEFT JOIN user_profiles p ON u.id = p.user_id
    WHERE cm.hobby_name = ?
    ORDER BY cm.created_at ASC
");
$msgStmt->execute([$currentHobby]);
$chatMessages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

$memStmt = $conn->prepare("
    SELECT u.id, u.username, p.profile_color,
           (SELECT 1 FROM user_follows WHERE follower_id = ? AND followed_id = u.id LIMIT 1) as am_following
    FROM users u
    JOIN user_profiles p ON u.id = p.user_id
    WHERE p.hobbies LIKE ? AND u.id != ?
    GROUP BY u.id
");
$memStmt->execute([$userId, "%$currentHobby%", $userId]);
$members = $memStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentHobby) ?> Circle</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
    <style>
        .chat-container {
            height: 450px !important; 
            display: flex;
            flex-direction: column;
            padding: 20px;
            margin-top: 30px;
        }
        .chat-box {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 15px;
            padding-right: 10px;
        }
        .member-row {
            display: flex;
            gap: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .chat-input {
            flex: 1;
            padding: 10px 15px;
            border-radius: 20px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
        }
        .member-avatar, .chat-avatar {
            width: 30px; height: 30px;
            border-radius: 50%;
            display: inline-block;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .chat-message-container {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .chat-message-container.mine { 
            flex-direction: row-reverse;
        }
        .chat-content {
            background: rgba(255,255,255,0.15);
            padding: 8px 12px;
            border-radius: 12px;
            max-width: 80%;
        }
        .chat-message-container.mine .chat-content {
            background: <?= htmlspecialchars($headerColor) ?>;
            color: white;
        }
        .chat-message-container.mine .chat-content { background: <?= htmlspecialchars($headerColor) ?>; } 
        
        .member-list { background-color: white; border-radius: 10px; padding: 15px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .member-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; }
        .member-row:last-child { border-bottom: none; }
        .member-avatar { width: 30px; height: 30px; border-radius: 50%; margin-right: 10px; border: 1px solid rgba(0,0,0,0.1); }
        
        .category-badge {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            color: white;
            font-size: 0.8rem;
            font-weight: bold;
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: inline-block;
        }
    </style>
</head>
<body class="circle-detail-body">
    <div class="circle-detail-main-container">
        <div class="detail-container-inside">
        
            <div style="background-color: <?= htmlspecialchars($headerColor) ?>; padding: 30px; border-radius: 15px; text-align: center; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; justify-content: center; gap: 15px; flex-wrap: wrap;">
                    <h1 style="color: white; margin: 0; font-size: 32px;"><?= htmlspecialchars($currentHobby) ?> Circle</h1>
                    <span class="category-badge">
                        <?= htmlspecialchars($circleData['category'] ?? 'General') ?>
                    </span>
                </div>
                <p style="color: #eee; margin-top: 10px;"><?= htmlspecialchars($circleData['description'] ?? 'Connect and share!') ?></p>
                
                <form action="circle_action.php" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="action" value="toggle_circle">
                    <input type="hidden" name="hobby" value="<?= htmlspecialchars($currentHobby) ?>">
                    <button type="submit" class="light-btn" style="background-color: white; color: #333; border-radius: 20px; padding: 8px 20px; font-weight: bold;">
                        <?= $isMember ? '✓ Member (Leave)' : '+ Join Circle' ?>
                    </button>
                </form>

                <div style="margin-top: 10px; display: flex; justify-content: center; gap: 10px;">
                    <?php if ($creatorId == $userId): ?>
                        <a href="edit_circle.php?id=<?= $circleId ?>" 
                            style="background: #1f5077; color: white; border: 1px solid white; padding: 8px 16px; border-radius: 10px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 14px;">
                            Edit Circle
                        </a>
                    <?php else: ?>
                        <button id="reportCircleBtn" 
                            style="background:#ff4d4d; color:white; border:none; padding:8px 16px; border-radius:10px; font-weight:bold; cursor:pointer;">
                            Report Circle
                        </button>
                    <?php endif; ?>
                </div>
            <h2>Circle Members</h2>
            <div class="member-list">
                <?php if (empty($members)): ?> 
                    <p style="color: #666; text-align: center; margin: 0;">No other members yet.</p> 
                <?php else: ?>
                    <?php foreach ($members as $mem): 
                        $mColor = !empty($mem['profile_color']) ? $mem['profile_color'] : '#' . substr(md5($mem['username']), 0, 6);
                    ?>
                        <div class="member-row">
                            <div style="display: flex; align-items: center;">
                                <div class="member-avatar" style="background-color: <?= $mColor ?>;"></div>
                                <a href="profile.php?id=<?= $mem['id'] ?>" style="color: #333; text-decoration: none;"><strong><?= htmlspecialchars($mem['username']) ?></strong></a>
                            </div>
                            <form action="circle_action.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="toggle_follow">
                                <input type="hidden" name="target_id" value="<?= $mem['id'] ?>">
                                <input type="hidden" name="hobby" value="<?= htmlspecialchars($currentHobby) ?>">
                                <button type="submit" class="light-btn" style="font-size: 12px;">
                                    <?= $mem['am_following'] ? 'Following' : 'Follow' ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h2>Modules in this Circle</h2> 
            <?php if (empty($circleModules)): ?>
                <div class="info-box" style="background-color: #1f5077; padding: 20px; border-radius: 10px; color: white; text-align: center;">
                    <p>No modules found for this circle yet.</p><br>
                    <a href="createForm.php" class="light-btn" style="text-decoration: none; background: white; color: #333; padding: 5px 15px; border-radius: 5px;">Create Module</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px;">
                    <?php foreach ($circleModules as $mod): ?>
                        <div class="info-card" style="background-color: #1f5077; color: white; border: none; padding: 15px; border-radius: 10px;">
                            <div class="card-row-between" style="display: flex; justify-content: space-between;">
                                <h4 style="color: white; margin: 0;"><?= htmlspecialchars($mod['name']) ?></h4>
                                <span style="background-color: white; color: #1f5077; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold;"><?= htmlspecialchars($mod['exp_level']) ?></span>
                            </div>
                            <p style="color: #ccc; font-size: 13px; margin: 10px 0;"><?= htmlspecialchars($mod['description']) ?></p>
                            <a href="module.php?id=<?= $mod['id'] ?>" class="light-btn" style="text-decoration: none; background: white; color: #333; padding: 5px 10px; border-radius: 5px; font-size: 12px;">View Module</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="chat-container">
                <h2 style="margin: 0 0 15px 0; color: white;">Circle Discussion</h2> 
                <div class="chat-box" id="chatBox">
                    <?php foreach ($chatMessages as $msg): 
                        $isMine = ($msg['user_id'] == $userId); 
                        $c = !empty($msg['profile_color']) ? $msg['profile_color'] : '#' . substr(md5($msg['username']), 0, 6);
                    ?>
                        <div class="chat-message-container <?= $isMine ? 'mine' : '' ?>">
                            <div class="chat-avatar" style="background-color: <?= $c ?>;"></div>
                            <div class="chat-content">
                                <small style="display: block; font-weight: bold; font-size: 10px; margin-bottom: 2px; opacity: 0.8;"><?= htmlspecialchars($msg['username']) ?></small>
                                <div style="font-size: 14px;"><?= htmlspecialchars($msg['message']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?> 
                </div>
                <form method="POST" class="chat-input-row">
                    <input type="text" name="chat_message" class="chat-input" placeholder="Type a message..." required autocomplete="off"> 
                    <button type="submit" class="light-btn" style="background-color: white; color: #333; border: none; font-weight: bold; border-radius: 20px; padding: 0 20px;">Send</button>
                </form>
            </div>

        </div> 
    </div> 
    <script>
        const chatBox = document.getElementById('chatBox');
        chatBox.scrollTop = chatBox.scrollHeight;
    </script>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const reportBtn = document.getElementById('reportCircleBtn');
    if(!reportBtn) return;

    reportBtn.addEventListener('click', function() {
        const reason = prompt("Why are you reporting this circle?");
        if(!reason) return;

        fetch('submit_report.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                type: 'circle',
                item_id: <?= json_encode($circleId) ?>,
                reason: reason
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success'){
                alert("Report submitted to moderation.");
            } else {
                alert("Error submitting report: " + (data.message || 'Unknown error'));
            }
        });
    });
});
</script>
</body>
</html>