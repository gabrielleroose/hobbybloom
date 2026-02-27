<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1); //debugging/error messages
error_reporting(E_ALL);

// session_start(); // NOTE: session_start(); allows access to $_SESSION variable, which can store data persistently across pages.
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../config/db.php'; //necessary to connect to db.

include 'base.php';

$googleId = $_SESSION['google_id'] ?? null;

if (!$googleId) {                   //checking if google id present, sending back to index.php if not.
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


$mod_id_list = [];

    $all_module_ids = "SELECT * from module";
    $stmt = $pdo->prepare($all_module_ids);
    $stmt -> execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mod_id_list[] = $row['id'];
    }
    



    $all_mods = [];
    foreach ($mod_id_list as $id) {
    
        $fetch_mod_info = "SELECT m.id, m.cid, m.name, m.description, m.rating, m.exp_level, m.num_lessons, msp.msid FROM module as m
        LEFT JOIN module_stage_progress AS msp ON msp.mid = m.id
        WHERE m.id = ?";

        $stmt = $pdo->prepare($fetch_mod_info);
        $stmt->execute([$id]);
        $module = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($module !== false) {
            $all_mods[$id] = $module; //checking to make sure module exists before adding it to $all_mods
            }
    }

    if (isset($_POST['module_delete'])) {
        $delete_sql = "DELETE FROM module WHERE id = ? ";
        $stmt = $conn->prepare($delete_sql);
        $stmt->execute([$_POST['module_delete']]);
    }



?>



<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beginner Cooking</title>
<link href="../css/style.css" rel="stylesheet">
<link href="../css/nav.css" rel="stylesheet">
</head>
    <div class="module_back_container">
        <?php foreach ($all_mods as $mod): ?>
            <div class="module_outter_card">
                <div class="module_inner_card">
                    <div class="module_header">
                        <div class="mod_name"><h3><?= htmlspecialchars($mod['name'] ?? '')?></h3></div> <!-- ?? '' checks if null -->
                        <div class="rating"><?= str_repeat('⭐', (int)($mod['rating'] ?? 0)) ?></div>
                    </div>
                    <div class="mod_description"><p><?= htmlspecialchars($mod['description'] ?? '')?></p></div>
                    <div class="exp_level"><p><?= htmlspecialchars($mod['exp_level'] ?? '')?></p></div>
                    <div class="num_lessons"><p>Number of lessons:<?= htmlspecialchars($mod['num_lessons'] ?? '')?></p></div>
                    <form  action="./module.php" method="POST">
                        <input type="hidden">
                       <button type="submit" class="module_display_entry_button" name="module_id" value="<?= $mod['id'] ?>">Begin Module</button>
                    </form>

                    <form action="modules_display.php" method="POST">

                        <?php if ($mod['cid'] == $user_id): ?> <!-- DELETE MODULE BUTTON. CHECKS IF CID = USER_ID. --->
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
            <button class="create-module-button">
                <a href="createForm.php">Create New Module</a>
            </button>

        </div>
    </div>
    
</body>    

</html>



<?php include __DIR__ . '/../includes/footer.php'; ?>