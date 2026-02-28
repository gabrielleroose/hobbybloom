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
    $myHobbies = array_filter(array_map('trim', explode(',', $profile['hobbies'])));
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

$feedItems = [];
if (!empty($myHobbies)) {
    $placeholders = str_repeat('?,', count($myHobbies) - 1) . '?';
    $feedStmt = $conn->prepare("
        (SELECT 'module' AS type, u.username, m.name AS target_name, '' AS message_text, l.last_visited AS activity_date, p.profile_color
         FROM log l
         JOIN users u ON l.uid = u.id
         JOIN module m ON l.mid = m.id
         LEFT JOIN user_profiles p ON u.id = p.user_id
         WHERE l.complete = 1 AND m.name IN ($placeholders))
        UNION
        (SELECT 'chat' AS type, u.username, msg.hobby_name AS target_name, msg.message AS message_text, msg.created_at AS activity_date, p.profile_color
         FROM circle_messages msg
         JOIN users u ON msg.user_id = u.id
         LEFT JOIN user_profiles p ON u.id = p.user_id
         WHERE msg.hobby_name IN ($placeholders))
        ORDER BY activity_date DESC
        LIMIT 6
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
    <title>Circles | HobbyBloom</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>
<body class="circles-body">
    <div class="page-container">
        <aside class="search-row">
            <p>Circles</p>
            <form method="GET" action="circles.php">
                <input type="text" name="q" class="search-bar" placeholder="Search..." value="<?= htmlspecialchars($searchQuery) ?>">
            </form>
            <a href="create_circle.php" class="create-new-circle-btn">+ Create Circle</a>
        </aside>

        <main class="page-container-inside">
            <?php if ($searchQuery): ?>
                <section class="results-section">
                    <h2 class="section-heading">Results for "<?= htmlspecialchars($searchQuery) ?>"</h2>
                    <div class="suggested-flex">
                        <?php foreach ($searchResults as $circle): ?>
                            <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" class="suggested-item" style="background-color: <?= $circle['color'] ?? '#1f5077' ?>;">
                                <strong><?= htmlspecialchars($circle['name']) ?></strong>
                                <span><?= htmlspecialchars($circle['description']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <div class="main-circles-activity-wrapper">
                <section class="your-circles-wrapper">
                    <h2 class="section-heading">Your Circles</h2>
                    <div class="circles-flex">
                        <?php if (empty($myHobbies)): ?>
                            <p class="empty-msg">No interests added yet.</p>
                        <?php else: ?>
                            <?php foreach ($myHobbies as $hobby): 
                                $color = $dbCircleColors[$hobby] ?? $hobbyColors[$hobby] ?? '#cccccc'; 
                            ?>
                            <a href="circle_detail.php?hobby=<?= urlencode($hobby) ?>" class="circles-circle">
                                <div class="circle-img" style="background-color: <?= $color ?>;"></div>
                                <p class="hobby-label"><?= htmlspecialchars($hobby) ?></p>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="circles-activity-wrapper">
                    <h2 class="section-heading">Circle Highlights</h2>
                    <div class="activity-column">
                        <?php if (empty($feedItems)): ?>
                            <p class="empty-msg">No recent activity.</p>
                        <?php else: ?>
                            <?php foreach ($feedItems as $item): 
                                $avatarColor = !empty($item['profile_color']) ? $item['profile_color'] : '#' . substr(md5($item['username']), 0, 6);
                            ?>
                            <div class="highlight-card">
                                <div class="card-avatar" style="background-color: <?= $avatarColor ?>;"><?= strtoupper(substr($item['username'], 0, 1)) ?></div>
                                <div class="card-body">
                                    <p><strong>@<?= htmlspecialchars($item['username']) ?></strong> <?= $item['type'] === 'chat' ? 'messaged' : 'completed' ?> <span><?= htmlspecialchars($item['target_name']) ?></span></p>
                                    <?php if ($item['type'] === 'chat'): ?>
                                        <small>"<?= htmlspecialchars(substr($item['message_text'], 0, 40)) ?>..."</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <section class="suggested-circles-wrapper">
                <h2 class="section-heading">Suggested For You</h2>
                <div class="suggested-grid">
                    <?php foreach ($suggestedCircles as $circle): ?>
                    <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" class="suggested-card" style="border-top: 5px solid <?= $circle['color'] ?? '#1f5077' ?>;">
                        <strong style="color: <?= $circle['color'] ?? '#1f5077' ?>;"><?= htmlspecialchars($circle['name']) ?></strong>
                        <p><?= htmlspecialchars($circle['description']) ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>