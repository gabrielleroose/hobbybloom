<?php
require_once 'db.php';

$reqCount = 0;
if (isset($_SESSION['user']['id'])) {
    $stmtCount = $conn->prepare("SELECT COUNT(*) FROM user_follows WHERE followed_id = ? AND status = 'pending'");
    $stmtCount->execute([$_SESSION['user']['id']]);
    $reqCount = (int)$stmtCount->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/nav.css">    
    
    <style>
        #toast { visibility: hidden; min-width: 250px; background-color: #1f5077; color: #fff; text-align: center; border-radius: 10px; padding: 16px; position: fixed; z-index: 1000; left: 50%; bottom: 30px; transform: translateX(-50%); box-shadow: 0 4px 15px rgba(0,0,0,0.2); font-weight: bold; }
        #toast.show { visibility: visible; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
        @keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
        @keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }

        .notification-badge {
            background-color: #ff4d4d;
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 50%;
            position: relative;
            top: -10px;
            right: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
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
                    <div class="nav-search-wrap">
                        <span class="nav-search-icon">🔍</span>
                        <input type="text" class="nav-search-input" id="navUserSearch" placeholder="Search users…" autocomplete="off">
                        <div class="nav-search-dropdown" id="navSearchDropdown"></div>
                    </div>
                    <a class="menu-item wide-view" href="account.php">
                        Account
                        <?php if ($reqCount > 0): ?>
                            <span class="notification-badge"><?= $reqCount ?></span>
                        <?php endif; ?>
                    </a>
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
