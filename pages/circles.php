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
    $myHobbies = array_filter(array_map('trim', explode(',', $profile['hobbies'])));
}

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

$feedItems = [];
if (!empty($myHobbies)) {
    $placeholders = str_repeat('?,', count($myHobbies) - 1) . '?';
    $feedStmt = $conn->prepare("
        (SELECT 'module' AS type, u.username, m.name AS target_name, '' AS message_text, l.last_visited AS activity_date, p.profile_color
         FROM log l JOIN users u ON l.uid = u.id JOIN module m ON l.mid = m.id LEFT JOIN user_profiles p ON u.id = p.user_id
         WHERE l.complete = 1 AND m.name IN ($placeholders))
        UNION
        (SELECT 'chat' AS type, u.username, msg.hobby_name AS target_name, msg.message AS message_text, msg.created_at AS activity_date, p.profile_color
         FROM circle_messages msg JOIN users u ON msg.user_id = u.id LEFT JOIN user_profiles p ON u.id = p.user_id
         WHERE msg.hobby_name IN ($placeholders))
        ORDER BY activity_date DESC LIMIT 4
    ");
    $params = array_merge($myHobbies, $myHobbies);
    $feedStmt->execute($params);
    $feedItems = $feedStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Circles Hub | HobbyBloom</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
    <style>
        .glass-tab-container { 
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

        .filter-row { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-chip { 
            padding: 6px 18px; border-radius: 20px; background: white; 
            color: #1f5077; text-decoration: none; font-size: 0.85rem; 
            border: 1px solid rgba(31, 80, 119, 0.1); 
        }
        .filter-chip.active { background: #1f5077; color: white; border-color: #1f5077; }
    </style>
</head>

<body class="circles-body">
    <div class="page-container">
        
        <div class="glass-tab-container">
            <div class="glass-tabs">
                <a href="circles.php" class="tab-btn <?= $viewMode === 'suggested' ? 'active' : '' ?>">My Feed</a>
                <a href="circles.php?view=all" class="tab-btn <?= $viewMode === 'all' ? 'active' : '' ?>">Explore Circles</a>
            </div>
        </div>

        <aside class="search-row" style="text-align: center;">
            <p style="font-size: 1.5rem; font-weight: bold; color: #1f5077; margin-bottom: 10px;">Circles Hub</p>
            <form method="GET" action="circles.php" style="display: inline-block;">
                <input type="text" name="q" class="search-bar" placeholder="Search..." value="<?= htmlspecialchars($searchQuery) ?>">
            </form>
            <a href="create_circle.php" class="create-new-circle-btn" style="margin-top: 15px; display: inline-block; padding: 10px 20px;">+ Create Circle</a>
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

                <section class="suggested-circles-wrapper" style="margin-top: 40px;">
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
        </main>
    </div>

    <?php if ($viewMode !== 'all' && !$searchQuery): ?>
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