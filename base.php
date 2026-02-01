<?php
session_start();
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
                <span class="nav-user">
                    <?= htmlspecialchars($_SESSION['user']['name']) ?>
                </span>
                <a class="nav-link" href="logout.php">Logout</a>
            <?php endif; ?>


        </div>

        <nav>
        <div class="branding">
            <!-- Logomaker: https://www.squarespace.com/ -->
            <img class="logo" src="#" alt="logo" />
            <p class="menu-button">
                <span class="material-symbols-outlined">menu</span>
            </p>
        </div>

        <div class="menu">
            <div class="menu-box">

                <a class="menu-item" href="dashboard.php">Dashboard</a>
                <a class="menu-item" href="activity.php">Activity</a>
                <a class="menu-item" href="module.php">Modules</a>
                <a class="menu-item" href="circles.php">Circles</a>
                <a class="menu-item" href="chat.php">Chat</a>
                <a class="menu-item" href="account.php">Account</a>
            </div>

        </div>
    </nav>
</header>



<footer class="footer mt-auto py-3">
    <div class="footer-container">
        <a class="footer-link" href="index.php">Getting Started</a>
        <a class="footer-link" href="team.php">Meet the Team</a>
        <a class="footer-link" href="project.php">About the Project</a>

    </div>

</footer>

</body>
</html>