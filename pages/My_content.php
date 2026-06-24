<?php
ob_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

include 'base.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['module_delete'])) {
        $mid = (int)$_POST['module_delete'];
        $conn->prepare("DELETE FROM module WHERE id = ? AND cid = ?")->execute([$mid, $user_id]);
        ob_end_clean();
        header('Location: My_content.php?tab=modules&deleted=1');
        exit;
    }

    if (isset($_POST['event_delete'])) {
        $eid = (int)$_POST['event_delete'];
        $conn->prepare("DELETE FROM event_invites WHERE event_id = ?")->execute([$eid]);
        $conn->prepare("DELETE FROM events WHERE id = ? AND created_by = ?")->execute([$eid, $user_id]);
        ob_end_clean();
        header('Location: My_content.php?tab=events&deleted=1');
        exit;
    }

    if (isset($_POST['circle_delete'])) {
        $cid = (int)$_POST['circle_delete'];
        $conn->prepare("DELETE FROM circle WHERE circle_id = ? AND uid = ?")->execute([$cid, $user_id]);
        ob_end_clean();
        header('Location: My_content.php?tab=circles&deleted=1');
        exit;
    }
}

$activeTab = $_GET['tab'] ?? 'modules';

$modStmt = $conn->prepare("
    SELECT id, name, description, rating, exp_level, num_lessons, est_comp_time, created_at
    FROM module WHERE cid = ?
    ORDER BY created_at DESC
");
$modStmt->execute([$user_id]);
$modules = $modStmt->fetchAll(PDO::FETCH_ASSOC);

$evtStmt = $conn->prepare("
    SELECT id, title, event_date, event_time, location, description, created_at
    FROM events WHERE created_by = ?
    ORDER BY event_date DESC
");
$evtStmt->execute([$user_id]);
$events = $evtStmt->fetchAll(PDO::FETCH_ASSOC);

$cirStmt = $conn->prepare("
    SELECT c.circle_id, c.name, c.description, c.color, c.category, c.created_at,
           COUNT(cm.user_id) AS member_count
    FROM circle c
    LEFT JOIN circle_members cm ON c.circle_id = cm.circle_id
    WHERE c.uid = ?
    GROUP BY c.circle_id
    ORDER BY c.created_at DESC
");
$cirStmt->execute([$user_id]);
$circles = $cirStmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Content | HobbyBloom</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">
    <style>
        .mc-page {
        /* background-color: #F1F2F5; */
        border-radius: 2rem;
        /* box-shadow: 1px 1px #434343; */
        width: 100%;
        max-width: 900px;
        display: flex;
        flex-direction: column;
        margin: 0 auto;
        justify-content: center;
        padding: 32px 20px 60px;

        /* glassmorphism */
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease-in-out;
        }

        .mc-heading {
            font-size: 2rem;
            font-weight: 700;
            color: #2C6CA3;
            margin-bottom: 6px;
            letter-spacing: -.5px;
        }

        .mc-subheading {
            color: #2C6CA3;
            font-size: .95rem;
            margin-bottom: 32px;
            font-style: italic;
        }

        .mc-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e8e8e8;
            margin-bottom: 28px;
        }

        .mc-tab {
            padding: 11px 28px;
            font-size: .88rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #2C6CA3;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            transition: color .15s, border-color .15s;
            font-family: 'Georgia', serif;
        }

        .mc-tab:hover { color: #156914; } /* make green */

        .mc-tab.active {
            color: #156914;
            border-bottom-color: #156914;
        }

        .mc-tab-count {
            display: inline-block;
            background: #e8f0f7;
            color: #1f5077;
            border-radius: 20px;
            padding: 1px 7px;
            font-size: .72rem;
            margin-left: 5px;
            font-style: normal;
        }

        .mc-panel { display: none; }
        .mc-panel.active { display: block; }

        .mc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px;
        }

        .mc-card {
            background: #fff;
            border-radius: 12px;
            border: 1.5px solid #ebebeb;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: box-shadow .2s, transform .2s;
            position: relative;
            overflow: hidden;
        }

        .mc-card:hover {
            box-shadow: 0 8px 28px rgba(31,80,119,.1);
            transform: translateY(-2px);
        }

        .mc-card-accent {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: #2C6CA3;
        }

        .mc-card-title {
            font-size: 1rem;
            font-weight: 700;
            color: #2C6CA3;
            margin: 0;
            text-decoration: none;
            display: block;
        }

        .mc-card-title:hover { color: #156914; } /* make gree */

        .mc-card-meta {
            font-size: .78rem;
            color: #2C6CA3;
            font-style: italic;
        }

        .mc-card-desc {
            font-size: .85rem;
            color: #2C6CA3;
            line-height: 1.5;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .mc-card-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 4px;
        }

        .mc-tag {
            background: #f0f6fc;
            color: #2C6CA3;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: .72rem;
            font-weight: 600;
        }

        .mc-card-actions {
            display: flex;
            gap: 7px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .mc-btn {
            padding: 6px 14px;
            border-radius: 7px;
            font-size: .78rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background .15s;
            font-family: 'Georgia', serif;
        }

        .mc-btn-view       { background: #2C6CA3; color: #fff; }
        .mc-btn-view:hover { background: #156914; } /* make green */
        .mc-btn-edit       { background: #f0f6fc; color: #2C6CA3; border: 1.5px solid #2C6CA3; }
        .mc-btn-edit:hover { background: #e0edf8; }
        .mc-btn-delete     { background: #fff0f0; color: #e74c3c; border: 1.5px solid #e74c3c; }
        .mc-btn-delete:hover { background: #fde8e8; }

        .mc-empty {
            text-align: center;
            padding: 60px 20px;
            color: #2C6CA3;
        }

        .mc-empty-icon { font-size: 3rem; margin-bottom: 12px; }
        .mc-empty p { font-size: .95rem; font-style: italic; }

        .mc-empty-link {
            display: inline-block;
            margin-top: 14px;
            padding: 9px 22px;
            background: #2C6CA3;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-size: .85rem;
            font-weight: 700;
            transition: background .15s;
        }

        .mc-empty-link:hover { background: #156914; }

        .mc-toast {
            display: none;
            position: fixed;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%);
            background: #27ae60;
            color: #fff;
            padding: 12px 26px;
            border-radius: 10px;
            font-size: .88rem;
            font-weight: 700;
            z-index: 999;
            box-shadow: 0 4px 16px rgba(0,0,0,.15);
        }

        .event-date-badge {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            background: #2C6CA3;
            color: #fff;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: .72rem;
            font-weight: 700;
            line-height: 1.3;
            width: fit-content;
        }

        .event-date-badge .month { text-transform: uppercase; font-size: .65rem; opacity: .8; }
        .event-date-badge .day   { font-size: 1.1rem; line-height: 1; }
    </style>
</head>
<body class="module-body">

<div class="mc-page">
    <h1 class="mc-heading">My Content</h1>
    <p class="mc-subheading">Everything you've created, in one place.</p>


    <div class="mc-tabs">
        <button class="mc-tab <?= $activeTab === 'modules' ? 'active' : '' ?>" data-tab="modules">
            Modules <span class="mc-tab-count"><?= count($modules) ?></span>
        </button>
        <button class="mc-tab <?= $activeTab === 'events' ? 'active' : '' ?>" data-tab="events">
            Events <span class="mc-tab-count"><?= count($events) ?></span>
        </button>
        <button class="mc-tab <?= $activeTab === 'circles' ? 'active' : '' ?>" data-tab="circles">
            Circles <span class="mc-tab-count"><?= count($circles) ?></span>
        </button>
    </div>


    <div class="mc-panel <?= $activeTab === 'modules' ? 'active' : '' ?>" id="panel-modules">
        <?php if (empty($modules)): ?>
            <div class="mc-empty">
                <div class="mc-empty-icon">📚</div>
                <p>You haven't created any modules yet.</p>
                <a href="createForm.php" class="mc-empty-link">Create a Module</a>
            </div>
        <?php else: ?>
            <div class="mc-grid">
                <?php foreach ($modules as $mod): ?>
                    <div class="mc-card">
                        <div class="mc-card-accent"></div>
                        <a href="modules_display.php" class="mc-card-title"><?= htmlspecialchars($mod['name']) ?></a>
                        <div class="mc-card-meta">
                            Created <?= date('M j, Y', strtotime($mod['created_at'])) ?>
                        </div>
                        <p class="mc-card-desc"><?= htmlspecialchars($mod['description'] ?? '') ?></p>
                        <div class="mc-card-tags">
                            <span class="mc-tag"><?= htmlspecialchars($mod['exp_level']) ?></span>
                            <span class="mc-tag"><?= $mod['num_lessons'] ?> lessons</span>
                        </div>
                        <div class="mc-card-actions">
                            <form method="POST" action="module.php">
                                <button type="submit" class="mc-btn mc-btn-view" name="module_id" value="<?= $mod['id'] ?>">Begin</button>
                            </form>
                            <a href="createForm.php?module_edit=<?= $mod['id'] ?>" class="mc-btn mc-btn-edit">Edit</a>
                            <form method="POST" onsubmit="return confirm('Delete this module?')">
                                <button type="submit" class="mc-btn mc-btn-delete" name="module_delete" value="<?= $mod['id'] ?>">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:24px;">
                <a href="createForm.php" class="mc-empty-link">+ Create New Module</a>
            </div>
        <?php endif; ?>
    </div>


    <div class="mc-panel <?= $activeTab === 'events' ? 'active' : '' ?>" id="panel-events">
        <?php if (empty($events)): ?>
            <div class="mc-empty">
                <div class="mc-empty-icon">📅</div>
                <p>You haven't created any events yet.</p>
                <a href="calendar.php" class="mc-empty-link">Go to Calendar</a>
            </div>
        <?php else: ?>
            <div class="mc-grid">
                <?php foreach ($events as $evt):
                    $dateObj = new DateTime($evt['event_date']);
                ?>
                    <div class="mc-card">
                        <div class="mc-card-accent" style="background:#2980b9;"></div>
                        <div style="display:flex; align-items:flex-start; gap:12px;">
                            <div class="event-date-badge">
                                <span class="month"><?= $dateObj->format('M') ?></span>
                                <span class="day"><?= $dateObj->format('j') ?></span>
                                <span class="month"><?= $dateObj->format('Y') ?></span>
                            </div>
                            <div style="flex:1;">
                                <a href="calendar.php" class="mc-card-title"><?= htmlspecialchars($evt['title']) ?></a>
                                <div class="mc-card-meta">
                                    <?php if ($evt['event_time']): ?>
                                        🕐 <?= date('g:i A', strtotime($evt['event_time'])) ?>
                                    <?php endif; ?>
                                    <?php if ($evt['location']): ?>
                                        &nbsp;📍 <?= htmlspecialchars($evt['location']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($evt['description']): ?>
                            <p class="mc-card-desc"><?= htmlspecialchars($evt['description']) ?></p>
                        <?php endif; ?>
                        <div class="mc-card-actions">
                            <a href="calendar.php" class="mc-btn mc-btn-view">View on Calendar</a>
                            <form method="POST" onsubmit="return confirm('Delete this event?')">
                                <button type="submit" class="mc-btn mc-btn-delete" name="event_delete" value="<?= $evt['id'] ?>">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:24px;">
                <a href="calendar.php" class="mc-empty-link">+ Create New Event</a>
            </div>
        <?php endif; ?>
    </div>


    <div class="mc-panel <?= $activeTab === 'circles' ? 'active' : '' ?>" id="panel-circles">
        <?php if (empty($circles)): ?>
            <div class="mc-empty">
                <div class="mc-empty-icon">⭕</div>
                <p>You haven't created any circles yet.</p>
                <a href="create_circle.php" class="mc-empty-link">Create a Circle</a>
            </div>
        <?php else: ?>
            <div class="mc-grid">
                <?php foreach ($circles as $circle): ?>
                    <div class="mc-card">
                        <div class="mc-card-accent" style="background:<?= htmlspecialchars($circle['color'] ?? '#1f5077') ?>;"></div>
                        <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" class="mc-card-title">
                            <?= htmlspecialchars($circle['name']) ?>
                        </a>
                        <div class="mc-card-meta">
                            <?= $circle['member_count'] ?> member<?= $circle['member_count'] != 1 ? 's' : '' ?>
                            &nbsp;·&nbsp; <?= htmlspecialchars($circle['category'] ?? 'General') ?>
                        </div>
                        <p class="mc-card-desc"><?= htmlspecialchars($circle['description'] ?? '') ?></p>
                        <div class="mc-card-actions">
                            <a href="circle_detail.php?hobby=<?= urlencode($circle['name']) ?>" class="mc-btn mc-btn-view">View</a>
                            <a href="edit_circle.php?id=<?= $circle['circle_id'] ?>" class="mc-btn mc-btn-edit">Edit</a>
                            <form method="POST" onsubmit="return confirm('Delete this circle?')">
                                <button type="submit" class="mc-btn mc-btn-delete" name="circle_delete" value="<?= $circle['circle_id'] ?>">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:24px;">
                <a href="create_circle.php" class="mc-empty-link">+ Create New Circle</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="mc-toast" id="mcToast">✓ Deleted successfully</div>

<script>
document.querySelectorAll('.mc-tab').forEach(tab => {
    tab.addEventListener('click', function () {
        document.querySelectorAll('.mc-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.mc-panel').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('panel-' + this.dataset.tab).classList.add('active');
        history.replaceState(null, '', '?tab=' + this.dataset.tab);
    });
});

<?php if (isset($_GET['deleted'])): ?>
    const toast = document.getElementById('mcToast');
    toast.style.display = 'block';
    setTimeout(() => toast.style.display = 'none', 3000);
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>