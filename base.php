<?php
session_start();
?>

 <link href="./css/style.css" rel="stylesheet">
 <head>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

    <header>
        <nav>
            <div class = "navbar">
                <!-- GOOGLE AUTH UI -->
<?php if (isset($_SESSION['user'])): ?>
    <span class="nav-user">
        <?= htmlspecialchars($_SESSION['user']['name']) ?>
    </span>
    <a class="nav-link" href="logout.php">Logout</a>
<?php endif; ?>


            </div>
        </nav>
    </header>

 <link href="./css/style.css" rel="stylesheet">
 <link href="./css/nav.css" rel="stylesheet">

    <header>
        <nav>
            <div class = "navbar">

                <a class="nav-link" href="index.php">Getting Started</a>
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="activity.php">Activity</a>
                <a class="nav-link" href="module.php">Modules</a>
                <a class="nav-link" href="circles.php">Circles</a>
                <a class="nav-link" href="chat.php">Chat</a>
                <a class="nav-link" href="team.php">Meet the Team</a>
                <a class="nav-link" href="project.php">About the Project</a>

            </div>
        </nav>

        <nav>
            <div class="branding">
                <!-- Logomaker: https://www.squarespace.com/ -->
                <img class="logo" src="images/Gabrielle-Roose-logo.png" alt="logo" />
                <p class="menu-button">
                <span class="material-symbols-outlined">menu</span>
                </p>
            </div>

            <div class = "menu">
                <div class="menu-box">

                    <a class="menu-item" href="dashboard.php">Dashboard</a>
                    <a class="menu-item" href="activity.php">Activity</a>
                    <a class="menu-item" href="module.php">Modules</a>
                    <a class="menu-item" href="circles.php">Circles</a>
                    <a class="menu-item" href="chat.php">Chat</a>
                </div>

            </div>
        </nav>



    </header>

