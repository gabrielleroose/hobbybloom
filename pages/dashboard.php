<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 
require_once __DIR__ . '/db.php';
echo '<script>document.body.classList.add("body-dashboard");</script>';
 
if (!isset($conn)) {
    die("Database connection variable (\$conn) is missing. Check your db.php file.");
}
 
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
 
$userId = $_SESSION['user']['id'];
 
$stmt = $conn->prepare("SELECT first_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$onboardingCheck = $stmt->fetchColumn();
 
if (empty($onboardingCheck)) {
    header("Location: index.php?onboarding=1");
    exit();
}
 

$extraHead = '
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<link href="../css/dashboard.css" rel="stylesheet">
';
 

require_once __DIR__ . '/base.php';
 
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$chosenUsername = $stmt->fetchColumn();
 
$streak = 1;
 
$stmt = $conn->prepare("SELECT last_login, login_streak FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);
 
if ($userData) {
    $lastLogin = $userData['last_login'];
    $currentStreak = (int)$userData['login_streak'];
    $today = new DateTime();
    $diff = $lastLogin ? (new DateTime($lastLogin))->diff($today)->days : 999;
 
    if ($diff === 1) {
        $streak = $currentStreak + 1;
    } elseif ($diff === 0) {
        $streak = $currentStreak;
    } else {
        $streak = 1;
    }
 
    $update = $conn->prepare("UPDATE user_profiles SET last_login = CURDATE(), login_streak = ? WHERE user_id = ?");
    $update->execute([$streak, $userId]);
}
 
$myHobbies = [];
$stmt = $conn->prepare("SELECT hobbies FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$res = $stmt->fetch();
if ($res && $res['hobbies']) {
    $myHobbies = array_map('trim', explode(',', $res['hobbies']));
}
 
$dbCircleColors = [];
$colorStmt = $conn->query("SELECT name, color FROM circle");
while ($row = $colorStmt->fetch(PDO::FETCH_ASSOC)) {
    $dbCircleColors[trim($row['name'])] = $row['color'];
}
 
$hobbyColors = [
    "Cooking"    => "#ff9999",
    "Knitting"   => "#e6e6fa",
    "Lego"       => "#ffd700",
    "Sewing"     => "#ffb6c1",
    "Painting"   => "#ffdab9",
    "Hiking"     => "#90ee90",
    "Reading"    => "#a8d0e6",
    "Gardening"  => "#3cb371",
    "Baking"     => "#f4a460",
    "Meditation" => "#e0ffff",
    "Music"      => "#dda0dd",
    "Movies"     => "#cd5c5c",
    "Gaming"     => "#9370db",
    "Yoga"       => "#ffdead"
];
 
$recommendations = [];
if (!empty($myHobbies)) {
    $stmt = $conn->prepare("SELECT mid FROM log WHERE uid = ? AND complete = 1");
    $stmt->execute([$userId]);
    $completedIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
 
    $stmt = $conn->prepare("SELECT id, name, description, exp_level FROM module");
    $stmt->execute();
    $allModules = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    foreach ($allModules as $mod) {
        if (in_array($mod['id'], $completedIds)) continue;
        foreach ($myHobbies as $hobbyWithEmoji) {
            
            $cleanHobby = trim(preg_replace('/^[\p{So}\p{Sk}\x{200d}\x{fe0f}]+\s*/u', '', $hobbyWithEmoji));
            
            if (!empty($cleanHobby) && (stripos($mod['name'], $cleanHobby) !== false || stripos($mod['description'], $cleanHobby) !== false)) {
                $recommendations[] = $mod;
                break;
            }
        }
    }
}
 
$currentModule = null;
$stmt = $conn->prepare("
    SELECT m.name, m.id
    FROM log l
    JOIN module m ON l.mid = m.id
    WHERE l.uid = ? AND l.complete = 0
    ORDER BY l.last_visited DESC LIMIT 1
");
$stmt->execute([$userId]);
$currentModule = $stmt->fetch(PDO::FETCH_ASSOC);
 
$modCount = $conn->prepare("SELECT COUNT(*) FROM module WHERE cid = ?");
$modCount->execute([$userId]);
$modules = (int)$modCount->fetchColumn();
 
$evtCount = $conn->prepare("SELECT COUNT(*) FROM events WHERE created_by = ?");
$evtCount->execute([$userId]);
$events = (int)$evtCount->fetchColumn();
 
$cirCount = $conn->prepare("SELECT COUNT(*) FROM circle WHERE uid = ?");
$cirCount->execute([$userId]);
$circles = (int)$cirCount->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>

<body class="dashboard-body">
    
 
<main class="main-dashboard">
 
    <div class="my-dashboard">
        <span class="dash-greeting-label">Welcome back,</span>
        <span class="dash-greeting-name"><?= htmlspecialchars($chosenUsername ?? 'there') ?></span>
    </div>
 
    <div class="dash-row-top">

 
        <div class="dash-display-streak">
            <div class="dash-card-label">Daily Streak</div>
            <div class="dash-streak-num">🔥 <?= $streak ?></div>
            <p class="dash-streak-sub">day<?= $streak !== 1 ? 's' : '' ?> in a row</p>
            <a href="achievements.php" class="dash-trophy-btn">🏆 View Trophies</a>
        </div>

         <div class="dash-calendar">
            <div class="dash-heading schedule">Upcoming Schedule</div>
            <div id="calendar-mini"></div>
            <div class="dash-calendar-button">
                <a class="dash-calendar-view" href="calendar.php">View full calendar →</a>
            </div>
        </div>
 
       
 
    </div>
 
    <div class="dash-row-mid">
 
        <div class="dash-display-content">
            <div class="dash-card-label">My Content</div>
            <div class="dash-card-title" style="font-size:15px;">Created by you</div>
            <div class="dash-content-pills">
                <span class="dash-pill dash-pill-blue">📚 <?= $modules ?> Modules</span>
                <span class="dash-pill dash-pill-green">📅 <?= $events ?> Events</span>
                <span class="dash-pill dash-pill-amber">⭕ <?= $circles ?> Circles</span>
            </div>
            <a href="My_content.php" class="dash-content-btn">✏️ View My Content</a>
        </div>
 
        <div class="dash-module">
            <?php if ($currentModule): ?>
                <span class="dash-module-tag">In Progress</span>
                <p class="dash-module-name"><?= htmlspecialchars($currentModule['name']) ?></p>
                <p class="dash-module-text">Pick up where you left off</p>
                <a href="module.php?id=<?= $currentModule['id'] ?>" class="resume-btn">▶ Resume Module</a>
            <?php else: ?>
                <span class="dash-module-tag">Modules</span>
                <p class="dash-module-name">All caught up!</p>
                <p class="dash-module-text">✨ Start something new</p>
                <a href="modules_display.php" class="dash-module-button">Browse Modules →</a>
            <?php endif; ?>
        </div>
 
        <?php if (!empty($recommendations)): ?>
        <div class="dashboard-circles">
            <div class="dash-heading">Recommended For You</div>
            <div class="dash-rec-circles">
                <?php foreach ($recommendations as $rec): ?>
                    <a href="module.php?id=<?= $rec['id'] ?>" class="story-circle">
                        <div class="circle-img" style="background-color: #<?= substr(md5($rec['name']), 0, 6) ?>;"></div>
                        <p class="circle-recommended-exp"><?= htmlspecialchars($rec['exp_level']) ?></p>
                        <p class="circle-recommended-name"><?= htmlspecialchars($rec['name']) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
 
    </div>
 
    <div class="dash-row-bottom">
        <div class="dashboard-circles">
            <div class="dash-heading">Your Circles</div>
            <div class="dashboard-circles-flex">
 
                <a href="circle_detail.php?hobby=General">
                    <div class="circle-icon" style="background-color: #cccccc;">
                        <?= extractEmoji('General') ?>
                    </div>
                    <p>General</p>
                </a>
 
                <?php if (empty($myHobbies)): ?>
                    <div style="padding: 20px; text-align: center; width: 100%;">
                        <p style="color: var(--hb-blue-dark); font-style: italic; margin-bottom: 10px;">You haven't joined any circles yet!</p>
                        <a href="circles.php" class="light-btn">Browse Circles</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($myHobbies as $hobby):
                        $color = $dbCircleColors[$hobby] ?? $hobbyColors[$hobby] ?? '#cccccc';
                    ?>
                        <a href="circle_detail.php?hobby=<?= urlencode($hobby) ?>">
                            <div class="circle-icon" style="background-color: <?= htmlspecialchars($color) ?>;">
                                <?= extractEmoji($hobby) ?>
                            </div>
                            <p><?= htmlspecialchars($hobby) ?></p>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
 
            </div>
        </div>
    </div>
 
</main>
</body>
</html>
 
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar-mini');
        var today = new Date();
        var twoWeeks = new Date();
        twoWeeks.setDate(today.getDate() + 14);
 
        function formatDate(d) {
            return d.toISOString().split('T')[0];
        }
 
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'listCustom',
            views: {
                listCustom: { type: 'list', duration: { days: 14 } }
            },
            headerToolbar: false,
            height: 'auto',
            visibleRange: {
                start: formatDate(today),
                end: formatDate(twoWeeks)
            },
            events: 'load_events.php',
            eventClick: function (info) {
                alert("Event: " + info.event.title + "\nDescription: " + info.event.extendedProps.description);
            }
        });
 
        calendar.render();
    });
</script>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>

 
<script>
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'onboarding') {
        showToast("Welcome to the community, <?= htmlspecialchars($chosenUsername) ?>! ✨");
        window.history.replaceState({}, document.title, window.location.pathname);
    }
</script>