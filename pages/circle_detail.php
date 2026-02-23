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

$circleStmt = $conn->prepare("SELECT color FROM circle WHERE name = ? LIMIT 1");
$circleStmt->execute([$currentHobby]);
$circleData = $circleStmt->fetch(PDO::FETCH_ASSOC);

$hobbyColors = [
    "Cooking" => "#ff9999", "Knitting" => "#e6e6fa", "Lego" => "#ffd700",
    "Sewing" => "#ffb6c1", "Painting" => "#ffdab9", "Hiking" => "#90ee90",
    "Reading" => "#a8d0e6", "Gardening" => "#3cb371", "Baking" => "#f4a460",
    "Meditation" => "#e0ffff", "Music" => "#dda0dd", "Movies" => "#cd5c5c",
    "Gaming" => "#9370db", "Yoga" => "#ffdead"
];

if ($circleData && !empty($circleData['color'])) {
    $headerColor = $circleData['color'];
} else {
    $headerColor = $hobbyColors[$currentHobby] ?? '#1f5077';
}

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
    SELECT cm.message, cm.created_at, u.id AS user_id, u.username 
    FROM circle_messages cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.hobby_name = ?
    ORDER BY cm.created_at ASC
");
$msgStmt->execute([$currentHobby]);
$chatMessages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

$memStmt = $conn->prepare("
    SELECT u.id, u.username, 
           (SELECT 1 FROM user_follows WHERE follower_id = ? AND followed_id = u.id) as am_following
    FROM users u
    JOIN user_profiles p ON u.id = p.user_id
    WHERE p.hobbies LIKE ? AND u.id != ?
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
        .chat-message.mine { background-color: <?= $headerColor ?>; }
        
        .member-list {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .member-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .member-row:last-child { border-bottom: none; }
        .member-avatar {
            width: 30px; height: 30px;
            border-radius: 50%;
            display: inline-block;
            vertical-align: middle;
            margin-right: 10px;
        }
    </style>
</head>
<body>

    <div class="page-container">
        <div class="page-container-inside">
        
            <div style="background-color: <?= $headerColor ?>; padding: 30px; border-radius: 15px; text-align: center; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); position: relative;">
                <h1 style="color: white; margin: 0; font-size: 32px; font-weight: bold; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);"><?= htmlspecialchars($currentHobby) ?> Circle</h1>
                <p style="color: #eee; margin-top: 10px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">Connect, share, and learn about <?= htmlspecialchars(strtolower($currentHobby)) ?>!</p>
                
                <form action="circle_action.php" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="action" value="toggle_circle">
                    <input type="hidden" name="hobby" value="<?= htmlspecialchars($currentHobby) ?>">
                    <?php if ($isMember): ?>
                        <button type="submit" style="background-color: #333; color: white; border: none; padding: 8px 20px; border-radius: 20px; cursor: pointer; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">✓ Member (Leave)</button>
                    <?php else: ?>
                        <button type="submit" style="background-color: white; color: #333; border: 2px solid #333; padding: 8px 20px; border-radius: 20px; cursor: pointer; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">+ Join Circle</button>
                    <?php endif; ?>
                </form>
            </div>

            <h2>Circle Members</h2>
            <div class="member-list">
                <?php if (empty($members)): ?>
                    <p style="color: #666; margin: 0; text-align: center;">You are the only member so far! Invite some friends.</p>
                <?php else: ?>
                    <?php foreach ($members as $mem): ?>
                        <div class="member-row">
                            <div>
                                <div class="member-avatar" style="background-color: #<?= substr(md5($mem['username']), 0, 6) ?>;"></div>
                                <a href="profile.php?id=<?= $mem['id'] ?>" style="color: #333; text-decoration: none;"><strong><?= htmlspecialchars($mem['username']) ?></strong></a>
                            </div>
                            
                            <form action="circle_action.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="toggle_follow">
                                <input type="hidden" name="hobby" value="<?= htmlspecialchars($currentHobby) ?>">
                                <input type="hidden" name="target_id" value="<?= $mem['id'] ?>">
                                <?php if ($mem['am_following']): ?>
                                    <button type="submit" class="light-btn" style="background-color: #eee; color: #333; border: 1px solid #ccc; font-size: 12px;">Following</button>
                                <?php else: ?>
                                    <button type="submit" class="light-btn" style="background-color: #1f5077; color: white; border: none; font-size: 12px;">Follow</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <h2>Modules in this Circle</h2>
            <?php if (empty($circleModules)): ?>
                <div class="info-box" style="background-color: #1f5077;">
                    <p>No modules found for this circle yet. Be the first to create one!</p><br>
                    <a href="createForm.php" class="light-btn" style="text-decoration: none; color: #333;">Create Module</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($circleModules as $mod): ?>
                        <div class="info-card" style="background-color: #1f5077; color: white; border: none;">
                            <div class="card-row-between">
                                <h4 style="color: white; font-size: 20px; margin: 0;"><?= htmlspecialchars($mod['name']) ?></h4>
                                <span style="background-color: <?= $headerColor ?>; color: #333; padding: 3px 8px; border-radius: 10px; font-size: 12px; font-weight: bold;"><?= htmlspecialchars($mod['exp_level']) ?></span>
                            </div>
                            <p style="color: #ccc; font-size: 14px; margin-bottom: 15px;"><?= htmlspecialchars($mod['description']) ?></p>
                            <a href="module.php?id=<?= $mod['id'] ?>" class="light-btn" style="text-decoration: none; display: inline-block;">View Module</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="chat-container">
                <h2 style="margin-top: 0; margin-bottom: 15px; color: white;">Circle Discussion</h2>
                <div class="chat-box" id="chatBox">
                    <?php if (empty($chatMessages)): ?>
                        <p style="text-align: center; color: #999; margin-top: 20px;">No messages yet. Say hello!</p>
                    <?php else: ?>
                        <?php foreach ($chatMessages as $msg): 
                            $isMine = ($msg['username'] === $_SESSION['user']['name']) ? 'mine' : '';
                        ?>
                            <div class="chat-message <?= $isMine ?>">
                                <a href="profile.php?id=<?= $msg['user_id'] ?>" class="chat-author" style="text-decoration: none; color: inherit; display: block;"><?= htmlspecialchars($msg['username']) ?></a>
                                <div style="font-size: 14px;"><?= htmlspecialchars($msg['message']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <form method="POST" class="chat-input-row">
                    <input type="text" name="chat_message" class="chat-input" placeholder="Type a message..." required autocomplete="off">
                    <button type="submit" class="light-btn" style="background-color: <?= $headerColor ?>; border: none; font-weight: bold; padding: 10px 20px; color: #333;">Send</button>
                </form>
            </div>

        </div> </div> <script>
        const chatBox = document.getElementById('chatBox');
        chatBox.scrollTop = chatBox.scrollHeight;
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>