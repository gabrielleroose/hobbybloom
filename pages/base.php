<?php
session_start();
require_once 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Login Script -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <!-- CSS styles -->
    
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/nav.css">    
   
</head>


<body>
    

<header>
        <div class="navbar">
            <!-- GOOGLE AUTH UI -->
            <?php if (isset($_SESSION['user'])): ?>
                <span class="nav-user" style="color: white; margin-left: auto; padding-right: 10px;">
                    <?= htmlspecialchars($_SESSION['user']['name']) ?>
                </span>
                <!-- <a class="nav-link" href="logout.php">Logout</a> -->
            <?php endif; ?>


        </div>

        <nav>
        <div class="branding">
            <!-- Logomaker: https://www.squarespace.com/ -->
            <!-- <img class="logo" src="#" alt="logo" />
            <button class="menu-button" type="button">
            <span class="material-symbols-outlined">menu</span> -->
            <p>Hobby<span class="bloom">Bloom</span></p>
            <!-- </button> -->
        </div>

        <div class="menu">
            <div class="menu-box">

                <a class="menu-item" href="dashboard.php">Dashboard</a>
                <a class="menu-item" href="activity.php">Activity</a>
                <a class="menu-item" href="modules_display.php">Modules</a>
                <a class="menu-item" href="circles.php">Circles</a>
                <a class="menu-item" href="chat.php">Chat</a>
                <a class="menu-item wide-view" href="account.php">Account</a>
                <!-- <a class="menu-item" href="calendar.php">Calendar</a> -->
                <a class="menu-item wide-view" href="share.php">Share</a>
                <a class="menu-item wide-view" href="logout.php">Logout</a>


            </div>

        </div>
    </nav>
</header>


</body>
</html>