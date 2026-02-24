<?php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/twig.php';

// get user's google_id
$googleId = $_SESSION['google_id'] ?? null;

// check if they're logged in
if (!$googleId) {
    header('Location: index.php');
    exit;
}

try {

    $conn->beginTransaction();


    $user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
    $stmt = $conn->prepare($user_id_sql);
    $stmt->execute([':gid' => $googleId]);

    $user_id = $stmt->fetchColumn();

    if (!$user_id) {
        throw new Exception("User not found in database.");
    }

    // get mod_id
    $mod_id = $_POST['module_id'];

    $mod_stage_sql = "SELECT ms.id FROM module_stage AS ms JOIN module AS m ON ms.mid = m.id WHERE ms.mid = :mid";
    $stmt = $conn->prepare($mod_stage_sql);
    $stmt->execute(['m.id' => $mod_id]);
    
    $mod_stage_ids = $stmt->fetchAll(); //gets an array of all module stage nums where module_stage.module_id = module.id, ensuring user receives relevant info.
     
    
    foreach($mod_stage_ids as $stage_id) {
        $module_stage_questions_sql = "SELECT id FROM module_stage_questions AS msq JOIN module_stage AS ms ON msq.msid = ms.id WHERE msq.msid = ms.id";



    }


    $conn->commit();
    echo "Module created successfully!";

} catch (Exception $e) {

    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}


    
?>