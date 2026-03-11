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

$fetch_query = "SELECT m.*, msp.msid, u.username, u.email
                FROM module AS m 
                LEFT JOIN module_stage_progress AS msp ON msp.mid = m.id
                JOIN users AS u on m.cid = u.id
                ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($fetch_query);
$stmt->execute();
$all_mods = $stmt->fetchAll(PDO::FETCH_ASSOC);


$module_delete_id = NULL;

if (isset($_POST['module_delete'])) {
    $module_delete_id = (int) $_POST['module_delete'];
    $module_delete_sql = "DELETE FROM module WHERE id = ? AND cid = ?";
    $stmt = $conn->prepare($module_delete_sql);
    $stmt->execute([$module_delete_id, $user_id]);
    
    header("Location: modules_display.php");
    exit();
}




?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Modules | HobbyBloom</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">

</head>
<body class="module-body">
    <div class="module_back_container">
        <?php foreach ($all_mods as $mod): 
            $c_stmt = $pdo->prepare("
                SELECT mc.*, u.username, up.profile_color, u.id as user_actual_id
                FROM module_comments mc 
                JOIN users u ON mc.user_id = u.id 
                LEFT JOIN user_profiles up ON u.id = up.user_id
                WHERE mc.module_id = ? 
                ORDER BY mc.created_at ASC
            ");
            $c_stmt->execute([$mod['id']]);
            $comments = $c_stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <div class="module_outter_card">
                <div class="module_inner_card">
                    <div class="module-header">
                        <h3 class="module-name"><?= htmlspecialchars($mod['name'] ?? '')?></h3>

                    </div>

                        
                                <p class="mod_description"><?= htmlspecialchars($mod['description'] ?? '')?></p>

                                <div><?= str_repeat('⭐', (int)($mod['rating'] ?? 0)) ?></div>

        
                                <div class="mod_exp">
                                <p>
                                    Level: <?= htmlspecialchars($mod['exp_level'] ?? 'beginner') ?> | Lessons: <?= htmlspecialchars($mod['num_lessons'] ?? '0') ?>
                                </p>
                             </div>
                        
                             <div class="module-author">
                                <p>Created by: <?= htmlspecialchars($mod['username'] ?? '') ?></p>
                                <p>Contact the creator here: <?= htmlspecialchars($mod['email'] ?? '')?><p>
                            </div>
                    
                
                <div class="module-back">
                                <div class="module-comments-container">
                                    <div class="comments-scroll-box" id="box-<?= $mod['id'] ?>">
                                        <?php if (empty($comments)): ?>
                                            <p style="font-size: 0.8rem; color: #666; font-style: italic; text-align: center; margin-top: 80px;">No comments yet.</p>
                                        <?php else: ?>
                                            <?php foreach ($comments as $c): ?>
                                                <div class="comment-item">
                                                    <a href="profile.php?user_id=<?= $c['user_actual_id'] ?>" class="profile-link">
                                                        <div class="user-color-circle" style="background-color: <?= htmlspecialchars($c['profile_color'] ?: '#cccccc') ?>;"></div>
                                                        <strong>@<?= htmlspecialchars($c['username']) ?></strong>
                                                    </a>
                                                    <div style="flex: 1; margin-left: 10px;">
                                                        <?= htmlspecialchars($c['comment_text']) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <script>
                                        var d = document.getElementById("box-<?= $mod['id'] ?>");
                                        d.scrollTop = d.scrollHeight;
                                    </script>

                                    <form method="POST" class="comment-input-form">
                                        <input type="hidden" name="module_id" value="<?= $mod['id'] ?>">
                                        <input type="text" name="comment_text" class="comment-input-field" placeholder="Write a comment..." required style="flex:1; padding:10px; border-radius:20px; border:1px solid #ccc;">
                                        <button type="submit" name="submit_comment" class="comment-btn" style="background:#1f5077; color:white; border:none; padding:5px 15px; border-radius:20px;">Post</button>
                                    </form>
                                </div>
                        </div>
                                <form class="begin-module-flex"  action="./module.php" method="POST">
                                    <input type="hidden">
                                <button type="submit" class="module_display_entry_button" name="module_id" value="<?= $mod['id'] ?>">Begin Module</button>
                                </form>
                

                                <form action="modules_display.php" method="POST">
                                    <?php if ($mod['cid'] == $user_id): ?> 
                                        <button type="submit" class="module_display_delete_button" name="module_delete" value="<?= $mod['id']?>">Delete Module</button>
                                    <?php endif ?>
                                </form>

                                <form action="createForm.php" method="POST">
                                    <?php if ($mod['cid'] == $user_id): ?>
                                        <button type="submit" class="module_display_delete_button" name="module_edit" value="<?= $mod['id']?>">Edit Module</button>
                                    <?php endif ?>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="create-button-wrapper">
                        <button class="create-module-button" onclick="location.href='createForm.php'">
                            <a href="createForm.php">Create New Module</a>
                        </button>
                    </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>    
</body>    
</html>
