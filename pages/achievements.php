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

$stmt = $conn->prepare("SELECT COUNT(*) FROM log WHERE uid = ? AND complete = 1");
$stmt->execute([$userId]);
$modulesCompleted = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("SELECT login_streak, hobbies FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profileData = $stmt->fetch(PDO::FETCH_ASSOC);
$streak = (int)($profileData['login_streak'] ?? 0);

$stmt = $conn->prepare("SELECT COUNT(*) FROM user_follows WHERE follower_id = ?");
$stmt->execute([$userId]);
$followingCount = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM circle WHERE uid = ?");
$stmt->execute([$userId]);
$circlesCreated = (int)$stmt->fetchColumn();

$myHobbiesStr = $profileData['hobbies'] ?? '';
$myCircles = $myHobbiesStr ? array_map('trim', explode(',', $myHobbiesStr)) : [];

$achievements = [
    [
        'title' => 'First Steps',
        'desc' => 'Complete your first module.',
        'icon' => '🐣',
        'color' => '#ff9999',
        'unlocked' => $modulesCompleted >= 1,
        'progress' => min($modulesCompleted, 1) . ' / 1'
    ],
    [
        'title' => 'Circle Architect',
        'desc' => 'Build the community by creating 3 circles.',
        'icon' => '🏗️',
        'color' => '#98fb98',
        'unlocked' => $circlesCreated >= 3,
        'progress' => min($circlesCreated, 3) . ' / 3'
    ],
    [
        'title' => 'Module Master',
        'desc' => 'Complete 5 different modules.',
        'icon' => '🎓',
        'color' => '#ffd700',
        'unlocked' => $modulesCompleted >= 5,
        'progress' => min($modulesCompleted, 5) . ' / 5'
    ],
    [
        'title' => 'Firestarter',
        'desc' => 'Reach a 7-day login streak.',
        'icon' => '🔥',
        'color' => '#ffb6c1',
        'unlocked' => $streak >= 7,
        'progress' => min($streak, 7) . ' / 7'
    ],
    [
        'title' => 'Social Butterfly',
        'desc' => 'Follow 5 other users.',
        'icon' => '🦋',
        'color' => '#a8d0e6',
        'unlocked' => $followingCount >= 5,
        'progress' => min($followingCount, 5) . ' / 5'
    ],
    [
        'title' => 'Community Leader',
        'desc' => 'Create your own circle.',
        'icon' => '👑',
        'color' => '#9370db',
        'unlocked' => $circlesCreated >= 1,
        'progress' => min($circlesCreated, 1) . ' / 1'
    ],
    [
        'title' => 'Bloom Legend',
        'desc' => 'Unlock all primary badges.',
        'icon' => '🌟',
        'color' => '#1f5077',
        'unlocked' => ($modulesCompleted >= 5 && $streak >= 7 && $followingCount >= 5 && $circlesCreated >= 3),
        'progress' => (($modulesCompleted >= 5 ? 1 : 0) + ($streak >= 7 ? 1 : 0) + ($followingCount >= 5 ? 1 : 0) + ($circlesCreated >= 3 ? 1 : 0) + ($modulesCompleted >= 1 ? 1 : 0)) . ' / 5'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_badge'])) {
    $badgeName = $_POST['badge_name'];
    $badgeIcon = $_POST['badge_icon'];
    $targetCircle = $_POST['target_circle'];
    $shareMsg = "I just earned the " . $badgeIcon . " " . $badgeName . " badge on HobbyBloom!";

    $ins = $conn->prepare("INSERT INTO circle_messages (hobby_name, user_id, message) VALUES (?, ?, ?)");
    $ins->execute([$targetCircle, $userId, $shareMsg]);
    header("Location: circle_detail.php?hobby=" . urlencode($targetCircle) . "&success=shared");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Achievements | HobbyBloom</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
    <style>
        .achievements-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 30px; }
        .badge-card { background-color: white; border-radius: 15px; padding: 25px 20px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.3s ease; position: relative; overflow: hidden; display: flex; flex-direction: column; }
        .badge-card:hover { transform: translateY(-5px); }
        .badge-icon-wrapper { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; font-size: 40px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .badge-title { color: #153853; font-weight: bold; font-size: 20px; margin-bottom: 5px; }
        .badge-desc { color: #666; font-size: 14px; margin-bottom: 15px; height: 40px; }
        
        .badge-progress-bar { width: 100%; height: 12px; background-color: rgba(0, 0, 0, 0.08); border-radius: 6px; overflow: hidden; margin-bottom: 5px; border: 1px solid rgba(0,0,0,0.05); }
        
        .locked .badge-progress-fill { background-color: #666 !important; }
        
        .badge-progress-fill { height: 100%; border-radius: 6px; transition: width 0.5s ease; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1); }
        .badge-progress-text { font-size: 12px; color: #777; font-weight: bold; }
        .locked { filter: grayscale(100%); opacity: 0.8; }
        .unlocked-banner { position: absolute; top: 15px; right: -35px; background-color: #2c6ca3; color: white; font-size: 10px; font-weight: bold; padding: 5px 40px; transform: rotate(45deg); box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        
        .share-section { margin-top: auto; padding-top: 15px; border-top: 1px solid #eee; }
        .share-select { padding: 5px; border-radius: 5px; font-size: 12px; width: 100%; margin-bottom: 8px; border: 1px solid #ddd; }
        .share-btn { background: #1f5077; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: bold; width: 100%; }
        .share-btn:hover { background: #2c6ca3; }
    </style>
</head>
<body style="background-color: #BDC29D;">

    <div class="account-main-container" style="margin-top: 50px; padding-bottom: 50px;">
        
        <div style="background-color: #1f5077; padding: 30px; border-radius: 15px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h1 style="color: white; margin: 0; font-size: 32px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);">Trophy Room 🏆</h1>
            <p style="color: #eee; margin-top: 10px;">Track your progress and unlock badges as you grow!</p>
        </div>

        <div class="achievements-grid">
            <?php foreach ($achievements as $badge): ?>
                <?php 
                    list($current, $max) = explode(' / ', $badge['progress']);
                    $percent = ($max > 0) ? ($current / $max) * 100 : 0;
                    $statusClass = $badge['unlocked'] ? 'unlocked' : 'locked';
                    $iconBg = $badge['unlocked'] ? $badge['color'] : '#e0e0e0';
                ?>

                <div class="badge-card <?= $statusClass ?>">
                    <?php if ($badge['unlocked']): ?>
                        <div class="unlocked-banner">EARNED!</div>
                    <?php endif; ?>

                    <div class="badge-icon-wrapper" style="background-color: <?= $iconBg ?>;">
                        <?= $badge['icon'] ?>
                    </div>
                    
                    <div class="badge-title"><?= htmlspecialchars($badge['title']) ?></div>
                    <div class="badge-desc"><?= htmlspecialchars($badge['desc']) ?></div>
                    
                    <div class="badge-progress-bar">
                        <div class="badge-progress-fill" style="width: <?= $percent ?>%; background-color: <?= $badge['color'] ?>;"></div>
                    </div>
                    <div class="badge-progress-text"><?= $badge['progress'] ?> Progress</div>

                    <?php if ($badge['unlocked']): ?>
                        <div class="share-section">
                            <form method="POST">
                                <input type="hidden" name="share_badge" value="1">
                                <input type="hidden" name="badge_name" value="<?= $badge['title'] ?>">
                                <input type="hidden" name="badge_icon" value="<?= $badge['icon'] ?>">
                                
                                <?php if (!empty($myCircles)): ?>
                                    <select name="target_circle" class="share-select" required>
                                        <option value="" disabled selected>Select a Circle to boast...</option>
                                        <?php foreach ($myCircles as $cName): ?>
                                            <option value="<?= htmlspecialchars($cName) ?>"><?= htmlspecialchars($cName) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="share-btn">Share to Discussion</button>
                                <?php else: ?>
                                    <p style="font-size: 10px; color: #999;">Join a Circle to share this achievement!</p>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endforeach; ?>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>