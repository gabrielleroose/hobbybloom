<?php
ob_start();
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
 
$user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
$stmt = $conn->prepare($user_id_sql);
$stmt->execute([':gid' => $googleId]);
$user_id = $stmt->fetchColumn();
 
$completed_count_sql = "SELECT COUNT(*) FROM module_user_completion WHERE uid = ? AND is_complete = 1";
$stmt = $pdo->prepare($completed_count_sql);
$stmt->execute([$user_id]);
$completedCount = (int)$stmt->fetchColumn();
 
$favorite_count_sql = "SELECT COUNT(*) FROM module_user_favorite WHERE uid = ? AND is_favorite = 1";
$stmt = $pdo->prepare($favorite_count_sql);
$stmt->execute([$user_id]);
$favoriteCount = (int)$stmt->fetchColumn();
 
$completedEmpty = $completedCount === 0;
$favoriteEmpty  = $favoriteCount === 0;
 
if (isset($_POST['toggle_fav'], $_POST['module_id']) && $user_id) {
    $module_id = (int)$_POST['module_id'];
    $stmt = $conn->prepare("SELECT is_favorite FROM module_user_favorite WHERE mid = :mid AND uid = :uid");
    $stmt->execute([':mid' => $module_id, ':uid' => $user_id]);
    $current = $stmt->fetchColumn();
 
    if ($current === false) {
        $stmt = $conn->prepare("INSERT INTO module_user_favorite (mid, uid, is_favorite) VALUES (:mid, :uid, 1)");
        $stmt->execute([':mid' => $module_id, ':uid' => $user_id]);
    } else {
        $new = $current ? 0 : 1;
        $stmt = $conn->prepare("UPDATE module_user_favorite SET is_favorite = :fav WHERE mid = :mid AND uid = :uid");
        $stmt->execute([':fav' => $new, ':mid' => $module_id, ':uid' => $user_id]);
    }
 
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
 
$currentTab = $_GET['tab'] ?? 'all';
 

 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $mid         = $_POST['module_id'];
    $uid         = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
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
 
if ($currentTab == "all") {
    $mod_query = "SELECT m.*, msp.msid, u.username, u.email,
                         COALESCE(muf.is_favorite, 0) AS is_favorite
                  FROM module AS m
                  LEFT JOIN module_stage_progress AS msp ON msp.mid = m.id
                  JOIN users AS u ON m.cid = u.id
                  LEFT JOIN module_user_favorite AS muf ON m.id = muf.mid AND muf.uid = ?
                  ORDER BY m.created_at DESC";
    $stmt = $pdo->prepare($mod_query);
    $stmt->execute([$user_id]);
    $all_mods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($currentTab == "completed") {
    $mod_query = "SELECT m.*, msp.msid, u.username, u.email,
                         COALESCE(muf.is_favorite, 0) AS is_favorite
                  FROM module AS m
                  LEFT JOIN module_stage_progress AS msp ON msp.mid = m.id
                  JOIN users AS u ON m.cid = u.id
                  JOIN module_user_completion AS umc ON m.id = umc.mid
                  LEFT JOIN module_user_favorite AS muf ON m.id = muf.mid AND muf.uid = ?
                  WHERE umc.is_complete = 1 AND umc.uid = ?
                  ORDER BY m.created_at DESC";
    $stmt = $pdo->prepare($mod_query);
    $stmt->execute([$user_id, $user_id]);
    $all_mods = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($currentTab == "favorite") {
    $mod_query = "SELECT m.*, msid, u.username, u.email, muf.is_favorite
                  FROM module AS m
                  LEFT JOIN module_stage_progress AS msp ON msp.mid = m.id
                  JOIN users AS u ON m.cid = u.id
                  LEFT JOIN module_user_favorite AS muf ON m.id = muf.mid
                  WHERE muf.is_favorite = 1 AND muf.uid = ?
                  ORDER BY m.created_at DESC";
    $stmt = $pdo->prepare($mod_query);
    $stmt->execute([$user_id]);
    $all_mods = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
 
if (empty($all_mods)) {
    if ($currentTab !== 'all') {
        header("Location: modules_display.php?tab=all");
        exit();
    }
    $noModulesMessage = "No modules available yet!";
}
 
if (isset($_POST['module_delete'])) {
    $module_delete_id  = (int)$_POST['module_delete'];
    $module_delete_sql = "DELETE FROM module WHERE id = ? AND cid = ?";
    $stmt = $conn->prepare($module_delete_sql);
    $stmt->execute([$module_delete_id, $user_id]);
    header("Location: modules_display.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Modules | HobbyBloom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/nav.css" rel="stylesheet">
</head>
<body class="module-body">
 
<div class="module_back_container">
 
    <!-- Tab bar -->
    <div class="glass-tab-container">
        <div class="glass-tabs">
            <a href="modules_display.php?tab=all"
               class="tab-btn <?= $currentTab === 'all' ? 'active' : '' ?>">
                All Modules
            </a>
            <a href="modules_display.php?tab=completed"
               class="tab-btn <?= $currentTab === 'completed' ? 'active' : '' ?> <?= $completedEmpty ? 'empty-tab' : '' ?>">
                Completed
            </a>
            <a href="modules_display.php?tab=favorite"
               class="tab-btn <?= $currentTab === 'favorite' ? 'active' : '' ?> <?= $favoriteEmpty ? 'empty-tab' : '' ?>">
                Favourites
            </a>
        </div>
    </div>
 
    <?php if (!empty($noModulesMessage)): ?>
        <p style="text-align:center; color:#5a6b3a; font-style:italic; padding: 3rem 0;">
            <?= htmlspecialchars($noModulesMessage) ?>
        </p>
    <?php else: ?>
 
    <!-- Two-column grid -->
    <div class="module_grid">
 
        <?php foreach ($all_mods as $mod):
            $c_stmt = $pdo->prepare("
                SELECT mc.*, u.username, up.profile_color, u.id AS user_actual_id
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
 
            <!-- Title + heart -->
            <div class="module_card_top">
                <h3 class="module-name"><?= htmlspecialchars($mod['name'] ?? '') ?></h3>
                <form method="POST" class="favorite-form">
                    <input type="hidden" name="toggle_fav" value="1">
                    <input type="hidden" name="module_id" value="<?= $mod['id'] ?>">
                    <button type="submit" class="heart-button">
                        <svg class="heart <?= (int)$mod['is_favorite'] === 1 ? 'active' : '' ?>"
                             viewBox="0 0 24 24" width="20" height="20">
                            <path fill="currentColor" stroke="currentColor" stroke-width="2"
                                d="M12 21s-6.716-4.534-9.428-8.062C.86 10.64 1.14 7.5 3.514 5.514
                                   5.886 3.528 9.066 4.09 12 7.09c2.934-3 6.114-3.562 8.486-1.576
                                   2.374 1.986 2.654 5.126.942 7.424C18.716 16.466 12 21 12 21z"/>
                        </svg>
                    </button>
                </form>
            </div>
 
            <!-- Inner content -->
            <div class="module_inner_card">
 
                <p class="mod_description"><?= htmlspecialchars($mod['description'] ?? '') ?></p>
 
                <!-- Pills -->
                <div class="module-pills">
                    <span class="module-pill module-pill-green"><?= htmlspecialchars($mod['exp_level'] ?? 'Beginner') ?></span>
                    <span class="module-pill module-pill-blue"><?= htmlspecialchars($mod['num_lessons'] ?? '0') ?> Lessons</span>
                </div>
 
                <!-- Author -->
                <div class="module-author">
                    <p>Created by <?= htmlspecialchars($mod['username'] ?? '') ?>
                       &middot; <?= htmlspecialchars($mod['email'] ?? '') ?></p>
                </div>
 
                <!-- Comments -->
                <div class="module-back">
                    <div class="module-comments-container">
                        <div class="comments-scroll-box" id="box-<?= $mod['id'] ?>">
                            <?php if (empty($comments)): ?>
                                <p style="font-size:11px; color:#7a8c5a; font-style:italic; text-align:center; padding: 0.5rem 0;">
                                    No comments yet.
                                </p>
                            <?php else: ?>
                                <?php foreach ($comments as $c): ?>
                                    <div class="comment-item">
                                        <a href="profile.php?id=<?= $c['user_actual_id'] ?>"
                                           class="profile-link"
                                           style="text-decoration:none; display:flex; align-items:center; flex-shrink:0;">
                                            <div class="user-color-circle"
                                                 style="background-color:<?= htmlspecialchars($c['profile_color'] ?: '#cccccc') ?>;
                                                        width:18px; height:18px; border-radius:50%; margin-right:6px; flex-shrink:0;">
                                            </div>
                                            <strong style="color:#1f5077; font-size:11px; white-space:nowrap;">
                                                @<?= htmlspecialchars($c['username']) ?>
                                            </strong>
                                        </a>
                                        <div style="flex:1; margin-left:8px; color:#2C3E1A; font-size:11px;">
                                            <?= htmlspecialchars($c['comment_text']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
 
                        <script>
                            (function() {
                                var d = document.getElementById("box-<?= $mod['id'] ?>");
                                if (d) d.scrollTop = d.scrollHeight;
                            })();
                        </script>
 
                        <form method="POST" class="comment-input-form">
                            <input type="hidden" name="module_id" value="<?= $mod['id'] ?>">
                            <input type="text" name="comment_text" class="comment-input-field"
                                   placeholder="Write a comment..." required>
                            <button type="submit" name="submit_comment" class="comment-btn">Post</button>
                        </form>
                    </div>
                </div>
 
                <!-- Begin module -->
                <form class="begin-module-flex" action="./module.php" method="POST">
                    <input type="hidden">
                    <button type="submit" class="module_display_entry_button"
                            name="module_id" value="<?= $mod['id'] ?>">
                        Begin Module
                    </button>
                </form>
 
                <!-- Owner-only: edit + delete -->
                <?php if ($mod['cid'] == $user_id): ?>
                <div class="module-owner-actions">
                    <form action="modules_display.php" method="POST">
                        <button type="submit" class="module_display_delete_button"
                                name="module_delete" value="<?= $mod['id'] ?>">
                            Delete
                        </button>
                    </form>
                    <form action="createForm.php" method="POST">
                        <button type="submit" class="module_display_edit_button"
                                name="module_edit" value="<?= $mod['id'] ?>">
                            Edit
                        </button>
                    </form>
                </div>
                <?php endif; ?>
 
            </div>
        </div>
 
        <?php endforeach; ?>
 
    </div><!-- end .module_grid -->
 
    <?php endif; ?>
 
    <!-- Create button -->
    <div class="create-button-wrapper">
        <button class="create-module-button" onclick="location.href='createForm.php'">
            + Create New Module
        </button>
    </div>
 
</div>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 
<script>
    document.querySelectorAll(".heart-button").forEach(button => {
        button.addEventListener("click", function () {
            const heart = this.querySelector(".heart");
            heart.classList.toggle("active");
        });
    });
</script>
 
<?php ob_end_flush(); ?>
</body>
</html>
