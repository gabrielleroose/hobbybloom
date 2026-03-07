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
    $uid = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
    $commentText = trim($_POST['comment_text']);
    
    if ($uid && !empty($commentText)) {
        try {
            $ins = $pdo->prepare("INSERT INTO module_comments (module_id, user_id, comment_text) VALUES (?, ?, ?)");
            $ins->execute([$mid, $uid, $commentText]);
            header("Location: modules_display.php");
            exit();
        } catch (PDOException $e) {
            error_log("Comment submission error: " . $e->getMessage());
        }
    } else if (!$uid) {
        header("Location: login.php");
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
        .module_back_container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            padding: 20px;
        }

        .module_outter_card {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            height: 650px !important; 
            padding: 25px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }
        
        .module_inner_card {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .module_header {
            margin-bottom: 10px;
        }

        .mod_description {
            font-size: 0.9rem;
            margin-bottom: 15px;
            height: 45px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .module-comments-container {
            flex: 1;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
            min-height: 250px;
        }

        .comments-scroll-box {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 12px;
            padding-right: 8px;
        }

        .comment-item {
            background: rgba(255, 255, 255, 0.25);
            padding: 10px 14px;
            border-radius: 12px;
            margin-bottom: 10px;
            font-size: 0.85rem;
            color: #153853;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .comment-item strong {
            color: #1f5077;
            display: block;
            margin-bottom: 3px;
            font-size: 0.75rem;
            text-transform: lowercase;
        }

        .comment-input-form {
            display: flex;
            gap: 8px;
        }

        .comment-input-field {
            flex: 1;
            padding: 10px 15px;
            border-radius: 25px;
            border: 1px solid rgba(31, 80, 119, 0.2);
            font-size: 0.8rem;
            background: rgba(255, 255, 255, 0.8);
        }

        .comment-btn {
            background: #1f5077;
            color: white;
            border: none;
            padding: 0 18px;
            border-radius: 25px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        .comment-btn:hover {
            background: #153853;
        }

        .begin-module-wrapper {
            margin-top: auto;
        }

        .module_display_entry_button {
            width: 100%;
            margin: 0;
            padding: 12px;
            font-weight: bold;
        }
    </style>
</head>

<body class="module-body">
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
                    
                    <div class="module-comments-container">
                        <div class="comments-scroll-box">
                            <?php if (empty($comments)): ?>
                                <p style="font-size: 0.8rem; color: #666; font-style: italic; text-align: center; margin-top: 80px;">No comments yet.</p>
                            <?php else: ?>
                                <?php foreach ($comments as $c): ?>
                                    <div class="comment-item">
                                        <strong>@<?= htmlspecialchars($c['username']) ?></strong> 
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
                           <button type="submit" class="module_display_entry_button" name="module_id" value="<?= $mod['id'] ?>">Begin Module</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="create-button-wrapper" style="grid-column: 1 / -1; text-align: center; margin-top: 30px;">
            <button class="create-module-button">
                <a href="createForm.php" style="color: inherit; text-decoration: none;">Create New Module</a>
            </button>
        </div>
    </div>
</body>    
</html>
<?php include __DIR__ . '/../includes/footer.php'; ?>