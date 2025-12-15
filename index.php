<?php include 'base.php'; ?>

<?php if (!isset($_SESSION['user'])): ?>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
window.handleCredentialResponse = function(response) {
    console.log("Google credential response:", response);

   fetch('/google-login.php', {
    method: 'POST',
    credentials: 'include', // <<< pnt of failure
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ token })
})
.then(res => res.json())
.then(data => {
    if (data.success) {
        window.location.reload();
    }
});

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
<head> <link href="./css/style.css" rel="stylesheet"> </head>
<body> 

<div class = "outter-box">
	<div class = "inner-box">
		<p>Tell us a little bit about you!</p>
		<div class = "subtext"> 
			<p>This information will be stored alongside your general user data, but not sold.</p>
		</div>
	</div>

</div>


<section class="form">
        <h2>Contact Me</h2>

      <form action="https://formspree.io/f/xeoyddao" method="post">

        <div class="mb-3">
            <label for="gender" class="form-label">What is your gender?</label>
            <input type="text" class="form-control" name="gender" id="gender" placeholder="Your Gender" minlength=2 maxlength=50 required>
            <div class="invalid-feedback">Name must be between 1 and 50 characters</div>
        </div>
        <div class="mb-3">
            <label for="age" class="form-label">What is your age?</label>
            <input type="text" class="form-control" id="age" name="age" placeholder="Your age" minlength="2" maxlength="50" required></input>
            <div class="invalid-feedback">Text must be less than 50 characters</div>
        </div>
     
        <div class="mb-3">
            <label for="from" class="form-label">Where are you from?</label>
            <textarea class="form-control" name="from" id="from" placeholder="Hometown" required></textarea>
        </div>

		<div class="mb-3">
            <label for="hobbies" class="form-label">Do you partake in any hobbies?</label>
            <input type="text" class="form-control" id="hobbies" name="hobbies" placeholder="Your hobbies" minlength="2" maxlength="50" required></input>
            <div class="invalid-feedback">Text must be less than 50 characters</div>
        </div>

		<div class="mb-3">
            <label for="time" class="form-label">How much time do you want to spend on your hobbies?</label>
            <input type="text" class="form-control" id="time" name="time" placeholder="Time spent" minlength="2" maxlength="50" required></input>
            <div class="invalid-feedback">Text must be less than 50 characters</div>
        </div>

		<div class="mb-3">
            <label for="activities" class="form-label">What types of activities do you like to do when you have free time?</label>
            <input type="text" class="form-control" id="activities" name="activities" placeholder="Activities in free time" minlength="2" maxlength="50" required></input>
            <div class="invalid-feedback">Text must be less than 50 characters</div>
        </div>
        
      
         <div class="mt-4">
             <button class="btn btn-primary" type="submit">Send</button>
             <button class="btn btn-secondary" type="reset">Clear</button>
         </div>
     </form>
    </section>

<div class = "other">
	
	<div>
		<p> What is your gender? <button class = "input-button" type = "submit"> input </button> </p>
	</div>

	<div> 
		<p> What is your age? <button class = "input-button" type = "submit"> input </button> </p>
	</div>

	<div>
		<p> Where are you from? <button class = "input-button" type = "submit"> input </button> </p>
	</div>

	<div>
		<p> Do you partake in any hobbies? <button class = "input-button" type = "submit"> input </button> </p>
	</div>

	<div>
		<p> How much time do you want to spend on your hobbies? <button class = "input-button" type = "submit"> input </button> </p>
	</div>

	<div> 
		<p> What types of activities do you like to do when you have free time? <button class = "input-button" type = "submit"> input </button> </p>
	</div>

</div>

</div>
	<a href="dashboard.php" class="next-button">Next</a>
</div>


</body>
</html>
