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
$currentHobby = $_GET['hobby'] ?? 'General';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['chat_message'])) {
    $msg = trim($_POST['chat_message']);
    
    $ins = $conn->prepare("INSERT INTO circle_messages (hobby_name, user_id, message) VALUES (?, ?, ?)");
    $ins->execute([$currentHobby, $userId, $msg]);
    
    header("Location: circle_detail.php?hobby=" . urlencode($currentHobby));
    exit();
}

$hobbyColors = [
    "Cooking" => "#ff9999", "Knitting" => "#e6e6fa", "Lego" => "#ffd700",
    "Sewing" => "#ffb6c1", "Painting" => "#ffdab9", "Hiking" => "#90ee90",
    "Reading" => "#a8d0e6", "Gardening" => "#3cb371", "Baking" => "#f4a460",
    "Meditation" => "#e0ffff", "Music" => "#dda0dd", "Movies" => "#cd5c5c",
    "Gaming" => "#9370db", "Yoga" => "#ffdead"
];
$headerColor = $hobbyColors[$currentHobby] ?? '#cccccc';

$circleModules = [];
$stmt = $conn->prepare("SELECT id, name, description, exp_level FROM module");
$stmt->execute();
$allModules = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allModules as $mod) {
    if (stripos($mod['name'], $currentHobby) !== false || stripos($mod['description'], $currentHobby) !== false) {
        $circleModules[] = $mod;
    }
}

$msgStmt = $conn->prepare("
    SELECT cm.message, cm.created_at, u.username 
    FROM circle_messages cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.hobby_name = ?
    ORDER BY cm.created_at ASC
");
$msgStmt->execute([$currentHobby]);
$chatMessages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentHobby) ?> Circle</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
    <style>
        .chat-message.mine { background-color: <?= $headerColor ?>; }
    </style>
</head>
<body>

    <div class="page-container">
        
        <div style="background-color: <?= $headerColor ?>; padding: 30px; border-radius: 15px; text-align: center; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h1 style="color: #333; margin: 0; font-size: 32px; font-weight: bold;"><?= htmlspecialchars($currentHobby) ?> Circle</h1>
            <p style="color: #555; margin-top: 10px;">Connect, share, and learn about <?= htmlspecialchars(strtolower($currentHobby)) ?>!</p>
        </div>

        <h2>Modules in this Circle</h2>
        
        <?php if (empty($circleModules)): ?>
            <div class="info-box" style="background-color: #1f5077;">
                <p>No modules found for this circle yet. Be the first to create one!</p>
                <br>
                <a href="createForm.php" class="light-btn" style="text-decoration: none; color: #333;">Create Module</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($circleModules as $mod): ?>
                    <div class="info-card" style="background-color: #1f5077; color: white; border: none;">
                        <div class="card-row-between">
                            <h4 style="color: white; font-size: 20px; margin: 0;"><?= htmlspecialchars($mod['name']) ?></h4>
                            <span style="background-color: <?= $headerColor ?>; color: #333; padding: 3px 8px; border-radius: 10px; font-size: 12px; font-weight: bold;">
                                <?= htmlspecialchars($mod['exp_level']) ?>
                            </span>
                        </div>
                        <p style="color: #ccc; font-size: 14px; margin-bottom: 15px;"><?= htmlspecialchars($mod['description']) ?></p>
                        <a href="module.php?id=<?= $mod['id'] ?>" class="light-btn" style="text-decoration: none; display: inline-block;">View Module</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="chat-container">
            <h2 style="margin-top: 0; margin-bottom: 15px; color: white;">Circle Discussion</h2>
            
            <div class="chat-box" id="chatBox">
                <?php if (empty($chatMessages)): ?>
                    <p style="text-align: center; color: #999; margin-top: 20px;">No messages yet. Say hello!</p>
                <?php else: ?>
                    <?php foreach ($chatMessages as $msg): 
                        $isMine = ($msg['username'] === $_SESSION['user']['name']) ? 'mine' : '';
                    ?>
                        <div class="chat-message <?= $isMine ?>">
                            <div class="chat-author"><?= htmlspecialchars($msg['username']) ?></div>
                            <div style="font-size: 14px;"><?= htmlspecialchars($msg['message']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form method="POST" class="chat-input-row">
                <input type="text" name="chat_message" class="chat-input" placeholder="Type a message..." required autocomplete="off">
                <button type="submit" class="light-btn" style="background-color: <?= $headerColor ?>; border: none; font-weight: bold; padding: 10px 20px; color: #333;">Send</button>
            </form>
        </div>

    </div>

    <script>
        const chatBox = document.getElementById('chatBox');
        chatBox.scrollTop = chatBox.scrollHeight;
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>