<?php
session_start();
?>

 <link href="./css/style.css" rel="stylesheet">
 <script src="https://accounts.google.com/gsi/client" async defer></script>
<!-- this is the google code below  -->
<script src="https://accounts.google.com/gsi/client" async defer></script>

    <header>
        <nav>
            <div class = "navbar">

                <button class="nav-button" id="tbutton">Time</button>

                <a class="nav-link" href="index.php">Home</a>

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
<script src="./js/site.js"></script>

 <link href="./css/style.css" rel="stylesheet">

    <header>
        <nav>
            <div class = "navbar">

                <a class="nav-link" href="index.html">Home</a>
                <a class="nav-link" href="team.php">Meet the Team</a>
                <a class="nav-link" href="project.php">About the Project</a>

            </div>
        </nav>
    </header>
<script src="./js/site.js"></script>
