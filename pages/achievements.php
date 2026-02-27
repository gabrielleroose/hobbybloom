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

$stmt = $conn->prepare("SELECT login_streak FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$streak = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM user_follows WHERE follower_id = ?");
$stmt->execute([$userId]);
$followingCount = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM circle WHERE uid = ?");
$stmt->execute([$userId]);
$circlesCreated = (int)$stmt->fetchColumn();


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
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Achievements</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
    <style>
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .badge-card {
            background-color: white;
            border-radius: 15px;
            padding: 25px 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .badge-card:hover {
            transform: scale(1.05);
        }

        .badge-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 15px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .badge-title {
            color: #153853;
            font-weight: bold;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .badge-desc {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            height: 40px;
        }

        .badge-progress-bar {
            width: 100%;
            height: 10px;
            background-color: #eee;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .badge-progress-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.5s ease;
        }

        .badge-progress-text {
            font-size: 12px;
            color: #999;
            font-weight: bold;
        }

        .locked {
            filter: grayscale(100%);
            opacity: 0.6;
        }
        .locked .badge-icon-wrapper {
            background-color: #e0e0e0 !important;
        }
        
        .unlocked-banner {
            position: absolute;
            top: 15px;
            right: -35px;
            background-color: #2c6ca3;
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 5px 40px;
            transform: rotate(45deg);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body style="background-color: #BDC29D;">

    <div class="account-main-container" style="margin-top: 50px;">
        
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
                </div>

            <?php endforeach; ?>
        </div>

    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>