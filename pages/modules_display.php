<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../config/db.php'; //necessary to connect to db. 

require_once __DIR__ . '/../config/twig.php'; //necessary to load twig
include 'base.php';

$googleId = $_SESSION['google_id'] ?? null;
if (!$googleId) {
    header('Location: index.php');
    exit;
}

$user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
$stmt = $conn->prepare($user_id_sql);
$stmt->execute([':gid' => $googleId]);

$user_id = $stmt->fetchColumn();

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
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            gap: 40px !important;
            padding: 50px 20px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .module_outter_card {
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(12px);
            border-radius: 35px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            width: 450px !important; 
            height: 650px !important;
            padding: 30px !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
        }
        
        .module_inner_card {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .mod_description {
            font-size: 1rem;
            margin-bottom: 10px;
            line-height: 1.4;
            color: #153853;
        }

        .module-comments-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            height: 220px !important;
            display: flex;
            flex-direction: column;
            margin: 15px 0;
            box-sizing: border-box;
        }

        .comments-scroll-box {
            flex: 1;
            overflow-y: auto !important;
            margin-bottom: 10px;
            padding-right: 8px;
        }

        .comment-item {
            background: rgba(255, 255, 255, 0.5);
            padding: 10px 12px;
            border-radius: 12px;
            margin-bottom: 10px;
            font-size: 0.85rem;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }

        .comment-item strong {
            display: block;
            color: #1f5077;
            font-size: 0.75rem;
            margin-bottom: 2px;
        }

        .comment-input-form {
            display: flex;
            gap: 8px;
        }

        .comment-input-field {
            flex: 1;
            padding: 8px 12px;
            border-radius: 20px;
            border: 1px solid rgba(31, 80, 119, 0.2);
            font-size: 0.8rem;
        }

        .comment-btn {
            background: #1f5077;
            color: white;
            border: none;
            padding: 0 15px;
            border-radius: 20px;
            cursor: pointer;
        }

        .begin-module-wrapper {
            margin-top: auto;
            width: 100%;
        }

        .module_display_entry_button {
            width: 100%;
            padding: 15px;
            font-weight: bold;
            border-radius: 15px;
            background: #1f5077;
            color: white;
            border: none;
            cursor: pointer;
        }

        .create-button-wrapper {
            width: 100%;
            display: flex;
            justify-content: center;
            margin-top: 40px;
            padding-bottom: 60px;
            grid-column: 1 / -1;
        }

        .create-module-button {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(5px);
            padding: 15px 40px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .create-module-button a {
            text-decoration: none;
            color: #1f5077;
            font-weight: bold;
            font-size: 1.1rem;
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
                    <div class="mod_description">
                        <p><?= htmlspecialchars($mod['description'] ?? '')?></p>
                        <p style="font-size: 0.85rem; font-style: italic; margin-top: 5px;">
                            Level: <?= htmlspecialchars($mod['exp_level'] ?? '') ?> | Lessons: <?= htmlspecialchars($mod['num_lessons'] ?? '') ?>
                        </p>
                    </div>
                    
                    <div class="module-comments-container">
                        <div class="comments-scroll-box">
                            <?php if (empty($comments)): ?>
                                <p style="font-size: 0.85rem; color: #666; font-style: italic; text-align: center; margin-top: 60px;">No comments yet.</p>
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

        <div class="create-button-wrapper">
            <button class="create-module-button">
                <a href="createForm.php">Create New Module</a>
            </button>
        </div>
    </div>
    
</body>    
</html>
<?php include __DIR__ . '/../includes/footer.php'; ?>