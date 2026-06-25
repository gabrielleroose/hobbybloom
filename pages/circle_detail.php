<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 
require_once 'db.php';
require_once 'base.php';
 
if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}
 
$userId      = $_SESSION['user']['id'];
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
$creatorId   = $circleData['uid'] ?? null;
$circleId    = $circleData['circle_id'] ?? null;
 
$stmt = $conn->prepare("SELECT hobbies FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$myHobbiesStr = $stmt->fetchColumn();
$myHobbiesArr = $myHobbiesStr ? explode(', ', $myHobbiesStr) : [];
$isMember     = in_array($currentHobby, $myHobbiesArr);
 
$cleanHobby = trim(preg_replace('/^[\p{So}\p{Sk}\x{200d}\x{fe0f}]+\s*/u', '', $currentHobby));
 
$circleModules = [];
if (!empty($cleanHobby)) {
    $stmt = $conn->prepare("SELECT id, name, description, exp_level FROM module WHERE name LIKE ? OR description LIKE ?");
    $stmt->execute(["%$cleanHobby%", "%$cleanHobby%"]);
    $circleModules = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
 
$msgStmt = $conn->prepare("
    SELECT DISTINCT cm.id, cm.message, cm.created_at, u.id AS user_id, u.username, p.profile_color
    FROM circle_messages cm
    JOIN users u ON cm.user_id = u.id
    LEFT JOIN user_profiles p ON u.id = p.user_id
    WHERE cm.hobby_name = ?
    GROUP BY cm.id
    ORDER BY cm.created_at ASC
");
$msgStmt->execute([$currentHobby]);
$chatMessages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
 
$memStmt = $conn->prepare("
    SELECT u.id, u.username, p.profile_color,
           (SELECT status FROM user_follows WHERE follower_id = ? AND followed_id = u.id LIMIT 1) as follow_status
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
    <title><?= htmlspecialchars($currentHobby) ?> Circle | HobbyBloom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/nav.css" rel="stylesheet">
</head>
<body class="circle-detail-body">
 
<div class="cd-page-wrap">
<div class="cd-wrap">
 
    <!-- ── Hero banner ── -->
    <div class="cd-hero" style="background: linear-gradient(135deg, <?= htmlspecialchars($headerColor) ?>cc, <?= htmlspecialchars($headerColor) ?>);">
 
        <div class="cd-hero-icon">
            <?= extractEmoji($currentHobby) ?>
        </div>
 
        <h1 class="cd-hero-title"><?= htmlspecialchars($currentHobby) ?></h1>
 
        <?php if (!empty($circleData['category'])): ?>
            <span class="cd-hero-badge"><?= htmlspecialchars($circleData['category']) ?></span>
        <?php endif; ?>
 
        <p class="cd-hero-desc">
            <?= htmlspecialchars($circleData['description'] ?? 'Connect, share, and grow together!') ?>
        </p>
 
        <div class="cd-hero-actions">
            <form action="circle_action.php" method="POST">
                <input type="hidden" name="action" value="toggle_circle">
                <input type="hidden" name="hobby" value="<?= htmlspecialchars($currentHobby) ?>">
                <button type="submit" class="cd-join-btn"
                        style="color: <?= htmlspecialchars($headerColor) ?>;">
                    <?= $isMember ? '✓ Member (Leave)' : '+ Join Circle' ?>
                </button>
            </form>
 
            <?php if ($creatorId == $userId): ?>
                <a href="edit_circle.php?id=<?= $circleId ?>" class="cd-action-btn">
                    Edit Circle
                </a>
            <?php else: ?>
                <button id="reportCircleBtn" class="cd-action-btn">Report</button>
            <?php endif; ?>
        </div>
    </div>
 
    <!-- ── Chat ── -->
    <div class="cd-card">
        <div class="cd-card-label">Circle Discussion</div>
 
        <div class="cd-chat-box" id="chatBox">
            <?php if (empty($chatMessages)): ?>
                <p class="cd-chat-empty">Be the first to send a message!</p>
            <?php endif; ?>
 
            <?php foreach ($chatMessages as $msg):
                $isMine  = ($msg['user_id'] == $userId);
                $avatarC = !empty($msg['profile_color'])
                    ? $msg['profile_color']
                    : '#' . substr(md5($msg['username']), 0, 6);
            ?>
                <div class="cd-msg <?= $isMine ? 'mine' : '' ?>">
                    <a href="profile.php?id=<?= $msg['user_id'] ?>"
                       class="cd-msg-av"
                       style="background-color: <?= $avatarC ?>;">
                        <?= strtoupper(substr($msg['username'], 0, 1)) ?>
                    </a>
                    <div class="cd-msg-bubble" style="<?= $isMine ? 'background:' . htmlspecialchars($headerColor) . ';color:#fff;' : '' ?>">
                        <div class="cd-msg-user" style="<?= $isMine ? 'color:rgba(255,255,255,0.75);' : '' ?>">
                            @<?= htmlspecialchars($msg['username']) ?>
                        </div>
                        <?= htmlspecialchars($msg['message']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
 
        <?php if ($isMember): ?>
            <form method="POST" class="cd-chat-input-row">
                <input type="text" name="chat_message" class="cd-chat-input"
                       placeholder="Type a message..." required autocomplete="off">
                <button type="submit" class="cd-send-btn"
                        style="background: <?= htmlspecialchars($headerColor) ?>;">
                    Send
                </button>
            </form>
        <?php else: ?>
            <div class="cd-join-prompt">
                Join this circle to participate in the discussion!
            </div>
        <?php endif; ?>
    </div>
 
    <!-- ── Members + Modules ── -->
    <div class="cd-grid">
 
        <!-- Members -->
        <div class="cd-card">
            <div class="cd-card-label">Circle Members</div>
 
            <?php if (empty($members)): ?>
                <p class="cd-empty-msg">No other members yet.</p>
            <?php else: ?>
                <?php foreach ($members as $mem):
                    $mColor = !empty($mem['profile_color'])
                        ? $mem['profile_color']
                        : '#' . substr(md5($mem['username']), 0, 6);
                    $status = $mem['follow_status'];
                ?>
                    <div class="cd-member-row">
                        <div class="cd-member-left">
                            <div class="cd-member-av" style="background-color: <?= $mColor ?>;"></div>
                            <a href="profile.php?id=<?= $mem['id'] ?>"
                               class="cd-member-name">
                                @<?= htmlspecialchars($mem['username']) ?>
                            </a>
                        </div>
                        <form action="circle_action.php" method="POST">
                            <input type="hidden" name="action" value="toggle_follow">
                            <input type="hidden" name="target_id" value="<?= $mem['id'] ?>">
                            <input type="hidden" name="hobby" value="<?= htmlspecialchars($currentHobby) ?>">
                            <button type="submit"
                                    class="<?= $status === 'accepted' ? 'cd-following-btn' : 'cd-follow-btn' ?>">
                                <?php
                                    if ($status === 'accepted')     echo 'Following ✓';
                                    elseif ($status === 'pending')  echo 'Requested…';
                                    else                            echo '+ Follow';
                                ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
 
        <!-- Related modules -->
        <div class="cd-card">
            <div class="cd-card-label">Related Modules</div>
 
            <?php if (empty($circleModules)): ?>
                <div class="cd-empty-modules">
                    <p class="cd-empty-msg">No modules found for this circle yet.</p>
                    <a href="createForm.php" class="cd-create-mod-btn">+ Create Module</a>
                </div>
            <?php else: ?>
                <?php foreach ($circleModules as $mod): ?>
                    <div class="cd-mod-card"
                         style="border-left: 3px solid <?= htmlspecialchars($headerColor) ?>;">
                        <div class="cd-mod-title"><?= htmlspecialchars($mod['name']) ?></div>
                        <div class="cd-mod-desc"><?= htmlspecialchars($mod['description']) ?></div>
                        <div class="cd-mod-footer">
                            <span class="cd-mod-level"><?= htmlspecialchars($mod['exp_level']) ?></span>
                            <a href="module.php?id=<?= $mod['id'] ?>" class="cd-mod-link">
                                View Module →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
 
    </div>
 
</div>
</div>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chatBox = document.getElementById('chatBox');
    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
 
    const reportBtn = document.getElementById('reportCircleBtn');
    if (reportBtn) {
        reportBtn.addEventListener('click', function () {
            const reason = prompt("Why are you reporting this circle?");
            if (!reason) return;
 
            fetch('submit_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'circle',
                    item_id: <?= json_encode($circleId) ?>,
                    reason: reason
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') alert("Report submitted to moderation.");
                else alert("Error submitting report: " + (data.message || 'Unknown error'));
            });
        });
    }
});
</script>
 
</body>
</html>