<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Activity</title>
    <link href="./css/style.css" rel="stylesheet">
</head>
<body>

    <?php include 'base.php'; ?>

    <div class="page-container">

        <h1>My Activity</h1>

        <div class="activity-card">
            <h2>Beginner Cooking</h2>
            <div class="progress-row">
                <span class="percentage-text">25%</span>
                <div class="progress-bar-track">
                    <div class="progress-bar-fill" style="width: 25%;"></div>
                </div>
            </div>
        </div>

        <h3>Days Active:</h3>
        <div class="calendar-box">
            <span class="placeholder-label">Calendar</span>
        </div>

        <h3>Completed Modules:</h3>
        <div class="completed-list">
            
            <div class="completed-item">
                <div class="video-thumbnail big">
                    <div class="play-icon">▶</div>
                </div>
                <div class="completed-details">
                    <div class="module-title">Sewing</div>
                    <div class="module-meta">
                        Creator Name<br>
                        Description<br>
                        Date
                    </div>
                </div>
            </div>

             <div class="completed-item">
                <div class="video-thumbnail big">
                    <div class="play-icon">▶</div>
                </div>
                <div class="completed-details">
                    <div class="module-title">Photography</div>
                    <div class="module-meta">
                        Creator Name<br>
                        Description<br>
                        Date
                    </div>
                </div>
            </div>

        </div>

    </div>
    <?php include 'includes/footer.php'; ?>

</body>
</html>