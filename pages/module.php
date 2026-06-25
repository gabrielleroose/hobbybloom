<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
 
require_once 'db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/twig.php';
require_once __DIR__ . '/base.php';
 
$googleId = $_SESSION['google_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en" class="begin-module-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Begin Module | HobbyBloom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/nav.css" rel="stylesheet">
</head>
<body class="begin-module-body">
 
<div class="begin-module-page">
<div class="begin-module-container">
 
<?php
if (!$googleId) {
    header('Location: index.php');
    exit;
}
 
function get_current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host     = $_SERVER['HTTP_HOST'];
    $url      = $_SERVER['REQUEST_URI'];
    return $protocol . "://" . $host . $url;
}
 
$current_url = get_current_url();
$query_mid   = NULL;
 
if (str_contains($current_url, '?')) {
    $url_parts     = explode('?', $current_url, 2);
    $module_id_url = explode('=', $url_parts[1], 2);
    $query_mid     = (int)$module_id_url[1];
}
 
try {
    $user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
    $stmt = $conn->prepare($user_id_sql);
    $stmt->execute([':gid' => $googleId]);
    $user_id = $stmt->fetchColumn();
 
    if (!$user_id) throw new Exception("User session not found. Please log in again.");
 
    if (!$query_mid) {
        $mod_id = $_REQUEST['module_id'] ?? null;
        if (!$mod_id) throw new Exception("No module selected.");
    } else {
        $mod_id = $query_mid;
    }
 
    // Fetch stages
    $mod_stage_sql = "
        SELECT ms.id, ms.title, ms.stage_num, MIN(msv.video_url) AS video_url
        FROM module_stage AS ms
        JOIN module AS m ON ms.mid = m.id
        LEFT JOIN module_stage_videos AS msv ON ms.id = msv.msid
        WHERE ms.mid = :mid
        GROUP BY ms.id
        ORDER BY ms.stage_num
    ";
    $stmt = $conn->prepare($mod_stage_sql);
    $stmt->execute(['mid' => $mod_id]);
    $mod_stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    $hasStages     = !empty($mod_stages);
    $totalStages   = count($mod_stages);
    $seen_videos   = [];
    $unique_videos = [];
 
    if ($hasStages) {
 
        foreach ($mod_stages as $stage) {
            $video_url = $stage['video_url'];
            if (!$video_url) continue;
            if (preg_match('/(youtu\.be\/|v=|shorts\/)([A-Za-z0-9_-]+)/', $video_url, $matches)) {
                $video_id = preg_replace('/[^A-Za-z0-9_-]/', '', $matches[2]);
                $embed    = "https://www.youtube.com/embed/" . $video_id;
                if (!in_array($embed, $seen_videos)) {
                    $seen_videos[]   = $embed;
                    $unique_videos[] = $embed;
                }
            }
        }
 
        $num_unique_videos = count($unique_videos);
        $displayed_videos  = [];
 
        foreach ($mod_stages as $stage) {
            $stage_num = $stage['stage_num'];
            $stage_id  = $stage['id'];
            $hidden    = ($stage_num == 1) ? "" : "hidden";
            $pct       = round(($stage_num / $totalStages) * 100);
 
            $q_stmt = $conn->prepare("SELECT id, question_text FROM module_stage_questions WHERE msid = ?");
            $q_stmt->execute([$stage_id]);
            $questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
            $hasQuiz   = !empty($questions);
?>
 
        <!-- ── Stage <?= $stage_num ?> ── -->
        <div class="stage <?= $hidden ?>" id="stage_<?= $stage_num ?>">
 
            <!-- Progress bar -->
            <div class="bm-progress-wrap">
                <div class="bm-progress-label">Stage <?= $stage_num ?> of <?= $totalStages ?></div>
                <div class="bm-progress-track">
                    <div class="bm-progress-fill" style="width: <?= $pct ?>%;"></div>
                </div>
            </div>
 
            <!-- Stage card -->
            <div class="bm-stage-card">
 
                <div>
                    <div class="bm-stage-eyebrow">Stage <?= $stage_num ?></div>
                    <div class="stage_title"><?= htmlspecialchars($stage['title']) ?></div>
                </div>
 
                <?php if ($num_unique_videos == 1 && $stage_num == 1): ?>
                    <div class="module-single-video">
                        <iframe src="<?= htmlspecialchars($unique_videos[0]) ?>"
                            title="YouTube video player" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen></iframe>
                    </div>
                <?php endif; ?>
 
                <?php
                $video_url = $stage['video_url'];
                if ($video_url && preg_match('/(youtu\.be\/|v=|shorts\/)([A-Za-z0-9_-]+)/', $video_url, $matches)):
                    $video_id  = preg_replace('/[^A-Za-z0-9_-]/', '', $matches[2]);
                    $embed_url = "https://www.youtube.com/embed/" . $video_id;
                    if ($num_unique_videos > 1 && !in_array($embed_url, $displayed_videos)):
                        $displayed_videos[] = $embed_url;
                ?>
                    <div class="module-single-video">
                        <iframe src="<?= htmlspecialchars($embed_url) ?>"
                            title="YouTube video player" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen></iframe>
                    </div>
                <?php endif; endif; ?>
 
                <?php if ($hasQuiz): ?>
                    <?php foreach ($questions as $question):
                        $question_id = $question['id'];
                        $a_stmt = $conn->prepare("SELECT id, answer, is_correct FROM module_stage_questions_answers WHERE msqid = ?");
                        $a_stmt->execute([$question_id]);
                        $answers = $a_stmt->fetchAll(PDO::FETCH_ASSOC);
                        shuffle($answers);
                    ?>
                    <div class="module-question">
                        <div class="bm-question-label">Question</div>
                        <p><?= htmlspecialchars($question['question_text']) ?></p>
                    </div>
                    <div class="answers-list">
                        <?php foreach ($answers as $answer): ?>
                            <label class="module_answer">
                                <input type="radio"
                                       name="question_<?= $question_id ?>"
                                       value="<?= $answer['id'] ?>">
                                <?= htmlspecialchars($answer['answer']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
 
                    <button class="submit-stage" data-stage="<?= $stage_num ?>" data-has-quiz="true">
                        Submit Answer
                    </button>
 
                <?php else: ?>
                    <p style="font-size:13px; color:#7a8c5a; font-style:italic; margin:0;">
                        Watch the video above and continue when you're ready.
                    </p>
                    <button class="submit-stage" data-stage="<?= $stage_num ?>" data-has-quiz="false">
                        Next Stage →
                    </button>
                <?php endif; ?>
 
            </div><!-- end .bm-stage-card -->
 
            <!-- Pills BELOW the card -->
            <div class="bm-stage-pills">
                <?php for ($i = 1; $i <= $totalStages; $i++):
                    $pillClass = $i < $stage_num ? 'done' : ($i === $stage_num ? 'active' : '');
                ?>
                    <div class="bm-stage-pill <?= $pillClass ?>"></div>
                <?php endfor; ?>
            </div>
 
        </div><!-- end .stage -->
 
<?php
        }
 
    } else {
        // No stages — video only
        $module_videos_sql = "SELECT video_url FROM module_stage_videos WHERE mid = ?";
        $stmt = $conn->prepare($module_videos_sql);
        $stmt->execute([$mod_id]);
        $module_videos = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
        <div class="bm-progress-wrap">
            <div class="bm-progress-label">Video Module</div>
            <div class="bm-progress-track">
                <div class="bm-progress-fill" style="width: 100%;"></div>
            </div>
        </div>
 
        <div class="bm-stage-card">
            <div>
                <div class="bm-stage-eyebrow">Module Content</div>
                <div class="stage_title">Watch &amp; Learn</div>
            </div>
            <?php foreach ($module_videos as $video_url): ?>
                <div class="module-single-video">
                    <iframe src="<?= htmlspecialchars($video_url) ?>"
                        title="YouTube video player" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                </div>
            <?php endforeach; ?>
        </div>
 
        <div class="bm-stage-pills">
            <div class="bm-stage-pill done"></div>
        </div>
<?php
    }
 
} catch (Exception $e) {
    echo "<p style='color:#c0392b; font-family:DM Sans,sans-serif; padding:1rem;'>Error: "
         . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
 
<!-- Bottom nav row -->
<div class="bm-nav-row">
    <div class="bm-btn-group">
        <?php if (isset($user_id)): ?>
            <button id="reportModuleBtn">Report Module</button>
        <?php endif; ?>
 
        <?php if (!$hasStages): ?>
            <button id="completeModuleBtn">Complete Module</button>
        <?php else: ?>
            <button id="completeModuleBtn" class="bm-complete-inline" style="display:none;">
                Complete Module
            </button>
        <?php endif; ?>
    </div>
</div>
 
</div>
</div>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 
<script>
    const moduleId = <?php echo json_encode($mod_id ?? null); ?>;
</script>
 
<script>
document.addEventListener('DOMContentLoaded', function () {
 
    // ── Answer row highlight ──
    document.querySelectorAll('.answers-list').forEach(list => {
        list.querySelectorAll('.module_answer').forEach(label => {
            label.addEventListener('click', function () {
                list.querySelectorAll('.module_answer').forEach(l => {
                    l.style.background = '';
                    l.style.borderColor = '';
                });
                this.style.background = 'rgba(44,108,163,0.10)';
                this.style.borderColor = 'rgba(44,108,163,0.35)';
            });
        });
    });
 
    // ── Single unified submit handler (replaces module.js) ──
    document.querySelectorAll('.submit-stage').forEach(button => {
        button.addEventListener('click', function () {
            const stageNum  = this.dataset.stage;
            const hasQuiz   = this.dataset.hasQuiz === 'true';
            const currStage = document.getElementById('stage_' + stageNum);
            const nextNum   = parseInt(stageNum) + 1;
            const nextStage = document.getElementById('stage_' + nextNum);
            const isFinal   = !nextStage;
 
            function goNext() {
                if (isFinal) {
                    // Show complete button and alert
                    const completeBtn = document.getElementById('completeModuleBtn');
                    if (completeBtn) completeBtn.style.display = 'inline-flex';
                    alert("🎉 Module complete! Great work.");
                } else {
                    if (currStage) currStage.classList.add('hidden');
                    nextStage.classList.remove('hidden');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
 
            if (!hasQuiz) {
                // No quiz — just advance, tell backend
                fetch('check_answer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        module_id: moduleId,
                        stage_num: stageNum,
                        is_final_stage: isFinal,
                        no_quiz: true
                    })
                })
                .then(res => res.json())
                .then(() => goNext())
                .catch(err => { console.error(err); goNext(); });
                return;
            }
 
            // Has quiz — check a radio is selected
            const checked = document.querySelectorAll(
                '#stage_' + stageNum + ' input[type="radio"]:checked'
            );
            if (checked.length === 0) {
                alert("Please select an answer before submitting.");
                return;
            }
 
            const answerId = checked[0].value;
 
            fetch('check_answer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    answer_id: answerId,
                    stage_num: stageNum,
                    is_final_stage: isFinal,
                    module_id: moduleId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.correct) {
                    goNext();
                } else {
                    alert("❌ Incorrect answer — give it another try!");
                }
            })
            .catch(err => console.error(err));
        });
    });
 
    // ── Complete module button (no-stage video modules) ──
    const completeBtn = document.getElementById('completeModuleBtn');
    if (completeBtn) {
        completeBtn.addEventListener('click', function () {
            fetch('check_answer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ module_id: moduleId, no_quiz: true })
            })
            .then(res => res.json())
            .then(data => {
                if (data.completed) {
                    alert("🎉 Module complete!");
                    this.disabled = true;
                }
            })
            .catch(err => console.error(err));
        });
    }
 
    // ── Report module ──
    const reportBtn = document.getElementById('reportModuleBtn');
    if (reportBtn) {
        reportBtn.addEventListener('click', function () {
            const reason = prompt("Why are you reporting this module?");
            if (!reason || reason.trim() === "") return;
 
            fetch('submit_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'module',
                    item_id: <?= json_encode($mod_id ?? null) ?>,
                    reason: reason.trim()
                })
            })
            .then(res => res.json())
            .then(data => alert(data.message || "Report submitted."))
            .catch(() => alert("Error submitting report."));
        });
    }
 
});
</script>
 
</body>
</html>
 