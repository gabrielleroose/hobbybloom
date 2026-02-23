<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1); //debugging/error messages
error_reporting(E_ALL);



session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/twig.php';
require_once __DIR__ . '/base.php';


//get user's Google ID from session

$googleId = $_SESSION['google_id'] ?? null;

//check if they're logged in. if not, return to index.php
if (!$googleId) {
    header('Location: index.php');
    exit;
}


// use of PDO's for security, mysqli extensions largely outdated.

try {
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    } catch (PDOException $e) {
        die("database connection failed: " . $e->getMessage());
    }

//basic PHP SQL syntax. remember, use of PDO's for security. 


//1. prepare sql statement. 
// if using WHERE or similar condition, remember to use syntax of "CONSTRAINT = :varname", 
// where varname doesn't have to align with anything so long as it's repeated in the second step ('varname' = $PHP var.)


$user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
$stmt = $pdo->prepare($user_id_sql);
$stmt->execute(['gid' => $googleId]);
$userid_row = $stmt->fetch(PDO::FETCH_ASSOC);
$userid = $userid_row ? $userid_row['id'] : null;

$mid_sql = "SELECT mid FROM log WHERE uid = :userid AND complete = 1";
$stmt = $pdo->prepare($mid_sql);
$stmt->execute(['userid' => $userid]);

$mid_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mid_ids = $mid_rows ? array_column($mid_rows, 'mid') : [];

$modules = []; // array to store all modules

foreach ($mid_ids as $mid_id) {
    $sql = "SELECT name, description FROM module WHERE id = :mid";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['mid' => $mid_id]);

    $module = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($module) {
        $modules[] = [
            'name' => $module['name'],
            'description' => $module['description']
        ];
    }
}



    echo $twig->render('share.twig', [
    'modules' => $modules
]);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share</title>

    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>

<body>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

