<?php
// Public homepage. Signed-in users go straight to their dashboard.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user']['id'])) {
    header('Location: dashboard.php');
    exit;
}
require_once __DIR__ . '/../config/google.php';

$demoUnavailable = ($_GET['demo'] ?? '') === 'unavailable';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HobbyBloom: Grow a hobby, find your people</title>
    <meta name="description" content="HobbyBloom helps beginners start a new hobby with guided modules, hobby circles, and a shared calendar.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;1,400&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        :root {
            --navy: #1E5077;
            --navy-deep: #153853;
            --sage: #bdc29d;
            --sage-soft: #eef0e4;
            --cream: #fbfaf6;
            --ink: #1d2730;
            --muted: #5b6670;
            --line: #dfe2d6;
            --radius: 16px;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            color: var(--ink);
            background: var(--cream);
            line-height: 1.6;
        }
        a { color: inherit; }
        .wrap { width: min(1100px, 100% - 32px); margin-inline: auto; }

        /* Top bar */
        .topbar { position: sticky; top: 0; z-index: 10; background: rgba(251,250,246,.92); backdrop-filter: blur(8px); border-bottom: 1px solid var(--line); }
        .topbar .wrap { display: flex; align-items: center; justify-content: space-between; height: 64px; gap: 12px; }
        .logo { font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 600; color: var(--navy-deep); text-decoration: none; }
        .logo span { color: #6f7a3f; font-style: italic; font-weight: 400; }
        .topbar nav { display: flex; align-items: center; gap: 20px; font-size: .95rem; }
        .topbar nav a { text-decoration: none; color: var(--muted); }
        .topbar nav a:hover { color: var(--navy); }
        .topbar nav .signin { color: #fff; background: var(--navy); padding: 8px 16px; border-radius: 999px; font-weight: 500; }
        .topbar nav .signin:hover { background: var(--navy-deep); color: #fff; }
        @media (max-width: 640px) { .topbar nav .hide-sm { display: none; } }

        /* Hero */
        .hero { padding: 72px 0 56px; background: radial-gradient(1200px 400px at 80% -10%, var(--sage-soft), transparent 70%); }
        .hero .wrap { display: grid; grid-template-columns: 1.1fr .9fr; gap: 48px; align-items: center; }
        .eyebrow { display: inline-block; font-size: .8rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #6f7a3f; background: var(--sage-soft); padding: 4px 12px; border-radius: 999px; }
        h1 { font-family: 'Playfair Display', serif; font-weight: 600; font-size: clamp(2.2rem, 5vw, 3.6rem); line-height: 1.1; color: var(--navy-deep); margin: 16px 0; }
        h1 em { font-weight: 400; color: var(--navy); }
        .lead { font-size: 1.15rem; color: var(--muted); max-width: 34em; margin: 0 0 28px; }
        .cta-row { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }
        .btn-demo { display: inline-flex; align-items: center; gap: 8px; height: 44px; padding: 0 22px; border-radius: 999px; background: var(--navy); color: #fff; font-weight: 700; text-decoration: none; font-size: 1rem; transition: background .2s, transform .2s; }
        .btn-demo:hover { background: var(--navy-deep); transform: translateY(-1px); }
        .cta-note { font-size: .88rem; color: var(--muted); margin-top: 14px; }
        .notice { background: #fff6e0; border: 1px solid #f0d58c; color: #6b5310; padding: 10px 14px; border-radius: 10px; margin-bottom: 20px; font-size: .95rem; }

        .hero-art { position: relative; aspect-ratio: 4 / 3.4; border-radius: 24px; overflow: hidden; box-shadow: 0 30px 60px -30px rgba(21,56,83,.45); }
        .hero-art img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .hero-card { position: absolute; left: 16px; right: 16px; bottom: 16px; background: rgba(255,255,255,.95); border-radius: 14px; padding: 14px 16px; display: flex; gap: 12px; align-items: center; box-shadow: 0 8px 24px rgba(0,0,0,.12); }
        .hero-card .emoji { font-size: 1.6rem; }
        .hero-card strong { display: block; color: var(--navy-deep); }
        .hero-card small { color: var(--muted); }

        /* Sections */
        section { padding: 64px 0; }
        .section-head { text-align: center; max-width: 640px; margin: 0 auto 40px; }
        h2 { font-family: 'Playfair Display', serif; font-weight: 600; font-size: clamp(1.7rem, 3.5vw, 2.4rem); color: var(--navy-deep); margin: 0 0 10px; }
        .section-head p { color: var(--muted); margin: 0; }

        .features { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .feature { background: #fff; border: 1px solid var(--line); border-radius: var(--radius); padding: 24px; }
        .feature .icon { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; background: var(--sage-soft); font-size: 1.4rem; margin-bottom: 14px; }
        .feature h3 { margin: 0 0 6px; font-size: 1.1rem; color: var(--navy-deep); }
        .feature p { margin: 0; color: var(--muted); font-size: .95rem; }

        .steps-band { background: var(--navy-deep); color: #e8eef3; }
        .steps-band h2 { color: #fff; }
        .steps-band .section-head p { color: #b9c7d3; }
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; counter-reset: step; }
        .step { counter-increment: step; }
        .step::before { content: counter(step); display: grid; place-items: center; width: 36px; height: 36px; border-radius: 50%; background: var(--sage); color: var(--navy-deep); font-weight: 700; margin-bottom: 12px; }
        .step h3 { margin: 0 0 6px; color: #fff; font-size: 1.1rem; }
        .step p { margin: 0; color: #b9c7d3; font-size: .95rem; }

        .closing { text-align: center; }
        .closing .cta-row { justify-content: center; }

        footer { border-top: 1px solid var(--line); padding: 28px 0; font-size: .9rem; color: var(--muted); }
        footer .wrap { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; }
        footer a { text-decoration: none; margin-left: 18px; }
        footer a:hover { color: var(--navy); }

        @media (max-width: 900px) {
            .hero .wrap { grid-template-columns: 1fr; }
            .features { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .hero { padding-top: 40px; }
            .features, .steps { grid-template-columns: 1fr; }
            footer a { margin: 0 18px 0 0; }
        }
    </style>
</head>
<body>

<header class="topbar">
    <div class="wrap">
        <a class="logo" href="/">Hobby<span>Bloom</span></a>
        <nav>
            <a class="hide-sm" href="#features">Features</a>
            <a class="hide-sm" href="team.php">Team</a>
            <a class="signin" href="login.php">Sign in</a>
        </nav>
    </div>
</header>

<main>
    <section class="hero">
        <div class="wrap">
            <div>
                <?php if ($demoUnavailable): ?>
                    <div class="notice">The demo isn't available right now. You can still sign in with Google.</div>
                <?php endif; ?>
                <span class="eyebrow">For curious beginners</span>
                <h1>Grow a hobby. <em>Find your people.</em></h1>
                <p class="lead">
                    HobbyBloom helps you start something new with bite-sized guided modules,
                    friendly hobby circles, and a calendar that keeps you showing up.
                </p>
                <div class="cta-row">
                    <a class="btn-demo" href="demo_login.php">Explore the demo &rarr;</a>
                    <div id="g_id_onload"
                         data-client_id="<?= htmlspecialchars(GOOGLE_SIGNIN_CLIENT_ID) ?>"
                         data-callback="handleCredentialResponse"
                         data-auto_prompt="false"></div>
                    <div class="g_id_signin"
                         data-type="standard" data-size="large" data-theme="outline"
                         data-text="signin_with" data-shape="pill" data-logo_alignment="left"></div>
                </div>
                <p class="cta-note">No account needed for the demo. It's a read-only tour with sample data.</p>
            </div>
            <div class="hero-art">
                <img src="/images/plants-grow.jpg" alt="Seedlings growing in soil">
                <div class="hero-card">
                    <span class="emoji">🌱</span>
                    <div>
                        <strong>Day 5 streak</strong>
                        <small>Watercolor basics: Stage 2 complete</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features">
        <div class="wrap">
            <div class="section-head">
                <h2>Everything a new hobby needs</h2>
                <p>Most hobby apps are built for experts. HobbyBloom is built for day one.</p>
            </div>
            <div class="features">
                <div class="feature">
                    <div class="icon">📘</div>
                    <h3>Guided modules</h3>
                    <p>Short stages with videos and quick checks, so you always know the next step.</p>
                </div>
                <div class="feature">
                    <div class="icon">🫂</div>
                    <h3>Hobby circles</h3>
                    <p>Join communities around what you're learning, chat, and share progress.</p>
                </div>
                <div class="feature">
                    <div class="icon">📅</div>
                    <h3>Shared calendar</h3>
                    <p>Plan sessions, invite friends or a whole circle, and sync with Google Calendar.</p>
                </div>
                <div class="feature">
                    <div class="icon">🏅</div>
                    <h3>Streaks &amp; badges</h3>
                    <p>A dashboard that tracks your streak and celebrates milestones as you grow.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="steps-band">
        <div class="wrap">
            <div class="section-head">
                <h2>How it works</h2>
                <p>From "I've always wanted to try that" to a habit in three steps.</p>
            </div>
            <div class="steps">
                <div class="step">
                    <h3>Pick your interests</h3>
                    <p>Tell us what you're curious about and get modules and circles that match.</p>
                </div>
                <div class="step">
                    <h3>Learn in small stages</h3>
                    <p>Watch, practice, and check your understanding at your own pace.</p>
                </div>
                <div class="step">
                    <h3>Grow with others</h3>
                    <p>Follow friends, join events, and keep each other going.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="closing">
        <div class="wrap">
            <h2>See it for yourself</h2>
            <p class="lead" style="margin-inline:auto">Take the demo tour, or sign in with Google to start your own garden of hobbies.</p>
            <div class="cta-row">
                <a class="btn-demo" href="demo_login.php">Explore the demo &rarr;</a>
                <a href="login.php" style="font-weight:500">Sign in with Google</a>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="wrap">
        <span>HobbyBloom &middot; INFO-I494/I495 capstone, Indiana University</span>
        <span>
            <a href="project.php">About the project</a>
            <a href="team.php">Team</a>
        </span>
    </div>
</footer>

<script>
function handleCredentialResponse(response) {
    fetch("google_login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ token: response.credential })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = data.redirect;
        } else {
            alert("Login failed: " + (data.message || "Unknown error"));
        }
    })
    .catch(() => alert("Login failed. Please try again."));
}
</script>
</body>
</html>
