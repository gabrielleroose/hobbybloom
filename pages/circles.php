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

$myHobbies = [];
$stmt = $conn->prepare("SELECT hobbies FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();
if ($profile && $profile['hobbies']) {
    $myHobbies = explode(', ', $profile['hobbies']);
}

$hobbyColors = [
    "Cooking" => "#ff9999", "Knitting" => "#e6e6fa", "Lego" => "#ffd700",
    "Sewing" => "#ffb6c1", "Painting" => "#ffdab9", "Hiking" => "#90ee90",
    "Reading" => "#a8d0e6", "Gardening" => "#3cb371", "Baking" => "#f4a460",
    "Meditation" => "#e0ffff", "Music" => "#dda0dd", "Movies" => "#cd5c5c",
    "Gaming" => "#9370db", "Yoga" => "#ffdead"
];

$stmt = $conn->query("SELECT * FROM circle ORDER BY RAND() LIMIT 5");
$suggestedCircles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$feedStmt = $conn->prepare("
    SELECT u.username, m.name AS module_name, m.exp_level, l.last_visited
    FROM log l
    JOIN users u ON l.uid = u.id
    JOIN module m ON l.mid = m.id
    WHERE l.complete = 1
    ORDER BY l.last_visited DESC
    LIMIT 10
");
$feedStmt->execute();
$feedItems = $feedStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Circles</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>

<body>

    <div class="page-container">

        <div class="search-row">
            <input type="text" class="search-bar" placeholder="Search Circles...">
        </div>

        <h2>Your Circles</h2>
        <div class="horizontal-scroll">
            <?php if (empty($myHobbies)): ?>
                <p style="color: white; font-style: italic;">You haven't added any interests yet. Update your account to see your circles!</p>
            <?php else: ?>
                <?php foreach ($myHobbies as $hobby): 
                    $color = $hobbyColors[$hobby] ?? '#cccccc'; 
                ?>
                <div class="story-circle">
                    <div class="circle-img" style="background-color: <?= $color ?>;"></div>
                    <p style="color: white; font-size: 12px; margin-top: 5px; text-align: center;"><?= htmlspecialchars($hobby) ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <h2>Suggested For You</h2>
        <div class="horizontal-scroll">
            <?php if (empty($suggestedCircles)): ?>
                <div class="suggested-item" style="display: flex; align-items: center; justify-content: center; color: white; padding: 10px; text-align: center;">
                    No new circles right now.
                </div>
            <?php else: ?>
                <?php foreach ($suggestedCircles as $circle): ?>
                <div class="suggested-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px;">
                    <strong style="color: white;"><?= htmlspecialchars($circle['name']) ?></strong>
                    <span style="color: #ccc; font-size: 10px; text-align: center; margin-top: 5px;"><?= htmlspecialchars(substr($circle['description'], 0, 30)) ?>...</span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <h2>Your Feed</h2>
        <?php if (empty($feedItems)): ?>
            <p style="color: white; font-style: italic;">No recent activity in your network. Be the first to complete a module!</p>
        <?php else: ?>
            <?php foreach ($feedItems as $item): ?>
            <div class="feed-card">
                <div class="feed-header" style="position: relative; padding-bottom: 5px;">
                    <div class="feed-avatar" style="background-color: #<?= substr(md5($item['username']), 0, 6) ?>;"></div> 
                    <span class="feed-username" style="color: white; font-weight: bold;">
                        <?= htmlspecialchars($item['username']) ?>
                    </span>
                    <span style="color: #ccc; font-size: 12px; margin-left: 10px;">
                        completed a module!
                    </span>
                </div>
                <div class="feed-image-placeholder" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; background-color: #2c6ca3;">
                    <h3 style="color: white; margin: 0;"><?= htmlspecialchars($item['module_name']) ?></h3>
                    <p style="color: #e0e0e0; margin: 5px 0 0 0;">Level: <?= htmlspecialchars($item['exp_level']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>