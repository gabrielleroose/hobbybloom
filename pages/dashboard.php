<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// require_once $_SERVER['DOCUMENT_ROOT'] . '/pages/base.php';
require_once __DIR__ . '/base.php';

$streak = 1;

if (isset($_SESSION['user']['id'])) {
    $stmt = $conn->prepare("
        SELECT last_login, login_streak 
        FROM user_profiles 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user']['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $lastLogin = $userData['last_login'];
        $currentStreak = (int)$userData['login_streak'];

        $today = new DateTime();
        if ($lastLogin) {
            $lastLoginDate = new DateTime($lastLogin);
            $diff = $lastLoginDate->diff($today)->days;
        } else {
            $diff = 999; // first login → reset streak
        }

        if ($diff === 1) {
            // logged in yesterday → streak continues
            $streak = $currentStreak + 1;
        } elseif ($diff === 0) {
            // already logged in today
            $streak = $currentStreak;
        } else {
            // missed a day → reset
            $streak = 1;
        }

        // update DB
        $update = $conn->prepare("
            UPDATE user_profiles
            SET last_login = CURDATE(), login_streak = ?
            WHERE user_id = ?
        ");
        $update->execute([$streak, $_SESSION['user']['id']]);
    }
}

$myHobbies = [];
if (isset($_SESSION['user']['id'])) {
    $stmt = $conn->prepare("SELECT hobbies FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $res = $stmt->fetch();
    
    if ($res && $res['hobbies']) {
        // Explode turns the string "Cooking, Lego" into an array ["Cooking", "Lego"]
        $myHobbies = explode(', ', $res['hobbies']);
    }
}

$currentModule = null;

if (isset($_SESSION['user']['id'])) {
    $stmt = $conn->prepare("
        SELECT m.name
        FROM log l
        JOIN module m ON l.mid = m.id
        WHERE l.uid = ?
          AND l.complete = 0
        ORDER BY l.last_visited DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user']['id']]);
    $currentModule = $stmt->fetchColumn();
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">

</head>

<body>

<main class="main-dashboard">

        <div class="my-dashboard">
        <p> Hello, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'there') ?> </p>
        </div>


        <div class="dash-outter">
            <div class="dash-inner"><p>My Dashboard</p></div>
            <div class="dash-inner2"><p>🔥 Streak - <?= $streak ?> Day<?= $streak === 1 ? '' : 's' ?></p></div>
        </div>
        
        <div class="dash-heading"><p>Jump Back In!</p></div>
        <div class="dash-item">
            <?php if ($currentModule): ?>
            <p>📘 Continue: <strong><?= htmlspecialchars($currentModule) ?></strong></p>
            <a href="module.php" class="resume-btn">Resume Module</a>
            <?php else: ?>
                <p class="dash-item-text">✨You're all caught up! Start a new module.</p>
                <a href="module.php" class="resume-btn">Browse Modules</a>
            <?php endif; ?>
        </div>

        <div class="dashboard-circles">

            <h2>Your Circles</h2>
            <div class="horizontal-scroll">
                
                <?php if (in_array("Cooking", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #ff9999;"></div>
                    <p>Cooking</p>
                </div>
                <?php endif; ?>

                <?php if (in_array("Knitting", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #e6e6fa;"></div>
                    <p>Knitting</p>
                </div>
                <?php endif; ?>

                <?php if (in_array("Lego", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #ffd700;"></div>
                    <p>Lego</p>
                </div>
                <?php endif; ?>

                <?php if (in_array("Sewing", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #ffb6c1;"></div>
                    <p>Sewing</p>
                </div>
                <?php endif; ?>

                <?php if (in_array("Painting", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #ffdab9;"></div>
                    <p>Painting</p>
                </div>
                <?php endif; ?>

                <?php if (in_array("Hiking", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #90ee90;"></div>
                    <p>Hiking</p>
                </div>
                <?php endif; ?>
                
                <?php if (in_array("Reading", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #a8d0e6;"></div>
                    <p>Reading</p>
                </div>
                <?php endif; ?>

                <?php if (in_array("Gardening", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #3cb371;"></div>
                    <p>Gardening</p>
                </div>
                <?php endif; ?>

                <?php if (in_array("Baking", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #f4a460;"></div>
                    <p>Baking</p>
                </div>
                <?php endif; ?>

                <?php if (in_array("Meditation", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #e0ffff;"></div>
                    <p>Meditation</p>
                </div>
                <?php endif; ?>

                <?php if (in_array("Music", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #dda0dd;"></div>
                    <p>Music</p>
                </div>
                <?php endif; ?>

                <?php if (in_array("Movies", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #cd5c5c;"></div>
                    <p>Movies</p>
                </div>
                <?php endif; ?>

                <?php if (in_array("Gaming", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #9370db;"></div>
                    <p>Gaming</p>
                </div>
                <?php endif; ?>

                <?php if (in_array("Yoga", $myHobbies)): ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #ffdead;"></div>
                    <p>Yoga</p>
                </div>
                <?php endif; ?>

                <div class="story-circle">
                    <div class="circle-img" style="background-color: #cccccc;"></div>
                    <p>General</p>
                </div>
                
            </div>
        </div>

    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>