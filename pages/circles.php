<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Circles</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>

<body>

    <?php include 'base.php'; ?>

    <div class="page-container">

        <div class="search-row">
            <input type="text" class="search-bar" placeholder="Search">
            <a href="chat.php" class="chat-icon-link">💬</a>
        </div>

        <h2>Your Circles</h2>
        <div class="horizontal-scroll">
            <div class="story-circle">
                <div class="circle-img" style="background-color: #a8d0e6;"></div>
            </div>
            <div class="story-circle">
                <div class="circle-img" style="background-color: #a8d0e6;"></div>
            </div>
            <div class="story-circle">
                <div class="circle-img" style="background-color: #a8d0e6;"></div>
            </div>
            <div class="story-circle">
                <div class="circle-img" style="background-color: #a8d0e6;"></div>
            </div>
        </div>

        <h2>Suggested For You</h2>
        <div class="horizontal-scroll">
            <div class="suggested-item"></div>
            <div class="suggested-item"></div>
            <div class="suggested-item"></div>
        </div>

        <h2>Your Feed</h2>
        <div class="feed-card">
            <div class="feed-header">
                <div class="feed-avatar"></div> <span class="feed-username">User2347</span>
            </div>
            <div class="feed-image-placeholder">
            </div>
        </div>

    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>