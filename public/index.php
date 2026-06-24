<?php 
require_once 'base.php'; 
 
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
 
<!DOCTYPE HTML>
<html>
<head> 
    <title>Getting Started | HobbyBloom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet"> 
    <link href="../css/nav.css" rel="stylesheet">
    <style>
        .d-none-custom { display: none !important; }
    </style>
</head>
<body class="index-body"> 
 
<div class="index-main-container">
 
    <form id="userForm" action="save_onboarding.php" method="post">
 
        <div id="step-1-wrapper">
 
            <div id="step-1" class="container mt-5">
                <div class="index-inner-box">
                    <div class="ob-step-indicator">
                        <div class="ob-step-dot active"></div>
                        <div class="ob-step-dot"></div>
                    </div>
                    <div class="ob-eyebrow">Step 1 of 2</div>
                    <h1>Tell us about you</h1>
                    <div>
                        <p>This information will be stored alongside your general user data.</p>
                    </div>
                </div>
            </div>
 
            <section class="index-form">
                <h2 class="index-form-title">Build Your Profile</h2>
 
                <div class="index-name-info">
                    <div class="index-form-input">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" id="first_name" placeholder="Jane" minlength="2" maxlength="50" required>
                    </div>
                    <div class="index-form-input">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" id="last_name" placeholder="Smith" minlength="2" maxlength="50" required>
                    </div>
                </div>
 
                <div class="index-form-input">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" id="username" placeholder="e.g. HobbyMaster201" minlength="2" maxlength="50" required>
                </div>
 
                <div class="index-name-info">
                    <div class="index-form-input">
                        <label for="age" class="form-label">Age</label>
                        <input type="number" class="form-control" id="age" name="age" placeholder="25" min="13" max="100" required>
                    </div>
                    <div class="index-form-input">
                        <label for="from" class="form-label">Location</label>
                        <input type="text" class="form-control" id="from" name="from" placeholder="City, State" minlength="2" maxlength="50" required>
                    </div>
                </div>
 
                <div class="index-form-input">
                    <label for="bio" class="form-label">A hobby you'd love to try</label>
                    <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Tell us about a hobby you want to learn..." maxlength="500"></textarea>
                </div>
 
                <button type="button" class="index-next-button" onclick="goToStep2()">Next →</button>
            </section>
 
        </div>
 
        <div id="step-2" class="container mt-5 d-none-custom">
 
            <div class="index-inner-box">
                <div class="ob-step-indicator">
                    <div class="ob-step-dot"></div>
                    <div class="ob-step-dot active"></div>
                </div>
                <div class="ob-eyebrow">Step 2 of 2</div>
                <h1>Pick your hobbies</h1>
                <div>
                    <p>We'll use these to recommend modules and circles just for you.</p>
                </div>
            </div>
 
            <section class="index-form">
                <button type="button" class="ob-back-btn" onclick="goToStep1()">← Back</button>
 
                <div class="info-box">
                    <h3>Select as many as you like!</h3>
                    <p>You can always update your interests later from your account settings.</p>
                </div>
 
                <div class="hobby-grid">
                    <div class="hobby-btn" onclick="toggleHobby(this)">🍳 Cooking</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🧶 Knitting</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🧱 Lego</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🧵 Sewing</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🎨 Painting</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🥾 Hiking</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">📚 Reading</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🪴 Gardening</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🥖 Baking</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🧘 Meditation</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🎵 Music</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🎬 Movies</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🎮 Gaming</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🧘 Yoga</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🌍 Travel</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">👽 Star Wars</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">🏴‍☠️ One Piece</div>
                    <div class="hobby-btn" onclick="toggleHobby(this)">✍️ Writing</div>
                </div>
 
                <button type="button" class="finish-arrow-btn" onclick="submitFullForm()">Finish →</button>
            </section>
 
        </div>
 
        <input type="hidden" name="selected_hobbies" id="selected_hobbies_input">
 
    </form>
 
</div>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 
<script>
    function goToStep2() {
        const first = document.getElementById('first_name').value;
        const last  = document.getElementById('last_name').value;
        const uname = document.getElementById('username').value;
        const age   = document.getElementById('age').value;
 
        if (first === '' || last === '' || uname === '' || age === '') {
            alert('Please fill out your name and details to continue.');
            return;
        }
 
        document.getElementById('step-1-wrapper').classList.add('d-none-custom');
        document.getElementById('step-2').classList.remove('d-none-custom');
        window.scrollTo(0, 0);
    }
 
    function goToStep1() {
        document.getElementById('step-2').classList.add('d-none-custom');
        document.getElementById('step-1-wrapper').classList.remove('d-none-custom');
        window.scrollTo(0, 0);
    }
 
    function toggleHobby(element) {
        element.classList.toggle('selected');
    }
 
    function submitFullForm() {
        const selectedButtons = document.querySelectorAll('.hobby-btn.selected');
 
        if (selectedButtons.length === 0) {
            alert('Please select at least one hobby!');
            return;
        }
 
        let hobbies = [];
        selectedButtons.forEach(btn => hobbies.push(btn.innerText));
 
        document.getElementById('selected_hobbies_input').value = hobbies.join(', ');
        document.getElementById('userForm').submit();
    }
</script>
 
</body>
</html>