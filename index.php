<?php include 'base.php'; ?>
<!DOCTYPE HTML>
<html>
<head> <link href="./css/style.css" rel="stylesheet"> </head>
<body> 
<?php 
if (!isset($_SESSION['user'])): ?>
    <!-- GOOGLE SIGN-IN -->
    <div id="g_id_onload"
         data-client_id="1011869688630-kl05vvf13cg6u6d1tlo9rnj0l4kj7rvn.apps.googleusercontent.com"
         data-callback="handleCredentialResponse">
    </div>

    <div class="g_id_signin"></div>

    <script>
        function handleCredentialResponse(response) {
            fetch("google_login.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ token: response.credential })
            })
            .then(() => window.location.reload());
        }
    </script>
<?php endif; ?>

<div class = "outter-box">
	<div class = "inner-box">
		<p>Tell us a little bit about you!</p>
		<div class = "subtext"> 
			<p>This information will be stored alongside your general user data, but not sold.</p>
		</div>
	</div>

</div>

<div class = "other">
	
	<div class = "padding">
		<p> What is your gender? test addition </p>
	</div class = "padding">

	<div class = "padding"> 
		<p> What is your age? </p>
	</div>

	<div class = "padding">
		<p> Where are you from? </p>
	</div>

	<div class = "padding">
		<p> Do you partake in any hobbies? </p>
	</div>

	<div class = "padding">
		<p> How much time do you want to spend on your hobbies? </p>
	</div>

	<div class = "padding"> 
		<p> What types of activities do you like to do when you have free time? </p>
	</div>

</div>

</body>
</html>
