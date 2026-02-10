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
    <link href="../css/style.css" rel="stylesheet"> 
    <link href="../css/nav.css" rel="stylesheet">
</head>
<body> 

<div id="step-1" class="container mt-5">
    <div class="outter-box mb-4">
        <div class="inner-box">
            <p class="lead fw-bold">Tell us a little bit about you!</p>
            <div class="subtext text-muted"> 
                <p>This information will be stored alongside your general user data.</p>
            </div>
        </div>
    </div>

    <section class="form">
        <h2>All About You</h2>
        
        <form id="userForm" action="save_onboarding.php" method="post">
            
            <div class="mb-3">
                <label for="gender" class="form-label">What is your gender?</label>
                <input type="text" class="form-control" name="gender" id="gender" placeholder="Your Gender" minlength=2 maxlength=50 required>
            </div>
            
            <div class="mb-3">
                <label for="age" class="form-label">What is your age?</label>
                <input type="text" class="form-control" id="age" name="age" placeholder="Your age" minlength="1" maxlength="3" required>
            </div>

            <div class="mb-3">
                <label for="from" class="form-label">Where are you from?</label>
                <input type="text" class="form-control" id="from" name="from" placeholder="Hometown" minlength="2" maxlength="50" required>
            </div>

            <div class="mb-3">
                <label for="bio" class="form-label">Fun Fact / Bio</label>
                <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Tell us a little about yourself..." maxlength="500"></textarea>
            </div>

            <div class="mt-4 text-center">
                <button type="button" class="btn btn-primary btn-lg" onclick="goToStep2()">Next</button>
            </div>
            
            <input type="hidden" name="selected_hobbies" id="selected_hobbies_input">
        </form>
    </section>
</div>

<div id="step-2" class="container mt-5 d-none-custom">
    <h1 class="mb-4">Profile Curation</h1>
    
    <div class="info-box">
        <h3>Please select some hobbies that interest you!</h3>
        <p>These answers will be used to curate some recommended modules and circles.</p>
    </div>

    <div class="hobby-grid">
        <div class="hobby-btn" onclick="toggleHobby(this)">Cooking</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Knitting</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Lego</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Sewing</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Painting</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Hiking</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Reading</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Gardening</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Baking</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Meditation</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Music</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Movies</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Gaming</div>
        <div class="hobby-btn" onclick="toggleHobby(this)">Yoga</div>
    </div>

    <div class="text-center pb-5">
        <button class="next-arrow-btn" onclick="submitFullForm()">
            Next &rarr;
        </button>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>

<script>
    function goToStep2() {
        const gender = document.getElementById('gender').value;
        const age = document.getElementById('age').value;
        const from = document.getElementById('from').value;
        
        if(gender === "" || age === "" || from === "") {
            alert("Please fill out all required fields.");
            return;
        }

        document.getElementById('step-1').classList.add('d-none-custom');
        document.getElementById('step-2').classList.remove('d-none-custom');
    }

    function toggleHobby(element) {
        element.classList.toggle('selected');
    }

    function submitFullForm() {
        const selectedButtons = document.querySelectorAll('.hobby-btn.selected');
        let hobbies = [];
        selectedButtons.forEach(btn => {
            hobbies.push(btn.innerText);
        });

        document.getElementById('selected_hobbies_input').value = hobbies.join(', ');
        document.getElementById('userForm').submit();
    }
</script>

</body>
</html>