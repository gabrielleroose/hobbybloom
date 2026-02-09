<?php
session_start();

$host = "db.luddy.indiana.edu";
$user = "i494f25_team18";
$password = "berms2227penes";
$database = "i494f25_team18";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$database;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    // In production, log this to a file instead of showing the user
    // error_log($e->getMessage()); 
}
?>

<link href="../css/style.css" rel="stylesheet">
<head>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<header>
    <nav>
        <div class="navbar">
            <a class="nav-link" href="index.php">Getting Started</a>
            <a class="nav-link" href="dashboard.php">Dashboard</a>
            <a class="nav-link" href="activity.php">Activity</a>
            <a class="nav-link" href="module.php">Modules</a>
            <a class="nav-link" href="circles.php">Circles</a>
            <a class="nav-link" href="chat.php">Chat</a>
            <a class="nav-link" href="team.php">Meet the Team</a>
            <a class="nav-link" href="project.php">About</a>

            <?php if (isset($_SESSION['user'])): ?>
                <span class="nav-user" style="color: white; margin-left: auto; padding-right: 10px;">
                    <?= htmlspecialchars($_SESSION['user']['name']) ?>
                </span>
                <a class="nav-link" href="logout.php">Logout</a>
            <?php endif; ?>
        </div>
    </nav>
</header>