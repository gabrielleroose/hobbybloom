<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beginner Cooking</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
</head>
<body>

    <?php include 'base.php'; ?>

    <div class="page-container">

        <div class="module-page-header">
            <h1>Beginner Cooking</h1>
            <button class="header-search-btn">Search</button>
        </div>

        <div class="main-step-card">
            <div class="step-title">Step 4: Prepare the Dough</div>
            <div class="step-body">
                <div class="video-thumbnail big">
                    <div class="play-icon">▶</div>
                </div>
                <p class="step-text">
                    In this lesson, you will learn how to prepare the dough for your homemade pizza!
                </p>
            </div>
        </div>

        <h3 class="section-title">Next Steps:</h3>
        <div class="horizontal-scroll">
            <div class="video-thumbnail small">
                <div class="play-icon">▶</div>
            </div>
            <div class="video-thumbnail small">
                <div class="play-icon">▶</div>
            </div>
            <div class="video-thumbnail small">
                <div class="play-icon">▶</div>
            </div>
        </div>

        <h3 class="section-title">Get to Know “Creator Name”!</h3>
        <div class="info-card">
            <div class="card-row-between">
                <h4>“Creator Name”</h4>
                <button class="light-btn">view profile</button>
            </div>
            <div class="creator-profile">
                <div class="creator-avatar"></div>
                <div class="creator-details">
                    <span class="username">Username</span>
                    <span class="description">Description</span>
                </div>
            </div>
        </div>

        <h3 class="section-title">Questions:</h3>
        <div class="info-card">
            <div class="card-row-between">
                <h4>Title</h4>
                <button class="light-btn">submit</button>
            </div>
            <p class="question-content">question contents</p>
        </div>

    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>

</body>
</html>