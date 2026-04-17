<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
// Include base.php at the very top so it handles the <head> and navbar
require_once 'base.php';

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user']['id'];
$currentHobby = $_GET['hobby'] ?? 'General';

// Chat message submission (This perfectly tracks for your Chatterbox badge!)
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

<style>
    /* Force background and flex layout for footer positioning */
    body { 
        background-color: #a3b18a !important; 
        margin: 0; 
        display: flex; 
        flex-direction: column; 
        min-height: 100vh; 
    }
    .circle-detail-wrapper { 
        flex: 1; 
        padding: 40px 20px; 
    }
    footer { margin-top: auto; }

    /* Page specific styles */
    .chat-container {
        height: 450px !important;
        display: flex;
        flex-direction: column;
        padding: 20px;
        margin-top: 30px;
        background: rgba(255,255,255,0.4);
        border-radius: 15px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.3);
        box-shadow: 0 8px 32px 0 rgba(0,0,0,0.1);
    }
    .chat-box { flex: 1; overflow-y: auto; margin-bottom: 15px; padding-right: 10px; }
    .chat-input-row { display: flex; gap: 10px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.3); }
    .chat-input { flex: 1; padding: 12px 20px; border-radius: 30px; background: white; border: 1px solid #ccc; color: #333; font-size: 1rem; }
    .chat-message-container { display: flex; align-items: flex-start; margin-bottom: 15px; }
    .chat-message-container.mine { flex-direction: row-reverse; }
    .chat-avatar { width: 35px; height: 35px; border-radius: 50%; margin: 0 10px; flex-shrink: 0; border: 1px solid rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; font-weight: bold; text-decoration: none; }
    
    /* Using the circle's dynamic color for the user's chat bubbles */
    .chat-message-container.mine .chat-content { background: <?= htmlspecialchars($headerColor) ?>; color: white; }
    .chat-message-container:not(.mine) .chat-content { background: white; color: #333; }
    
    .chat-content { padding: 10px 15px; border-radius: 15px; max-width: 75%; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    
    .member-list { background-color: rgba(255,255,255,0.8); border-radius: 15px; padding: 20px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .member-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
    .member-row:last-child { border-bottom: none; }
    .member-avatar { width: 40px; height: 40px; border-radius: 50%; margin-right: 15px; border: 1px solid rgba(0,0,0,0.1); }
    
    .category-badge { background-color: rgba(255, 255, 255, 0.25); padding: 5px 15px; border-radius: 20px; color: white; font-size: 0.85rem; font-weight: bold; border: 1px solid rgba(255, 255, 255, 0.4); text-transform: uppercase; letter-spacing: 1px; }

    /* Override circle icon scale for the hero header */
    .hero-icon { width: 80px; height: 80px; font-size: 40px; margin: 0 auto 15px auto; background-color: white; border: 4px solid rgba(255,255,255,0.3); }
</style>

<div class="circle-detail-wrapper">
    <div class="circle-detail-main-container" style="max-width: 900px; margin: 0 auto;">
    
        <div style="background-color: <?= htmlspecialchars($headerColor) ?>; padding: 40px 30px; border-radius: 20px; text-align: center; margin-bottom: 30px; box-shadow: 0 6px 15px rgba(0,0,0,0.15);">
            
            <div class="circle-icon hero-icon" style="color: #333;">
                <?= extractEmoji($currentHobby) ?>
            </div>

            <div style="display: flex; align-items: center; justify-content: center; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
                <h1 style="color: white; margin: 0; font-size: 38px; text-shadow: 1px 1px 3px rgba(0,0,0,0.3);"><?= htmlspecialchars($currentHobby) ?></h1>
                <span class="category-badge">
                    <?= htmlspecialchars($circleData['category'] ?? 'General') ?>
                </span>
            </div>
            
            <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem; max-width: 600px; margin: 0 auto 20px auto; line-height: 1.5;">
                <?= htmlspecialchars($circleData['description'] ?? 'Connect, share, and grow together!') ?>
            </p>
            
            <div style="display: flex; justify-content: center; gap: 15px; align-items: center;">
                <form action="circle_action.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="toggle_circle">
                    <input type="hidden" name="hobby" value="<?= htmlspecialchars($currentHobby) ?>">
                    <button type="submit" style="background-color: white; color: <?= htmlspecialchars($headerColor) ?>; border: none; border-radius: 30px; padding: 10px 25px; font-weight: bold; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s;">
                        <?= $isMember ? '✓ Member (Leave)' : '+ Join Circle' ?>
                    </button>
                </form>

                <?php if ($creatorId == $userId): ?>
                    <a href="edit_circle.php?id=<?= $circleId ?>" style="background: rgba(0,0,0,0.2); color: white; border: 1px solid rgba(255,255,255,0.5); padding: 10px 20px; border-radius: 30px; font-weight: bold; text-decoration: none; font-size: 0.9rem; transition: background 0.3s;">
                        Edit Circle
                    </a>
                <?php else: ?>
                    <button id="reportCircleBtn" style="background: rgba(255,0,0,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 10px 20px; border-radius: 30px; font-weight: bold; cursor: pointer; font-size: 0.9rem; transition: background 0.3s;">
                        Report
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 30px;">
            
            <div class="chat-container">
                <h2 style="margin: 0 0 15px 0; color: #1E5077; font-size: 24px; display: flex; align-items: center; gap: 10px;">
                    💬 Circle Discussion
                </h2>
                <div class="chat-box" id="chatBox">
                    <?php if (empty($chatMessages)): ?>
                        <p style="text-align: center; color: #666; font-style: italic; margin-top: 50px;">Be the first to send a message!</p>
                    <?php endif; ?>

                    <?php foreach ($chatMessages as $msg): 
                        $isMine = ($msg['user_id'] == $userId);
                        $c = !empty($msg['profile_color']) ? $msg['profile_color'] : '#' . substr(md5($msg['username']), 0, 6);
                    ?>
                        <div class="chat-message-container <?= $isMine ? 'mine' : '' ?>">
                            <a href="profile.php?id=<?= $msg['user_id'] ?>" class="chat-avatar" style="background-color: <?= $c ?>;">
                                <?= strtoupper(substr($msg['username'], 0, 1)) ?>
                            </a>
                            <div class="chat-content">
                                <a href="profile.php?id=<?= $msg['user_id'] ?>" style="text-decoration: none;">
                                    <small style="display: block; font-weight: bold; font-size: 11px; margin-bottom: 4px; color: <?= $isMine ? 'rgba(255,255,255,0.8)' : '#888' ?>;">
                                        @<?= htmlspecialchars($msg['username']) ?>
                                    </small>
                                </a>
                                <div style="font-size: 15px; line-height: 1.4;"><?= htmlspecialchars($msg['message']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($isMember): ?>
                    <form method="POST" class="chat-input-row">
                        <input type="text" name="chat_message" class="chat-input" placeholder="Type a message to earn the Chatterbox badge..." required autocomplete="off">
                        <button type="submit" style="background-color: #1f5077; color: white; border: none; font-weight: bold; border-radius: 30px; padding: 0 25px; cursor: pointer; transition: background 0.3s;">Send</button>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; padding: 15px; background: rgba(255,255,255,0.5); border-radius: 10px; color: #666; font-style: italic;">
                        Join this circle to participate in the discussion!
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <h2 style="color: #1E5077; font-size: 20px; margin-bottom: 15px;">👥 Circle Members</h2>
                <div class="member-list">
                    <?php if (empty($members)): ?>
                        <p style="color: #666; text-align: center; margin: 0; font-style: italic;">No other members yet.</p>
                    <?php else: ?>
                        <?php foreach ($members as $mem): 
                            $mColor = !empty($mem['profile_color']) ? $mem['profile_color'] : '#' . substr(md5($mem['username']), 0, 6);
                            $status = $mem['follow_status'];
                        ?>
                            <div class="member-row">
                                <div style="display: flex; align-items: center;">
                                    <div class="member-avatar" style="background-color: <?= $mColor ?>;"></div>
                                    <a href="profile.php?id=<?= $mem['id'] ?>" style="color: #333; text-decoration: none; font-size: 15px;"><strong>@<?= htmlspecialchars($mem['username']) ?></strong></a>
                                </div>
                                <form action="circle_action.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="toggle_follow">
                                    <input type="hidden" name="target_id" value="<?= $mem['id'] ?>">
                                    <input type="hidden" name="hobby" value="<?= htmlspecialchars($currentHobby) ?>">
                                    <button type="submit" style="background: <?= $status === 'accepted' ? '#e0e0e0' : '#1f5077' ?>; color: <?= $status === 'accepted' ? '#333' : 'white' ?>; border: none; padding: 6px 15px; border-radius: 20px; font-size: 11px; font-weight: bold; cursor: pointer;">
                                        <?php 
                                            if ($status === 'accepted') echo 'Following ✓';
                                            elseif ($status === 'pending') echo 'Requested...';
                                            else echo '+ Follow';
                                        ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h2 style="color: #1E5077; font-size: 20px; margin-bottom: 15px;">📚 Related Modules</h2> 
                <?php if (empty($circleModules)): ?>
                    <div style="background-color: rgba(255,255,255,0.6); padding: 25px; border-radius: 15px; text-align: center; border: 1px dashed #ccc;">
                        <p style="color: #666; margin-bottom: 15px; font-style: italic;">No modules found for this circle yet.</p>
                        <a href="createForm.php" style="text-decoration: none; background: #1f5077; color: white; padding: 8px 20px; border-radius: 20px; font-size: 13px; font-weight: bold;">+ Create Module</a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px;">
                        <?php foreach ($circleModules as $mod): ?>
                            <div style="background-color: white; border-left: 5px solid <?= htmlspecialchars($headerColor) ?>; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <h4 style="color: #1f5077; margin: 0; font-size: 16px;"><?= htmlspecialchars($mod['name']) ?></h4>
                                    <span style="background-color: #f0f0f0; color: #666; padding: 3px 10px; border-radius: 15px; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                                        <?= htmlspecialchars($mod['exp_level']) ?>
                                    </span>
                                </div>
                                <p style="color: #666; font-size: 13px; margin: 0 0 15px 0; line-height: 1.4;"><?= htmlspecialchars($mod['description']) ?></p>
                                <a href="module.php?id=<?= $mod['id'] ?>" style="text-decoration: none; background: #f8f9fa; border: 1px solid #ddd; color: #333; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block;">View Module →</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div> 

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatBox = document.getElementById('chatBox');
            if(chatBox) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }

            const reportBtn = document.getElementById('reportCircleBtn');
            if(reportBtn) {
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
            }
        });
    </script>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>