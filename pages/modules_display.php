<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../config/twig.php'; 
include 'base.php';

$googleId = $_SESSION['google_id'] ?? null;
if (!$googleId) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $mid = $_POST['module_id'];
    $uid = $_SESSION['user']['id'];
    $commentText = trim($_POST['comment_text']);
    
    if (!empty($commentText)) {
        $ins = $pdo->prepare("INSERT INTO module_comments (module_id, user_id, comment_text) VALUES (?, ?, ?)");
        $ins->execute([$mid, $uid, $commentText]);
        header("Location: modules_display.php");
        exit();
    }
}

$fetch_query = "SELECT m.*, msp.msid 
                FROM module AS m 
                LEFT JOIN module_stage_progress AS msp ON msp.mid = m.id 
                ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($fetch_query);
$stmt->execute();
$all_mods = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Modules | HobbyBloom</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
    <style>
        .module-comments-container {
            margin-top: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            height: 180px;
        }

        .comments-scroll-box {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 8px;
            padding-right: 5px;
        }

        .comment-item {
            background: rgba(255, 255, 255, 0.15);
            padding: 6px 10px;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 0.8rem;
            color: #153853;
            line-height: 1.3;
        }

        .comment-item strong {
            color: #1f5077;
        }

        .comment-input-form {
            display: flex;
            gap: 5px;
        }

        .comment-input-field {
            flex: 1;
            padding: 6px 12px;
            border-radius: 20px;
            border: 1px solid rgba(31, 80, 119, 0.2);
            font-size: 0.75rem;
            background: rgba(255, 255, 255, 0.5);
        }

        .comment-btn {
            background: #1f5077;
            color: white;
            border: none;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: 0.2s;
        }

        .comment-btn:hover {
            background: #153853;
        }
        
        .module_outter_card {
            height: auto !important;
            min-height: 520px;
            padding-bottom: 25px;
            display: flex;
            flex-direction: column;
        }
        
        .module_inner_card {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .begin-module-wrapper {
            margin-top: auto;
            padding-top: 15px;
        }
    </style>
</head>

        .module_outter_card {
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(12px);
            border-radius: 35px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            width: 450px !important; 
            height: 720px !important; 
            padding: 35px !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            position: relative;
        }

        .module_inner_card {
            width: 100% !important;
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            padding-left: 0 !important; 
        }

        .module-comments-container {
            width: 100%;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 25px;
            padding: 15px;
            height: 280px !important; 
            margin: 15px 0;
            display: flex !important;
            flex-direction: column !important;
            box-sizing: border-box !important;
        }

        .comments-scroll-box {
            flex: 1 !important;
            overflow-y: auto !important; 
            padding-right: 10px;
            display: flex;
            flex-direction: column; 
            text-align: left;
        }

        .comment-item {
            background: rgba(255, 255, 255, 0.6);
            padding: 10px 12px;
            border-radius: 15px;
            margin-bottom: 10px;
            font-size: 0.85rem;
            word-wrap: break-word;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .profile-link {
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            color: inherit;
            transition: opacity 0.2s;
        }

        .profile-link:hover {
            opacity: 0.7;
        }

        .user-color-circle {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .begin-module-wrapper {
            width: 100% !important;
            margin-top: auto !important;
        }

        .module_display_entry_button {
            width: 100% !important;
            padding: 16px !important;
            font-weight: bold !important;
            border-radius: 20px !important;
            background: #1f5077 !important;
            color: white !important;
            border: none !important;
            cursor: pointer !important;
            margin: 0 !important;
        }

        .create-button-wrapper {
            width: 100%;
            margin-top: 50px;
            padding-bottom: 60px;
            text-align: center;
        }

        .create-module-button {
            background: white;
            padding: 15px 40px;
            border-radius: 35px;
            border: 1px solid #1f5077;
            cursor: pointer;
        }
    </style>
</head>
    <div class="module_back_container">
        <?php foreach ($all_mods as $mod): 
            $c_stmt = $pdo->prepare("
                SELECT mc.*, u.username 
                FROM module_comments mc 
                JOIN users u ON mc.user_id = u.id 
                WHERE mc.module_id = ? 
                ORDER BY mc.created_at ASC
            ");
            $c_stmt->execute([$mod['id']]);
            $comments = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <div class="module_outter_card">
                <div class="module_inner_card">
                    <div class="module_header">
                        <div class="mod_name"><h3><?= htmlspecialchars($mod['name'] ?? '')?></h3></div>
                        <div class="rating"><?= str_repeat('⭐', (int)($mod['rating'] ?? 0)) ?></div>
                    </div>
                    <div class="mod_description"><p><?= htmlspecialchars($mod['description'] ?? '')?></p></div>
                    <div class="exp_level"><p>Level: <?= htmlspecialchars($mod['exp_level'] ?? '')?></p></div>
                    <div class="num_lessons"><p>Lessons: <?= htmlspecialchars($mod['num_lessons'] ?? '')?></p></div>

                    <div class="module-comments-container">
                        <div class="comments-scroll-box">
                            <?php if (empty($comments)): ?>
                                <p style="font-size: 0.75rem; color: #666; font-style: italic; text-align: center; margin-top: 50px;">No comments yet.</p>
                            <?php else: ?>
                                <?php foreach ($comments as $c): ?>
                                    <div class="comment-item">
                                        <strong>@<?= htmlspecialchars($c['username']) ?>:</strong> 
                                        <?= htmlspecialchars($c['comment_text']) ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" class="comment-input-form">
                            <input type="hidden" name="module_id" value="<?= $mod['id'] ?>">
                            <input type="text" name="comment_text" class="comment-input-field" placeholder="Write a comment..." required>
                            <button type="submit" name="submit_comment" class="comment-btn">Post</button>
                        </form>
                    </div>

                    <div class="begin-module-wrapper">
                        <form action="./module.php" method="GET">
                           <button type="submit" class="module_display_entry_button" name="module_id" value="<?= $mod['id'] ?>" style="margin: 0; width: 100%;">Begin Module</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="create-button-wrapper">
            <button class="create-module-button" onclick="location.href='createForm.php'">
                <a href="createForm.php" style="text-decoration:none; color:#1f5077; font-weight:bold;">Create New Module</a>
            </button>
        </div>
    </div>
    
</body>    
</html>
<?php include __DIR__ . '/../includes/footer.php'; ?>