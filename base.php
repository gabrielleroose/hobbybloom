<?php
session_start();
?>

 <link href="./css/style.css" rel="stylesheet">
 <script src="https://accounts.google.com/gsi/client" async defer></script>
    <header>
        <nav>
            <div class = "navbar">

                <button class="nav-button" id="tbutton">Time</button>

                <a class="nav-link" href="index.php">Home</a>
// below is the google login code, not sure if it works // 
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
