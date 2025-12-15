<?php
session_start();
?>

 <link href="./css/style.css" rel="stylesheet">
 <head>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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

    <header>
        <nav>
            <div class = "navbar">

                <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link" href="team.php">Meet the Team</a>
                <a class="nav-link" href="project.php">About the Project</a>
                <a class="nav-link" href="module.php">Modules</a>
                <a class="nav-link" href="activity.php">Activity</a>
                <a class="nav-link" href="circles.php">Circles</a>
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="chat.php">Chat</a>

            </div>
        </nav>
    </header>

