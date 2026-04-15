<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/base.php';

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
    "Cooking" => "#ff9999", "Knitting" => "#e6e6fa", "Lego" => "#ffd700",
    "Sewing" => "#ffb6c1", "Painting" => "#ffdab9", "Hiking" => "#90ee90",
    "Reading" => "#a8d0e6", "Gardening" => "#3cb371", "Baking" => "#f4a460",
    "Meditation" => "#e0ffff", "Music" => "#dda0dd", "Movies" => "#cd5c5c",
    "Gaming" => "#9370db", "Yoga" => "#ffdead"
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
        if (in_array($mod['id'], $completedIds)) { continue; }

        foreach ($myHobbies as $hobby) {
            if (stripos($mod['name'], $hobby) !== false || stripos($mod['description'], $hobby) !== false) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
</head>

<body class="body-dashboard">

<main class="main-dashboard">

    <div class="my-dashboard">
        <p> Hello, <?= htmlspecialchars($chosenUsername ?? 'there') ?> </p>
    </div>

    <div class="dash-display">
        
        <div class="dash-display-streak" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <p class="dash-heading">Streaks</p>
                <p class="day-streak" style="padding-top: 2rem;">🔥<?= $streak ?> Days</p>
            </div>
            
            <div style="text-align: center; padding-bottom: 1.5rem;">
                <a href="achievements.php" style="text-decoration: none; color: #333; font-weight: bold; background-color: #ffd700; border-radius: 20px; padding: 8px 20px; font-size: 14px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s; display: inline-block;">
                    🏆 View Trophies
                </a>
            </div>
        </div>

        <div class="dash-calendar" >
            <h2 class="dash-heading schedule">Upcoming Schedule</h2>
            <div id="calendar-mini"></div>
            <div class="dash-calendar-button">
            <a class="dash-calendar-view" href="calendar.php">View Full Calendar →</a>
            </div>
        </div>
    </div>

    <div class="dash-module">
        <?php if ($currentModule): ?>
            <p class="dash-module-text">📘 Continue: <strong><?= htmlspecialchars($currentModule['name']) ?></strong></p>
            <a href="module.php?id=<?= $currentModule['id'] ?>" class="resume-btn">Resume Module</a>
        <?php else: ?>
            <h2 class="dash-heading">Modules</h2>
            <p class="dash-module-text">✨You're all caught up! Start a new module.</p>
            <a href="modules_display.php" class="dash-module-button">Browse Modules →</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($recommendations)): ?>
    <div class="dashboard-circles">
        <div class="horizontal-scroll">
            <h2 class="dash-heading">Recommended Modules For You</h2>
            <div class="dash-rec-circles">

            <?php foreach ($recommendations as $rec): ?>
            <a href="module.php?id=<?= $rec['id'] ?>" style="text-decoration: none; color: inherit;">
                <div class="story-circle">
                    <div class="circle-img" style="background-color: #<?= substr(md5($rec['name']), 0, 6) ?>;"></div>
                    <p class="circle-recommended-exp"><?= htmlspecialchars($rec['exp_level']) ?></p>
                    <p class="circle-recommended-name"><?= htmlspecialchars($rec['name']) ?></p>
                
                </div>
            </a>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="dashboard-circles circle">
        <div class="horizontal-scroll">
            <h2 class="dash-heading">Your Circles</h2>

            <div class="dashboard-circles-flex">

                <a href="circle_detail.php?hobby=General" style="text-decoration: none; color: inherit;">
                    <div class="story-circle">
                        <div class="circle-icon" style="background-color: #cccccc;">
                            <?= extractEmoji('General') ?>
                        </div>
                        <p>General</p>
                    </div>
                </a>
            
                <?php if (empty($myHobbies)): ?>
                    <div style="padding: 20px; text-align: center; width: 100%;">
                        <p style="color: #1E5077; font-style: italic; margin-bottom: 10px;">You haven't joined any circles yet!</p>
                        <a href="circles.php" class="light-btn" style="text-decoration: none; background-color: #1E5077; color: white; padding: 8px 20px; border-radius: 20px;">Browse Circles</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($myHobbies as $hobby): 
                        $color = $dbCircleColors[$hobby] ?? $hobbyColors[$hobby] ?? '#cccccc'; 
                    ?>
                    <a href="circle_detail.php?hobby=<?= urlencode($hobby) ?>" style="text-decoration: none; color: inherit;">
                        <div class="story-circle">
                            <div class="circle-icon" style="background-color: <?= htmlspecialchars($color) ?>;">
                                <?= extractEmoji($hobby) ?>
                            </div>
                            <p><?= htmlspecialchars($hobby) ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
            
        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar-mini');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'listWeek',
        headerToolbar: false,   
        height: 'auto',
        events: 'load_events.php', 
        eventClick: function(info) {
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

</body>
</html>