<?php include 'base.php'; ?>

<?php if (!isset($_SESSION['user'])): ?>
<script>
window.handleCredentialResponse = function(response) {
    console.log("Google credential response:", response);

    fetch("google_login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ token: response.credential })
    })
    .then(res => {
        if (res.ok) window.location.reload();
        else alert("Google login failed on server.");
    })
    .catch(err => console.error("Fetch error:", err));
};
</script>

<div id="g_id_onload"
     data-client_id="1011869688630-kl05vvf13cg6u6d1tlo9rnj0l4kj7rvn.apps.googleusercontent.com"
     data-callback="handleCredentialResponse"
     data-auto_prompt="false">
</div>

<div class="g_id_signin"
     data-type="standard"
     data-size="large"
     data-theme="outline"
     data-text="sign_in_with"
     data-shape="rectangular"
     data-logo_alignment="left">
</div>
<?php endif; ?>

<!DOCTYPE HTML>
<html>
<head> 
    <link href="./css/style.css" rel="stylesheet"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        .step-container {
            transition: opacity 0.3s ease;
        }
        
        .d-none-custom {
            display: none !important;
        }

        .info-box {
            background-color: #2c6ca3;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .info-box h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        .info-box p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }

        .hobby-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 40px;
        }

        .hobby-btn {
            background-color: #b0d4e9;
            border: 1px solid #7eaec9;
            border-radius: 8px;
            padding: 15px 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
            color: #333;
            font-weight: 500;
        }

        .hobby-btn:hover {
            background-color: #9bc5dd;
        }

        .hobby-btn.selected {
            background-color: #2c6ca3;
            color: white;
            border-color: #1a4f7a;
        }

        .next-arrow-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
            display: block;
            margin: 0 auto;
            padding: 10px 30px;
            background-color: #e0e0e0;
            border-radius: 50px;
        }
        
        .next-arrow-btn:hover {
            background-color: #ccc;
        }
    </style>
</head>
<body> 

<div id="step-1" class="container mt-5">
    <div class="outter-box mb-4">
        <div class="inner-box">
            <p class="lead fw-bold">Tell us a little bit about you!</p>
            <div class="subtext text-muted"> 
                <p>This information will be stored alongside your general user data, but not sold.</p>
            </div>
        </div>
    </div>

    <section class="form">
        <h2>All About You</h2>
        
        <form id="userForm" action="https://formspree.io/f/xeoyddao" method="post">
            
            <div class="mb-3">
                <label for="gender" class="form-label">What is your gender?</label>
                <input type="text" class="form-control" name="gender" id="gender" placeholder="Your Gender" minlength=2 maxlength=50 required>
            </div>
            
            <div class="mb-3">
                <label for="age" class="form-label">What is your age?</label>
                <input type="text" class="form-control" id="age" name="age" placeholder="Your age" minlength="2" maxlength="50" required>
            </div>

            <div class="mb-3">
                <label for="from" class="form-label">Where are you from?</label>
                <input type="text" class="form-control" id="from" name="from" placeholder="Hometown" minlength="2" maxlength="50" required>
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

<script>
    function goToStep2() {
        const gender = document.getElementById('gender').value;
        if(gender === "") {
            alert("Please fill out the form first.");
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
        
        window.location.href = "dashboard.php";
    }
</script>

</body>
</html>