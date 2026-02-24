<?php
require_once 'db.php';

$module_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM modules
    WHERE id = :id
");
$stmt->execute([":id" => $module_id]);
$module = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT *
    FROM module_videos
    WHERE module_id = :id
    ORDER BY lesson_number
");
$stmt->execute([":id" => $module_id]);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beginner Cooking</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>

<body class="module-body">

    <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1); //debugging/error messages
    error_reporting(E_ALL);

    session_start(); // NOTE: session_start(); allows access to $_SESSION variable, which can store data persistantly across pages.
    require_once __DIR__ . '/../vendor/autoload.php';

    require_once __DIR__ . '/../config/db.php'; //necessary to connect to db.

    require_once __DIR__ . '/../config/twig.php'; //necessary to load twig
    include 'base.php';

    $googleId = $_SESSION['google_id'] ?? null;

    if (!$googleId) {                   //checking if google id present, sending back to index.php if not.
        header('Location: index.php');
        exit;
    }


    try {
        $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        die("database connection failed: " . $e->getMessage());
    }




    //SELECTS USER ID WHERE GOOGLEID FROM SESSION MATCHES GOOGLE_ID IN USERS TABLE
    $user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
    $stmt = $pdo->prepare($user_id_sql);
    $stmt->execute(['gid' => $googleId]);
    $userid_row = $stmt->fetch(PDO::FETCH_ASSOC);
    $userid = $userid_row ? $userid_row['id'] : null; //NOTE: $userid may be used for all queries hereon out.


    //HELPS DISPLAY LOGGED-IN USER'S CREATED MODULES. SELECTS MODULE ID WHERE CURRENT USER ID IS EQUAL TO THAT OF THE CREATOR OF THE MODULE. HELPS DISPLAY USER-CREATED MODULES.
    $user_mid_sql = "SELECT id FROM module WHERE :userid = cid";
    $stmt = $pdo->prepare($user_mid_sql);
    $stmt->execute(['userid' => $userid]);
    $user_created_modules_id_row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $user_created_modules_id = $user_created_modules_id_row ? array_column($user_created_modules_id_row, 'id') : []; //array_column function structure in this context: (array gained from query, column name from SQL table column from which you're fetching : failsafe answer)
    //output for above variable is an array of all logged in user's created module id's; each module id is one that was created by the user

    // $current_module_stage = "SELECT id FROM module_stage WHERE mid = ""; NOTE: WE NEED TO CREATE A PAGE WHICH DISPLAYS ALL CREATED MODULES


    //!!!NOTE: WE NEED TO ADD A MODULES_DISPLAY.TWIG FILE SO THAT WE CAN PROPERLY PASS THROUGH THE SELECTED MID!!!

    ?>

    <!-- general structure of module.twig content for generated content w/ example (wait for Wednesday meeting for normalized css conversation?).
    may also have to create a couple more div containers, or change tags, in order to accurately style in accordance w/ wireframe


<div class = classfor-maincontainer>

    <div class="current-stage">

        content, according to module wireframe: 
        stage number (1, 2, or 3)
        
        video                 brief description

    </div>

    <div class="classfor-next-steps">
        videos of next steps side-by-side, maybe just present single next step and its description?
    </div>

    <div class="classfor-creator-profile">
        "creator name"          view profile button (settings implementation?)

        pfp and username
    </div>


    <div class="classfor-questions>
    display for questions here. discuss on wednesday whether or not we want this to be called questions or comments.
    </div>

</div>
    -->




    <div class="page-container">

        <div class="module-page-header">
            <h1><?= htmlspecialchars($module['name']) ?></h1>
            <button class="header-search-btn">Search</button>
        </div>

        <div class="main-step-card">
            <div class="step-title">
                Step <?= $current['lesson_number'] ?>
            </div>
            <?php if (!empty($videos)): ?>
                <iframe
                    width="560"
                    height="315"
                    src="<?= htmlspecialchars($videos[0]['video_url']) ?>"
                    allowfullscreen>
                </iframe>
            <?php endif; ?>


            <?php foreach (array_slice($videos, 1) as $video): ?>
                <div class="video-thumbnail small">
                    Lesson <?= $video['lesson_number'] ?>
                </div>
            <?php endforeach; ?>


            <h3 class="section-title">Get to Know “Creator Name”!</h3>
            <div class="info-card">
                <div class="card-row-between">
                    <h4>“Creator Name”</h4>
                    <button class="light-btn">view profile</button>
                </div>
                <div class="creator-profile">
                    <div class="creator-avatar"></div>
                    <div class="creator-details">
                        <span class="username">Username</span>
                        <span class="description">Description</span>
                    </div>
                </div>
            </div>

            <h3 class="section-title">Questions:</h3>
            <div class="info-card">
                <div class="card-row-between">
                    <h4>Title</h4>
                    <button class="light-btn">submit</button>
                </div>
                <p class="question-content">question contents</p>
            </div>

        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>