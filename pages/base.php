<?php
require_once 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/nav.css">    
    <style>
        #toast { visibility: hidden; min-width: 250px; background-color: #1f5077; color: #fff; text-align: center; border-radius: 10px; padding: 16px; position: fixed; z-index: 1000; left: 50%; bottom: 30px; transform: translateX(-50%); box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-weight: bold; }
        #toast.show { visibility: visible; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
        @keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
        @keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }
    </style>
</head>

<body>
<header>
    <div class="navbar"></div>
    <nav>
        <div class="branding">
            <div class="branding-spacer"></div>
                <p>Hobby<span class="bloom">Bloom</span></p>

                <div class="branding-info">
                    <a class="menu-item wide-view" href="account.php">Account</a>
                    <a class="menu-item wide-view" href="logout.php">Logout</a>

                </div>
        
        </div>
        <div class="menu">
            <div class="menu-box">
                <a class="menu-item" href="dashboard.php">Dashboard</a>
                <a class="menu-item" href="modules_display.php">Modules</a>
                <a class="menu-item" href="circles.php">Circles</a>
                <a class="menu-item" href="activity.php">Activity</a>
                <a class="menu-item" href="calendar.php">Calendar</a>
                <a class="menu-item wide-view" href="share.php">Share</a>
    
            </div>
        </div>
    </nav>
</header>

<div id="toast">Notification Message</div>

<script>
    function showToast(message) {
        var x = document.getElementById("toast");
        x.innerHTML = message;
        x.className = "show";
        setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
    }
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        const type = urlParams.get('success');
        if (type === '1') showToast("Changes saved successfully! ✅");
        if (type === 'joined') showToast("Event added to your calendar! 📅");
        if (type === 'followed') showToast("New connection added! 🤝");
    }
</script>
</body>
</html>