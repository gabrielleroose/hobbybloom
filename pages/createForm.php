<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);
 
require_once 'db.php';
 
$success = false;
$error   = "";
 
$googleId = $_SESSION['google_id'] ?? null;
 
if (!$googleId) {
    header('Location: index.php');
    exit;
}
 
$user_id_sql = "SELECT id FROM users WHERE google_id = :gid";
$stmt = $conn->prepare($user_id_sql);
$stmt->execute([':gid' => $googleId]);
$user_id = $stmt->fetchColumn();
 
$module_id = NULL;
 
if (isset($_GET['module_edit'])) {
    $module_id = (int)$_GET['module_edit'];
 
    $module_info_sql = "SELECT m.id, m.cid, m.name, m.description, m.rating, m.exp_level, m.num_lessons, m.est_comp_time, msp.msid
                        FROM module AS m
                        LEFT JOIN module_stage_progress AS msp ON msp.mid = m.id
                        WHERE m.id = ? AND m.cid = ?";
    $stmt = $conn->prepare($module_info_sql);
    $stmt->execute([$module_id, $user_id]);
    $module_info = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if (!$module_info) {
        header("Location: dashboard.php");
        exit("Invalid Module Information.");
    }
 
    $module_stage_info_sql = "SELECT ms.id, ms.stage_num, ms.title, msv.video_url
                               FROM module_stage AS ms
                               JOIN module AS m ON ms.mid = m.id
                               LEFT JOIN module_stage_videos AS msv ON ms.id = msv.msid
                               WHERE ms.mid = ?";
    $stmt = $conn->prepare($module_stage_info_sql);
    $stmt->execute([$module_id]);
    $module_stage_info = $stmt->fetchAll();
 
    $module_stage_questions_info = [];
    foreach ($module_stage_info as $stage) {
        $q_sql = "SELECT msq.id, msq.question_text, msq.order_num
                  FROM module_stage_questions AS msq
                  JOIN module_stage AS ms ON msq.msid = ms.id
                  WHERE msq.msid = ?";
        $stmt = $conn->prepare($q_sql);
        $stmt->execute([$stage['id']]);
        $questions = $stmt->fetchAll();
 
        $module_stage_questions_info[] = [
            'id'        => $stage['id'],
            'stage_num' => $stage['stage_num'],
            'title'     => $stage['title'],
            'question'  => $questions,
            'video_url' => $stage['video_url'],
        ];
    }
 
    foreach ($module_stage_questions_info as &$stage) {
        foreach ($stage['question'] as &$question) {
            $a_sql = "SELECT msqa.id, msqa.answer, msqa.is_correct, msqa.ans_num
                      FROM module_stage_questions_answers AS msqa
                      JOIN module_stage_questions AS msq ON msqa.msqid = msq.id
                      WHERE msqa.msqid = ?";
            $stmt = $conn->prepare($a_sql);
            $stmt->execute([$question['id']]);
            $question['answers'] = $stmt->fetchAll();
 
            $ua_sql = "SELECT msqua.id, msqua.msqaid
                       FROM module_stage_questions_user_answers AS msqua
                       WHERE msqua.uid = ? AND msqua.msqaid IN
                           (SELECT msqa.id FROM module_stage_questions_answers AS msqa WHERE msqa.msqid = ?)";
            $stmt = $conn->prepare($ua_sql);
            $stmt->execute([$user_id, $question['id']]);
            $question['user_answers'] = $stmt->fetchAll();
        }
    }
}
 
if (isset($_POST['module_edit'])) {
    $module_id = $_POST['module_edit'];
 
    $module_info_sql = "SELECT m.id, m.cid, m.name, m.description, m.rating, m.exp_level, m.num_lessons, msp.msid
                        FROM module AS m
                        LEFT JOIN module_stage_progress AS msp ON msp.mid = m.id
                        WHERE m.id = ?";
    $stmt = $conn->prepare($module_info_sql);
    $stmt->execute([$module_id]);
    $module_info = $stmt->fetch(PDO::FETCH_ASSOC);
 
    $module_stage_info_sql = "SELECT ms.id, ms.stage_num, ms.title
                               FROM module_stage AS ms
                               JOIN module AS m ON ms.mid = m.id
                               WHERE ms.mid = ?";
    $stmt = $conn->prepare($module_stage_info_sql);
    $stmt->execute([$module_id]);
    $module_stage_info = $stmt->fetchAll();
 
    $module_stage_questions_info = [];
    foreach ($module_stage_info as $stage) {
        $q_sql = "SELECT msq.id, msq.question_text, msq.order_num
                  FROM module_stage_questions AS msq
                  JOIN module_stage AS ms ON msq.msid = ms.id
                  WHERE msq.msid = ?";
        $stmt = $conn->prepare($q_sql);
        $stmt->execute([$stage['id']]);
        $questions = $stmt->fetchAll();
 
        $module_stage_questions_info[] = [
            'id'        => $stage['id'],
            'stage_num' => $stage['stage_num'],
            'title'     => $stage['title'],
            'question'  => $questions,
        ];
    }
 
    foreach ($module_stage_questions_info as &$stage) {
        foreach ($stage['question'] as &$question) {
            $a_sql = "SELECT msqa.id, msqa.answer, msqa.is_correct, msqa.ans_num
                      FROM module_stage_questions_answers AS msqa
                      JOIN module_stage_questions AS msq ON msqa.msqid = msq.id
                      WHERE msqa.msqid = ?";
            $stmt = $conn->prepare($a_sql);
            $stmt->execute([$question['id']]);
            $question['answers'] = $stmt->fetchAll();
        }
    }
}
 
