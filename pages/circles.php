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
$filterCategory = $_GET['category'] ?? '';
$viewMode = $_GET['view'] ?? 'suggested';

$dbCircleColors = [];
$colorStmt = $conn->query("SELECT name, color FROM circle");
while ($row = $colorStmt->fetch(PDO::FETCH_ASSOC)) {
    $dbCircleColors[trim($row['name'])] = $row['color'];
}

$searchResults = [];
if ($searchQuery) {
    $sStmt = $conn->prepare("SELECT * FROM circle WHERE name LIKE ? OR description LIKE ?");
    $sStmt->execute(["%$searchQuery%", "%$searchQuery%"]);
    $searchResults = $sStmt->fetchAll(PDO::FETCH_ASSOC);
}

$allCircles = [];
if ($viewMode === 'all') {
    $query = "SELECT * FROM circle";
    $params = [];
    if ($filterCategory) {
        $query .= " WHERE category = ?";
        $params[] = $filterCategory;
    }
    $query .= " ORDER BY name ASC";
    $allStmt = $conn->prepare($query);
    $allStmt->execute($params);
    $allCircles = $allStmt->fetchAll(PDO::FETCH_ASSOC);
}

$myHobbies = [];
$stmt = $conn->prepare("SELECT hobbies FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();
if ($profile && $profile['hobbies']) {
    $myHobbies = explode(', ', $profile['hobbies']);
}

$dbCircleColors = [];
if (!empty($myHobbies)) {
    $placeholders = str_repeat('?,', count($myHobbies) - 1) . '?';
    $colorStmt = $conn->prepare("SELECT name, color FROM circle WHERE name IN ($placeholders)");
    $colorStmt->execute($myHobbies);
    while ($row = $colorStmt->fetch(PDO::FETCH_ASSOC)) {
        $dbCircleColors[$row['name']] = $row['color'];
    }
}

$hobbyColors = [
    "Cooking" => "#ff9999", "Knitting" => "#e6e6fa", "Lego" => "#ffd700",
    "Sewing" => "#ffb6c1", "Painting" => "#ffdab9", "Hiking" => "#90ee90",
    "Reading" => "#a8d0e6", "Gardening" => "#3cb371", "Baking" => "#f4a460",
    "Meditation" => "#e0ffff", "Music" => "#dda0dd", "Movies" => "#cd5c5c",
    "Gaming" => "#9370db", "Yoga" => "#ffdead"
];

$suggestedCircles = [];
if (!empty($myHobbies)) {
    $placeholders = str_repeat('?,', count($myHobbies) - 1) . '?';
    $suggestStmt = $conn->prepare("SELECT * FROM circle WHERE name NOT IN ($placeholders) ORDER BY RAND() LIMIT 5");
    $suggestStmt->execute($myHobbies);
    $suggestedCircles = $suggestStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $suggestStmt = $conn->query("SELECT * FROM circle ORDER BY RAND() LIMIT 5");
    $suggestedCircles = $suggestStmt->fetchAll(PDO::FETCH_ASSOC);
}

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
    <title>Circles Hub | HobbyBloom</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
    <style>
        .hub-top-nav {
            width: 100%;
            display: flex;
            justify-content: center;
            margin: 20px 0 35px 0;
        }

        .glass-tabs {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 40px;
            padding: 5px;
            display: flex;
            gap: 5px;
        }

        .tab-btn {
            padding: 10px 25px;
            border-radius: 35px;
            text-decoration: none;
            font-weight: 600;
            color: #1f5077;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .tab-btn.active {
            background-color: #1f5077;
            color: white;
            box-shadow: 0 4px 10px rgba(31, 80, 119, 0.2);
        }

        .filter-row {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-chip {
            padding: 6px 18px;
            border-radius: 20px;
            background: white;
            color: #1f5077;
            text-decoration: none;
            font-size: 0.85rem;
            border: 1px solid rgba(31, 80, 119, 0.1);
        }

        .filter-chip.active {
            background: #1f5077;
            color: white;
            border-color: #1f5077;
        }
    </style>
</head>

<body class="circles-body">

    <div class="hub-top-nav">
        <div class="glass-tabs">
            <a href="circles.php" class="tab-btn <?= $viewMode === 'suggested' ? 'active' : '' ?>">My Feed</a>
            <a href="circles.php?view=all" class="tab-btn <?= $viewMode === 'all' ? 'active' : '' ?>">Explore Circles</a>
        </div>
    </div>

    <div class="page-container">
        <aside class="search-row">
            <p style="font-size: 1.5rem; font-weight: bold; color: #1f5077; margin-bottom: 10px;">Circles Hub</p>
            <form method="GET" action="circles.php">
                <input type="text" name="q" class="search-bar" placeholder="Search..." value="<?= htmlspecialchars($searchQuery) ?>">
            </form>
            <a href="create_circle.php" class="create-new-circle-btn" style="margin-top: 15px; display: block; text-align: center; padding: 10px 20px;">+ Create Circle</a>
        </aside>

        <main class="page-container-inside">
            <?php if ($searchQuery): ?>
                <section class="results-section">
                    <h2 class="section-heading">Results for "<?= htmlspecialchars($searchQuery) ?>"</h2>
                    <div class="suggested-grid">
                        <?php foreach ($searchResults as $circle): ?>
                            <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" class="suggested-card" style="border-top: 5px solid <?= $circle['color'] ?>;">
                                <strong style="color: <?= $circle['color'] ?>;"><?= htmlspecialchars($circle['name']) ?></strong>
                                <p><?= htmlspecialchars($circle['description']) ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($viewMode === 'all'): ?>
                <section class="results-section">
                    <div class="filter-row">
                        <a href="circles.php?view=all" class="filter-chip <?= !$filterCategory ? 'active' : '' ?>">All Categories</a>
                        <a href="circles.php?view=all&category=Arts" class="filter-chip <?= $filterCategory === 'Arts' ? 'active' : '' ?>">Arts</a>
                        <a href="circles.php?view=all&category=Technical" class="filter-chip <?= $filterCategory === 'Technical' ? 'active' : '' ?>">Technical</a>
                        <a href="circles.php?view=all&category=Wellness" class="filter-chip <?= $filterCategory === 'Wellness' ? 'active' : '' ?>">Wellness</a>
                    </div>
                    <div class="suggested-grid">
                        <?php foreach ($allCircles as $circle): ?>
                            <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" class="suggested-card" style="border-top: 5px solid <?= $circle['color'] ?>;">
                                <strong style="color: <?= $circle['color'] ?>;"><?= htmlspecialchars($circle['name']) ?></strong>
                                <p><?= htmlspecialchars($circle['description']) ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php else: ?>
                <div class="main-circles-activity-wrapper">
                    <section class="your-circles-wrapper">
                        <h2 class="section-heading">Your Circles</h2>
                        <div class="circles-flex">
                            <?php foreach ($myHobbies as $hobby):
                                $color = $dbCircleColors[trim($hobby)] ?? '#cccccc';
                            ?>
                                <a href="circle_detail.php?hobby=<?= urlencode($hobby) ?>" class="circles-circle">
                                    <div class="circle-img" style="background-color: <?= $color ?>;"></div>
                                    <p class="hobby-label"><?= htmlspecialchars($hobby) ?></p>
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
                    </section>

                    <section class="circles-activity-wrapper">
                        <h2 class="section-heading">Recent Highlights</h2>
                        <div class="activity-column">
                            <?php foreach ($feedItems as $item):
                                $avatarColor = !empty($item['profile_color']) ? $item['profile_color'] : '#' . substr(md5($item['username']), 0, 6);
                            ?>
                                <a href="circle_detail.php?hobby=<?= urlencode($item['target_name']) ?>" class="highlight-link">
                                    <div class="highlight-card">
                                        <div class="card-avatar" style="background-color: <?= $avatarColor ?>;"><?= strtoupper(substr($item['username'], 0, 1)) ?></div>
                                        <div class="card-body">
                                            <p><strong>@<?= htmlspecialchars($item['username']) ?></strong> <?= $item['type'] === 'chat' ? 'messaged' : 'completed module' ?> <span><?= htmlspecialchars($item['target_name']) ?></span></p>
                                            <?php if ($item['message_text']): ?>
                                                <p style="font-size: 0.8rem; font-style: italic; color: #666; margin-top: 4px;">"<?= htmlspecialchars($item['message_text']) ?>"</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <?php if ($viewMode !== 'all'): ?>
    <section class="suggested-circles-wrapper">
        <h2 class="section-heading">Suggested For You</h2>
        <div class="suggested-grid">
            <?php foreach ($suggestedCircles as $circle): ?>
                <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" class="suggested-card" style="border-top: 5px solid <?= $circle['color'] ?>;">
                    <strong style="color: <?= $circle['color'] ?>;"><?= htmlspecialchars($circle['name']) ?></strong>
                    <p><?= htmlspecialchars($circle['description']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>