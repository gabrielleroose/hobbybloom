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
	<button class = "next-button" type = submit> Next </button> 

</body>
</html>
