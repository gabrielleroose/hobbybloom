<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="page-container">

        <div class="page-header">
            <h1>Messages</h1>
            <span>✏️</span>
        </div>

        <input type="text" class="search-bar" placeholder="Search">

        <div class="horizontal-scroll">
            <div class="story-circle">
                <div class="circle-img"></div>
                <div class="circle-name">You</div>
            </div>
            
            <div class="story-circle">
                <div class="circle-img"></div>
                <div class="circle-name">Martin</div>
            </div>

            <div class="story-circle">
                <div class="circle-img"></div>
                <div class="circle-name">Kieron</div>
            </div>
            
            <div class="story-circle">
                <div class="circle-img"></div>
                <div class="circle-name">Jamie</div>
            </div>
            
             <div class="story-circle">
                <div class="circle-img"></div>
                <div class="circle-name">Karen</div>
            </div>
        </div>

        <div class="chat-list">
            
            <div class="chat-item">
                <div class="chat-avatar"></div>
                <div class="chat-info">
                    <div class="chat-name">Martin Randolph</div>
                    <div class="chat-preview">You: What time is the meeting?</div>
                </div>
                <div class="chat-time">9:41 AM</div>
            </div>

            <div class="chat-item">
                <div class="chat-avatar"></div>
                <div class="chat-info">
                    <div class="chat-name">Kieron Dotson</div>
                    <div class="chat-preview">The project files are ready.</div>
                </div>
                <div class="chat-time">Yesterday</div>
            </div>

            <div class="chat-item">
                <div class="chat-avatar"></div>
                <div class="chat-info">
                    <div class="chat-name">Jamie P.</div>
                    <div class="chat-preview">Thanks again!</div>
                </div>
                <div class="chat-time">Tue</div>
            </div>
            
             <div class="chat-item">
                <div class="chat-avatar"></div>
                <div class="chat-info">
                    <div class="chat-name">Karen W.</div>
                    <div class="chat-preview">Are we still on for lunch?</div>
                </div>
                <div class="chat-time">Mon</div>
            </div>

        </div>
    </div>

    <div class="next-button">
        <a href="index.php" class="nav-link">🏠</a>
        <a href="chat.php" class="nav-link">💬</a>
        <a href="profile.php" class="nav-link">👤</a>
    </div>

</body>
</html>