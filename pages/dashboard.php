<?php 
require_once 'base.php'; 

$myHobbies = [];
if (isset($_SESSION['user']['id'])) {
    $stmt = $conn->prepare("SELECT hobbies FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $res = $stmt->fetch();
    
    if ($res && $res['hobbies']) {
        $myHobbies = explode(', ', $res['hobbies']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="../css/style.css" rel="stylesheet">
</head>

<body>
    <div class="dash-outter">
        <div class="dash-inner"><p>My Dashboard</p></div>
        <div class="dash-inner2"><p>Streak - 4 Days</p></div>
    </div>
    
    <div class="dash-heading"><p>Jump Back In!</p></div>
    <div class="dash-item"></div>
    <div><p></p></div>
    <div class="dash-item"></div>

    <h2>Your Circles</h2>
    <div class="horizontal-scroll">
        
        <?php if (in_array("Cooking", $myHobbies)): ?>
        <div class="story-circle">
            <div class="circle-img" style="background-color: #ff9999;"></div>
            <p>Cooking</p>
        </div>
        <?php endif; ?>

        <?php if (in_array("Lego", $myHobbies)): ?>
        <div class="story-circle">
            <div class="circle-img" style="background-color: #ffd700;"></div>
            <p>Lego</p>
        </div>
        <?php endif; ?>
        
        <?php if (in_array("Reading", $myHobbies)): ?>
        <div class="story-circle">
            <div class="circle-img" style="background-color: #a8d0e6;"></div>
            <p>Reading</p>
        </div>
        <?php endif; ?>

        <div class="story-circle">
            <div class="circle-img" style="background-color: #cccccc;"></div>
            <p>General</p>
        </div>
        
    </div>
</body>
</html>