$circleId = $_GET['circle_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Module | HobbyBloom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/nav.css" rel="stylesheet">
</head>
<body class="create-body">
 
<?php include 'base.php'; ?>
 
<div class="create-module-main">

 
    <?php if (!empty($error)): ?>
        <div class="cm-message cm-message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
 
    <?php if ($success): ?>
        <div class="cm-message cm-message-success">Module created successfully!</div>
    <?php endif; ?>
 
    <div class="create-module-inner">

    <div class="create-module-heading">
        <div class="cm-eyebrow">Module Builder</div>
        <h2><?= $module_id ? 'Edit Module' : 'Create a Module' ?></h2>
    </div>


        <div class="create-module-inner-form">
 
            <form method="POST" action="form_processing.php" enctype="multipart/form-data">
                <input type="hidden" name="circle_id" value="<?= htmlspecialchars($circleId ?? '') ?>">
 
                <!-- Module Name -->
                <div class="cm-field">
                    <label class="create-module-label cm-show">Module Name</label>
                    <input class="create-module-input" type="text" name="name"
                           placeholder="e.g. Intro to Watercolour"
                           value="<?= htmlspecialchars($module_info['name'] ?? '') ?>"
                           required>
                </div>
 
                <!-- Description -->
                <div class="cm-field">
                    <label class="create-module-label cm-show">Description</label>
                    <textarea class="create-module-input" name="description" rows="4"
                              placeholder="What will learners get out of this module?"><?= htmlspecialchars($module_info['description'] ?? '') ?></textarea>
                </div>
 
                <!-- Experience Level + Estimated Time (side by side) -->
                <div class="cm-row">
                    <div class="cm-field">
                        <label class="create-module-label cm-show">Experience Level</label>
                        <select class="create-module-input-exp" id="exp_level" name="exp_level">
                            <option value="beginner"     <?= (($module_info['exp_level'] ?? '') === 'beginner')     ? 'selected' : '' ?>>Beginner</option>
                            <option value="intermediate" <?= (($module_info['exp_level'] ?? '') === 'intermediate') ? 'selected' : '' ?>>Intermediate</option>
                            <option value="expert"       <?= (($module_info['exp_level'] ?? '') === 'expert')       ? 'selected' : '' ?>>Expert</option>
                        </select>
                    </div>
                    <div class="cm-field">
                        <label class="create-module-label cm-show">Estimated Time (minutes)</label>
                        <input class="create-module-input" type="number" id="estimate" name="estimate"
                               placeholder="e.g. 45" min="1"
                               value="<?= htmlspecialchars($module_info['est_comp_time'] ?? '') ?>"
                               required>
                        <p class="create-form-output cm-time-hint" id="formattedOutput"></p>
                    </div>
                </div>
 
                <!-- Notes -->
                <div class="cm-field">
                    <label class="create-module-label cm-show">Notes</label>
                    <textarea class="create-module-input" name="notes" rows="3"
                              placeholder="Any extra notes for learners..."></textarea>
                </div>
 
                <hr class="cm-divider">
 
                <!-- Quiz toggle -->
                <div class="create-form-quiz">
                    <label class="cm-checkbox-row">
                        <input class="cm-checkbox" type="checkbox" id="enable_stages">
                        <span class="create-module-label cm-show">Include interactive quiz stages with videos?</span>
                    </label>
                </div>
 
                <!-- Stages section -->
                <div id="stages_section" style="display:none;">
                    <div class="cm-field">
                        <label class="create-module-label cm-show">Number of Stages</label>
                        <input class="create-module-input" type="number" id="stage_num" name="stage_num"
                               placeholder="Up to 5 stages" min="1" max="5">
                    </div>
                    <div id="stages_container"></div>
                </div>
 
                <!-- Submit -->
                <?php if (!$module_id): ?>
                    <button type="submit" class="module_display_entry_button createForm" name="create_module">
                        Create Module
                    </button>
                <?php else: ?>
                    <input type="hidden" name="module_id" value="<?= htmlspecialchars($module_id) ?>">
                    <button type="submit" class="module_display_entry_button createForm" name="edit_module">
                        Confirm Module Changes
                    </button>
                <?php endif; ?>
 
            </form>
        </div>
    </div>
</div>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>
 
<script>
    const existingStages = <?= json_encode($module_stage_questions_info ?? []) ?>;
    const enableStages   = document.getElementById("enable_stages");
    const stageSection   = document.getElementById("stages_section");
 
    if (existingStages.length > 0) {
        enableStages.checked = true;
        stageSection.style.display = "flex";
        stageSection.classList.add("stages-visible");
    }
 
    enableStages.addEventListener("change", function () {
        if (this.checked) {
            stageSection.style.display = "flex";
            stageSection.classList.add("stages-visible");
        } else {
            stageSection.style.display = "none";
            stageSection.classList.remove("stages-visible");
            document.getElementById("stages_container").innerHTML = "";
            document.getElementById("stage_num").value = "";
        }
    });
 
    function generateStageVideoInput(stage_num, url = "") {
        return `
            <div class="cm-field">
                <label class="create-module-label cm-show">Video URL</label>
                <input class="create-module-input" type="url"
                       name="stages[${stage_num}][video_url]"
                       value="${url}"
                       placeholder="https://youtube.com/..." required>
            </div>
        `;
    }
 
    document.addEventListener("DOMContentLoaded", function () {
        const stageSelect      = document.getElementById("stage_num");
        const stagesContainer  = document.getElementById("stages_container");
 
        function generateStages(stageCount, data = null) {
            stagesContainer.innerHTML = "";
 
            for (let i = 1; i <= stageCount; i++) {
                const stageData    = data ? data[i - 1] : null;
                const questionData = stageData?.question?.[0] ?? null;
                const answersData  = questionData?.answers ?? [];
 
                let answersHTML = "";
                for (let a = 1; a <= 4; a++) {
                    const answerObj  = answersData[a - 1] ?? null;
                    const answerText = answerObj ? answerObj.answer : "";
                    const isCorrect  = answerObj ? answerObj.is_correct : (a === 4 ? 1 : 0);
                    const isCorrectField = a === 4;
 
                    answersHTML += `
                        <div class="cm-field">
                            <label class="create-module-label cm-show">${isCorrectField ? 'Correct Answer' : 'False Answer ' + a}</label>
                            <input class="create-module-input${isCorrectField ? ' cm-input-correct' : ''}"
                                   type="text"
                                   name="stages[${i}][answers][${a}][text]"
                                   value="${answerText}"
                                   placeholder="${isCorrectField ? 'Correct answer' : 'Wrong answer'}"
                                   required>
                            <input type="hidden"
                                   name="stages[${i}][answers][${a}][is_correct]"
                                   value="${isCorrect}">
                        </div>
                    `;
                }
 
                const videoHTML = generateStageVideoInput(i, stageData?.video_url ?? "");
 
                const stageDiv = document.createElement("div");
                stageDiv.className = "cm-stage-card";
                stageDiv.innerHTML = `
                    <div class="cm-stage-eyebrow">Stage ${i}</div>
 
                    <div class="cm-field">
                        <label class="create-module-label cm-show">Stage Title</label>
                        <input class="create-module-input" type="text"
                               name="stages[${i}][title]"
                               value="${stageData?.title ?? ''}"
                               placeholder="e.g. Colour Mixing Basics"
                               required>
                    </div>
 
                    ${videoHTML}
 
                    <div class="cm-field">
                        <label class="create-module-label cm-show">Question</label>
                        <input class="create-module-input" type="text"
                               name="stages[${i}][question]"
                               value="${questionData?.question_text ?? ''}"
                               placeholder="e.g. Which colours make green?"
                               required>
                    </div>
 
                    ${answersHTML}
                `;
 
                stagesContainer.appendChild(stageDiv);
            }
        }
 
        if (existingStages.length > 0) {
            stageSelect.value = existingStages.length;
            generateStages(existingStages.length, existingStages);
        }
 
        stageSelect.addEventListener("input", function () {
            const count = this.valueAsNumber;
            if (!count || count < 1 || count > 5) {
                stagesContainer.innerHTML = "";
                return;
            }
            generateStages(count);
        });
    });
 
    // Estimated time formatter
    document.getElementById("estimate").addEventListener("input", function () {
        const minutes = parseInt(this.value, 10);
        const output  = document.getElementById("formattedOutput");
        if (isNaN(minutes) || minutes <= 0) { output.textContent = ""; return; }
        const hours   = Math.floor(minutes / 60);
        const mins    = minutes % 60;
        let formatted = "";
        if (hours > 0) formatted += `${hours} hour${hours !== 1 ? "s" : ""} `;
        if (mins  > 0) formatted += `${mins} minute${mins !== 1 ? "s" : ""}`;
        output.textContent = formatted ? `= ${formatted}` : "";
    });
</script>
 
</body>
</html>
 