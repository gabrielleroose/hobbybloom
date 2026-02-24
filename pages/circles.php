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

$searchQuery = trim($_GET['q'] ?? '');
$searchResults = [];
if ($searchQuery) {
    $sStmt = $conn->prepare("SELECT * FROM circle WHERE name LIKE ? OR description LIKE ?");
    $sStmt->execute(["%$searchQuery%", "%$searchQuery%"]);
    $searchResults = $sStmt->fetchAll(PDO::FETCH_ASSOC);
}

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

<body class="circles-body">

    <div class="page-container">

        <div class="search-row">
            <p>Circles</p>
            
            <form method="GET" action="circles.php" style="margin-bottom: 20px;">
                <input type="text" name="q" class="search-bar" placeholder="Search Circles..." value="<?= htmlspecialchars($searchQuery) ?>">
                <!-- <button type="submit" class="search-circle-btn">Search</button> -->
            </form>

            <a href="create_circle.php" class="create-new-circle-btn">+ Create New Circle</a>
        </div>

        <div class="page-container-inside">
            
            <?php if ($searchQuery): ?>
                <div style="margin-bottom: 30px;">
                    <h2>Search Results for "<?= htmlspecialchars($searchQuery) ?>"</h2>
                    <div class="horizontal-scroll">
                        <?php if (empty($searchResults)): ?>
                            <p style="color: white; font-style: italic;">No circles found matching your search.</p>
                        <?php else: ?>
                            <?php foreach ($searchResults as $circle): ?>
                            <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" style="text-decoration: none; color: inherit;">
                                <div class="suggested-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px; background-color: <?= htmlspecialchars($circle['color'] ?? '#1f5077') ?>;">
                                    <strong style="color: white;"><?= htmlspecialchars($circle['name']) ?></strong>
                                    <span style="color: #ccc; font-size: 10px; text-align: center; margin-top: 5px;"><?= htmlspecialchars(substr($circle['description'], 0, 30)) ?>...</span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="main-circles-activity-wrapper">

        
                <div class="your-circles-wrapper">
                    <h2>Your Circles</h2>

                    <div class="circles-flex">

                    <?php if (empty($myHobbies)): ?>
                        <p style="color: white; font-style: italic;">You haven't added any interests yet. Update your account to see your circles!</p>
                    <?php else: ?>
                        <?php foreach ($myHobbies as $hobby): 
                            $color = $hobbyColors[$hobby] ?? '#cccccc'; 
                        ?>
                        <a href="circle_detail.php?hobby=<?= urlencode($hobby) ?>" style="text-decoration: none;">
                            <div class="circles-circle">
                                <div class="circle-img" style="background-color: <?= $color ?>;"></div>
                                <p style="color: white; font-size: 12px; margin-top: 5px; text-align: center;"><?= htmlspecialchars($hobby) ?></p>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    </div>
                </div>

                <div class="circles-activity-wrapper">
                    <h2>Your Feed</h2>

                    <div class="activity-flex">
                
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
                </div>

            </div>

        
            <div class="suggested-circles-wrapper">
                <h2>Suggested For You</h2>

                    <div class="suggested-flex">

                        <?php if (empty($suggestedCircles)): ?>
                            <div class="suggested-item" style="display: flex; align-items: center; justify-content: center; color: white; padding: 10px; text-align: center;">
                                No new circles right now.
                            </div>
                        <?php else: ?>
                            <?php foreach ($suggestedCircles as $circle): ?>
                            <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" style="text-decoration: none; color: inherit;">
                                <div class="suggested-item" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px; background-color: <?= htmlspecialchars($circle['color'] ?? '#1f5077') ?>;">                            
                                    <strong style="color: white;"><?= htmlspecialchars($circle['name']) ?></strong>
                                    <span style="color: #ccc; font-size: 10px; text-align: center; margin-top: 5px;"><?= htmlspecialchars(substr($circle['description'], 0, 30)) ?>...</span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
            </div>


        </div>

    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>