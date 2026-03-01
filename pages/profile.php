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

// 1. Fetch user data (Added login_streak for the Firestarter badge)
$stmt = $conn->prepare("
    SELECT u.username, u.age, p.hometown, p.bio, p.hobbies, p.profile_color, p.login_streak 
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

// --- 2. CALCULATE EARNED TROPHIES FOR PUBLIC DISPLAY ---
$earnedBadges = [];

// Modules Completed
$stmt = $conn->prepare("SELECT COUNT(*) FROM log WHERE uid = ? AND complete = 1");
$stmt->execute([$targetId]);
$modulesCompleted = (int)$stmt->fetchColumn();

// Following Count
$stmt = $conn->prepare("SELECT COUNT(*) FROM user_follows WHERE follower_id = ?");
$stmt->execute([$targetId]);
$followingCount = (int)$stmt->fetchColumn();

// Circles Created
$stmt = $conn->prepare("SELECT COUNT(*) FROM circle WHERE uid = ?");
$stmt->execute([$targetId]);
$circlesCreated = (int)$stmt->fetchColumn();

$streak = (int)($profileUser['login_streak'] ?? 0);

// Check which badges they have fully unlocked
if ($modulesCompleted >= 1) $earnedBadges[] = ['title' => 'First Steps', 'icon' => '🐣', 'color' => '#ff9999'];
if ($modulesCompleted >= 5) $earnedBadges[] = ['title' => 'Module Master', 'icon' => '🎓', 'color' => '#ffd700'];
if ($streak >= 7) $earnedBadges[] = ['title' => 'Firestarter', 'icon' => '🔥', 'color' => '#ffb6c1'];
if ($followingCount >= 5) $earnedBadges[] = ['title' => 'Social Butterfly', 'icon' => '🦋', 'color' => '#a8d0e6'];
if ($circlesCreated >= 1) $earnedBadges[] = ['title' => 'Community Leader', 'icon' => '👑', 'color' => '#9370db'];

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
            <<?php if ($myId != $targetId): ?>
                <button id="reportUserBtn" style="background:#ff4d4d; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; margin-top:10px;">
                    Report User
                </button>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: #333;">About Me</h3>
                <p><strong>Age:</strong> <?= htmlspecialchars($profileUser['age'] ?? 'Not specified') ?></p>
                <p><strong>Hometown:</strong> <?= htmlspecialchars($profileUser['hometown'] ?? 'Not specified') ?></p>
                <p><strong>Bio:</strong> <?= htmlspecialchars($profileUser['bio'] ?? 'No bio yet.') ?></p>
            </div>

            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; color: #333;">Trophy Case 🏆</h3>
                <?php if (empty($earnedBadges)): ?>
                    <p style="color: #999; font-style: italic;">No badges earned yet.</p>
                <?php else: ?>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 15px;">
                        <?php foreach ($earnedBadges as $badge): ?>
                            <div title="<?= htmlspecialchars($badge['title']) ?>" style="display: flex; flex-direction: column; align-items: center; width: 60px;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; background-color: <?= $badge['color'] ?>; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                    <?= $badge['icon'] ?>
                                </div>
                                <span style="font-size: 10px; text-align: center; margin-top: 5px; font-weight: bold; color: #555;">
                                    <?= htmlspecialchars($badge['title']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; color: #333;">Interests & Circles</h3>
            <?php if (empty($hobbiesArr)): ?>
                <p style="color: #999; font-style: italic;">No interests added yet.</p>
            <?php else: ?>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php foreach ($hobbiesArr as $hobby): 
                        $trimmedHobby = trim($hobby); 
                    ?>
                        <a href="circle_detail.php?hobby=<?= urlencode($trimmedHobby) ?>" style="text-decoration: none;">
                            <span style="background-color: #eee; padding: 8px 18px; border-radius: 20px; font-size: 14px; font-weight: bold; color: #555; display: inline-block; transition: background-color 0.2s; border: 1px solid #ddd;">
                                <?= htmlspecialchars($trimmedHobby) ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const reportBtn = document.getElementById('reportUserBtn');
        if (!reportBtn) return;

        reportBtn.addEventListener('click', () => {
            const reason = prompt("Please enter a reason for reporting this user:");
            if (!reason || reason.trim() === "") {
                alert("Report cancelled. You must enter a reason.");
                return;
            }

            const formData = new FormData();
            formData.append('type', 'user');           // exactly what PHP expects
            formData.append('target_id', '<?= $targetId ?>');
            formData.append('reason', reason.trim());

            fetch('submit_report.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
            })
            .catch(err => {
                console.error(err);
                alert("Error submitting report. Please try again.");
            });
        });
    });
    </script>
</body>
</html>