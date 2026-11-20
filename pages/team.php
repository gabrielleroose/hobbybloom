<?php
require_once __DIR__ . '/base.php';
$pageTitle = 'Meet the Team | HobbyBloom';
$bodyClass = 'team-body';
$extraHead = '
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link href="/css/v5.css" rel="stylesheet">
';
require __DIR__ . '/../includes/header.php';
?>

<main class="v5-main">

  <header class="v5-page-header">
    <p class="v5-eyebrow">Team 18 · INFO-I494/I495 Capstone · Indiana University</p>
    <h1 class="v5-title">Meet the team</h1>
    <p class="v5-lead">
      We are Informatics majors seeking to make it easier for one to learn new hobby skills. Our group is passionate about developing technology that inspires people, helps remove barriers to entry, and fosters personal development through exploration. Using technical knowledge together with design thinking, research, and business strategy, we offer a multidisciplinary outlook on our project and its mission.
    </p>
  </header>

  <section class="v5-grid-2" aria-label="Team members">

    <article class="v5-card v5-member">
      <img src="/images/Gabby.jpg" alt="Photo of Gabrielle Roose" class="v5-member-photo">
      <div>
        <h2 class="v5-member-name">Gabrielle Roose</h2>
        <span class="v5-pill">UX/UI Designer &amp; Styling Lead</span>
        <p class="v5-member-bio">
          Gabrielle Roose is a senior majoring in Informatics with a specialization in Business and minors in Human-Centered Computing, Web Design &amp; Development, and Marketing within the Kelley School of Business. She has been very helpful in producing high quality work due to her mastery in styling and CSS. Additionally, her experience with coding has ensured our team's success throughout this semester.
        </p>
      </div>
    </article>

    <article class="v5-card v5-member">
      <img src="/images/Drew.jpg" alt="Photo of Drew Bauman" class="v5-member-photo">
      <div>
        <h2 class="v5-member-name">Drew Bauman</h2>
        <span class="v5-pill">Backend Developer &amp; Database Lead</span>
        <p class="v5-member-bio">
          Drew Bauman is a senior graduating in May of 2026 from the Luddy School of Informatics, Computing, and Engineering. He is very knowledgeable on PHP and MySQL, which is very helpful for this project. He also brings important time management, accounting, and leadership skills through his focus on the Kelley School of Business.
        </p>
      </div>
    </article>

    <article class="v5-card v5-member">
      <img src="/images/jack_new.jpg?v=2" alt="Photo of Jack Bruce" class="v5-member-photo">
      <div>
        <h2 class="v5-member-name">Jack Bruce</h2>
        <span class="v5-pill">Project Lead &amp; Full-Stack Developer</span>
        <p class="v5-member-bio">
          Jack Bruce is a senior majoring in Informatics, with a minor in Computer Science and a cognate in Cybersecurity. He has mastered HTML, CSS, Python, and SQL and is still expanding his skill set. He has been an important leader, ensuring our project is at the highest level.
        </p>
      </div>
    </article>

    <article class="v5-card v5-member">
      <img src="/images/Chris.jpg" alt="Photo of Chris Martin" class="v5-member-photo">
      <div>
        <h2 class="v5-member-name">Chris Martin</h2>
        <span class="v5-pill">Research &amp; Front-End Development</span>
        <p class="v5-member-bio">
          Chris Martin is a junior majoring in Informatics, with a cognate and minor in Public Health, but he is graduating early in May of 2026. He is very good at utilizing Python, SQL, HTML, and CSS and has worked very hard doing research on the market. He values providing an application that stands out from existing options and helps people grow through learning new hobbies.
        </p>
      </div>
    </article>

  </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
