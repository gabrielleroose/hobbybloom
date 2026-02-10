<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - HobbyBloom</title>
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body style="display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f0f2f5;">

    <div class="login-container" style="text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        
        <h1 style="color: #153853; margin-bottom: 10px;">HobbyBloom</h1>
        <p style="color: #666; margin-bottom: 30px;">Sign in to start your journey</p>

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
             data-logo_alignment="center">
        </div>
    </div>

    <script>
    function handleCredentialResponse(response) {
        console.log("Google credential response:", response);

        fetch("google_login.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ token: response.credential })
        })
        .then(res => {
            if (res.ok) {
                window.location.href = "index.php";
            } else {
                alert("Login failed. Please try again.");
            }
        })
        .catch(err => console.error("Fetch error:", err));
    }
    </script>
</body>
</html>