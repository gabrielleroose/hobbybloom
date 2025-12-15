<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link href="./css/style.css" rel="stylesheet"> 
</head>
<body>

    <?php include 'base.php'; ?>

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
                    <div class="chat-preview">You: How is sewing going?</div>
                </div>
                <div class="chat-time">9:41 AM</div>
            </div>

            <div class="chat-item">
                <div class="chat-avatar"></div>
                <div class="chat-info">
                    <div class="chat-name">Kieron Dotson</div>
                    <div class="chat-preview">My sweater is finally done!</div>
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
                    <div class="chat-preview">Are we still on for our gardening session?</div>
                </div>
                <div class="chat-time">Mon</div>
            </div>

        </div>
    </div>

</body>
</html>