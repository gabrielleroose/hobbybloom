<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>

    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/nav.css" rel="stylesheet">

    <?php include 'base.php'; ?>

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

    <style>
        /* ── Modal overlay ── */
        #modalOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            backdrop-filter: blur(3px);
            z-index: 900;
        }

        /* ── Modal shell ── */
        #eventModal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            width: min(560px, 95vw);
            max-height: 90vh;
            overflow-y: auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 24px 64px rgba(0,0,0,.18);
            padding: 28px 32px 24px;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        #eventModal h3 {
            margin: 0 0 20px;
            font-size: 1.2rem;
            font-weight: 700;
            color: #1a1a2e;
            letter-spacing: -.3px;
        }

        /* ── Form fields ── */
        #eventModal label {
            display: block;
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #666;
            margin: 14px 0 5px;
        }

        #eventModal input[type="text"],
        #eventModal input[type="time"],
        #eventModal textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            padding: 9px 12px;
            font-size: .93rem;
            color: #222;
            background: #fafafa;
            transition: border-color .2s;
            outline: none;
        }

        #eventModal input:focus,
        #eventModal textarea:focus {
            border-color: #1f5077;
            background: #fff;
        }

        #eventModal textarea {
            resize: vertical;
            min-height: 72px;
        }

        /* ── Invite section wrapper ── */
        .invite-section {
            margin-top: 18px;
            border: 1.5px solid #e8e8e8;
            border-radius: 10px;
            overflow: hidden;
        }

        .invite-tabs {
            display: flex;
            border-bottom: 1.5px solid #e8e8e8;
            background: #f5f5f5;
        }

        .invite-tab {
            flex: 1;
            padding: 9px 0;
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #888;
            background: none;
            border: none;
            cursor: pointer;
            transition: color .15s, background .15s;
        }

        .invite-tab.active {
            color: #1f5077;
            background: #fff;
            border-bottom: 2px solid #1f5077;
            margin-bottom: -1.5px;
        }

        .invite-panel {
            display: none;
            padding: 14px;
        }

        .invite-panel.active { display: block; }

        /* ── Search box ── */
        .user-search-wrap {
            position: relative;
        }

        .user-search-wrap input {
            width: 100%;
            box-sizing: border-box;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 12px 8px 34px;
            font-size: .9rem;
            background: #fafafa;
            outline: none;
            transition: border-color .2s;
        }

        .user-search-wrap input:focus { border-color: #1f5077; background: #fff; }

        .user-search-wrap .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: .95rem;
            pointer-events: none;
        }

        /* ── Dropdown results ── */
        .user-dropdown {
            margin-top: 6px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            background: #fff;
            max-height: 160px;
            overflow-y: auto;
            display: none;
        }

        .user-dropdown.open { display: block; }

        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: .9rem;
            color: #222;
            transition: background .12s;
        }

        .user-dropdown-item:hover { background: #f0f6fc; }

        .user-dropdown-item .badge-following {
            margin-left: auto;
            font-size: .7rem;
            background: #e3f0fa;
            color: #1f5077;
            border-radius: 20px;
            padding: 1px 7px;
            font-weight: 600;
        }

        .user-dropdown-empty {
            padding: 10px 12px;
            color: #aaa;
            font-size: .88rem;
            font-style: italic;
        }

        /* ── Following quick-list ── */
        .following-list {
            max-height: 170px;
            overflow-y: auto;
        }

        .following-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 4px;
            font-size: .9rem;
            color: #222;
            cursor: pointer;
            border-radius: 6px;
            transition: background .12s;
        }

        .following-item:hover { background: #f0f6fc; }

        .following-item input[type="checkbox"] {
            width: 15px;
            height: 15px;
            accent-color: #1f5077;
            cursor: pointer;
            flex-shrink: 0;
        }

        /* ── Circle panel ── */
        .circle-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 170px;
            overflow-y: auto;
        }

        .circle-item {
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .circle-item-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            cursor: pointer;
            background: #fafafa;
            transition: background .12s;
        }

        .circle-item-header:hover { background: #f0f6fc; }

        .circle-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .circle-name {
            flex: 1;
            font-size: .9rem;
            font-weight: 600;
            color: #222;
        }

        .circle-count {
            font-size: .78rem;
            color: #888;
        }

        .circle-toggle-btn {
            background: #1f5077;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 3px 10px;
            font-size: .75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
        }

        .circle-toggle-btn:hover { background: #174060; }
        .circle-toggle-btn.added { background: #e74c3c; }
        .circle-toggle-btn.added:hover { background: #c0392b; }

        /* ── Selected invitees chips ── */
        .selected-invitees {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 14px;
            min-height: 28px;
        }

        .invitee-chip {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #e3f0fa;
            color: #1f5077;
            border-radius: 20px;
            padding: 4px 10px 4px 10px;
            font-size: .82rem;
            font-weight: 600;
        }

        .invitee-chip .remove-chip {
            cursor: pointer;
            font-size: .85rem;
            line-height: 1;
            color: #1f5077;
            margin-left: 2px;
            opacity: .7;
            transition: opacity .15s;
        }

        .invitee-chip .remove-chip:hover { opacity: 1; }

        .selected-label {
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #666;
            margin-top: 14px;
            display: block;
        }

        /* ── Action buttons ── */
        .modal-actions {
            display: flex;
            gap: 8px;
            margin-top: 22px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 9px 20px;
            border-radius: 8px;
            border: none;
            font-size: .88rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s, transform .1s;
        }

        .btn:active { transform: scale(.97); }
        .btn-primary { background: #1f5077; color: #fff; }
        .btn-primary:hover { background: #174060; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .btn-danger:hover { background: #c0392b; }
        .btn-ghost { background: #f0f0f0; color: #555; }
        .btn-ghost:hover { background: #e0e0e0; }
        .btn-success { background: #27ae60; color: #fff; }
        .btn-success:hover { background: #1e8449; }

        #inviteActions { display: flex; gap: 8px; margin-top: 16px; }

        /* ── Scrollbar styling ── */
        .user-dropdown::-webkit-scrollbar,
        .following-list::-webkit-scrollbar,
        .circle-list::-webkit-scrollbar { width: 5px; }
        .user-dropdown::-webkit-scrollbar-thumb,
        .following-list::-webkit-scrollbar-thumb,
        .circle-list::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
    </style>
</head>

<body class="calendar-body">

<div id="calendar"></div>

<div id="modalOverlay"></div>

<div id="eventModal">
    <h3 id="modalTitle">Create Event</h3>

    <input type="hidden" id="eventId">

    <label>Title</label>
    <input type="text" id="eventTitle" placeholder="Event name">

    <label>Time</label>
    <input type="time" id="eventTime">

    <label>Location</label>
    <input type="text" id="eventLocation" placeholder="Where?">

    <label>Description</label>
    <textarea id="eventDescription" placeholder="Details..."></textarea>

    <!-- ══ INVITE SECTION (owner only) ══ -->
    <div id="inviteSection">

        <label style="margin-top:18px;">Invite People</label>

        <div class="invite-section">
            <!-- Tabs -->
            <div class="invite-tabs">
                <button class="invite-tab active" data-panel="search">Search</button>
                <button class="invite-tab" data-panel="following">Following</button>
                <button class="invite-tab" data-panel="circles">Circles</button>
            </div>

            <!-- Search panel -->
            <div class="invite-panel active" id="panel-search">
                <div class="user-search-wrap">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="userSearchInput" placeholder="Search by username…" autocomplete="off">
                </div>
                <div class="user-dropdown" id="userDropdown"></div>
            </div>

            <!-- Following panel -->
            <div class="invite-panel" id="panel-following">
                <div class="following-list" id="followingList">
                    <div class="user-dropdown-empty">Loading…</div>
                </div>
            </div>

            <!-- Circles panel -->
            <div class="invite-panel" id="panel-circles">
                <div class="circle-list" id="circleList">
                    <?php
                    try {
                        $ownedCircles = $conn->prepare("SELECT circle_id, name, color FROM circle WHERE uid = ?");
                        $ownedCircles->execute([$_SESSION['user']['id']]);
                        $owned = $ownedCircles->fetchAll(PDO::FETCH_ASSOC);

                        $joinedCircles = $conn->prepare("
                            SELECT c.circle_id, c.name, c.color
                            FROM circle c
                            INNER JOIN circle_members cm ON c.circle_id = cm.circle_id
                            WHERE cm.user_id = ?
                        ");
                        $joinedCircles->execute([$_SESSION['user']['id']]);
                        $joined = $joinedCircles->fetchAll(PDO::FETCH_ASSOC);

                        $allCircles = [];
                        $addedIds = [];
                        foreach (array_merge($owned, $joined) as $c) {
                            if (!in_array($c['circle_id'], $addedIds)) {
                                $allCircles[] = $c;
                                $addedIds[] = $c['circle_id'];
                            }
                        }

                        // Get member counts
                        $countStmt = $conn->prepare("SELECT COUNT(*) FROM circle_members WHERE circle_id = ?");

                        foreach ($allCircles as $c) {
                            $countStmt->execute([$c['circle_id']]);
                            $memberCount = $countStmt->fetchColumn();
                            $color = htmlspecialchars($c['color'] ?? '#1f5077');
                            echo "
                            <div class='circle-item' data-circle-id='{$c['circle_id']}'>
                                <div class='circle-item-header'>
                                    <span class='circle-dot' style='background:{$color}'></span>
                                    <span class='circle-name'>" . htmlspecialchars($c['name']) . "</span>
                                    <span class='circle-count'>{$memberCount} members</span>
                                    <button class='circle-toggle-btn' data-circle-id='{$c['circle_id']}' data-circle-name='" . htmlspecialchars($c['name']) . "'>Add All</button>
                                </div>
                            </div>";
                        }

                        if (empty($allCircles)) {
                            echo "<div class='user-dropdown-empty'>You're not in any circles.</div>";
                        }

                    } catch (Exception $e) {
                        echo "<div class='user-dropdown-empty'>Error loading circles.</div>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Selected invitees chips -->
        <span class="selected-label" id="selectedLabel" style="display:none;">Invited</span>
        <div class="selected-invitees" id="selectedInvitees"></div>

    </div>

    <!-- Invite accept/decline (non-owner pending) -->
    <div id="inviteActions" style="display:none;">
        <button class="btn btn-success" id="acceptInvite">✓ Accept</button>
        <button class="btn btn-danger" id="declineInvite">✗ Decline</button>
    </div>

        <button id="saveEvent">Save</button>
        <button id="deleteEvent" style="display:none;">Delete</button>
        <button id="cancelEvent">Cancel</button>
    </div>
    <button onclick="openReportModal(<?= $module_id ?>)">Report</button> 
    <div id="reportModal" class="modal">
    <div class="modal-content">
        <h3>Report Module</h3>
        <form id="reportForm">
            <input type="hidden" name="module_id" id="reportModuleId">
            <textarea name="reason" placeholder="Describe the issue..." required></textarea>
            <button type="submit">Submit Report</button>
            <button type="button" onclick="closeReportModal()">Cancel</button>
        </form>
    </div>
</div>

    <div id="inviteActions" style="display:none;">
        <button id="acceptInvite">Accept</button>
        <button id="declineInvite">Decline</button>
    </div>

    <button id="saveEvent">Save</button>
    <button id="deleteEvent" style="display:none;">Delete</button>
    <button id="cancelEvent">Cancel</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── State ──────────────────────────────────────────────
    let selectedDate    = null;
    let editingEventId  = null;
    let followingLoaded = false;

    // Map: userId → username  (tracks who's invited)
    const invitedUsers = new Map();

    // Track circle → [userIds] so we can remove them if "Remove All" clicked
    const circleUserMap = new Map();

    // ── Calendar ───────────────────────────────────────────
    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        events: 'load_events.php',
        dateClick(info)  { openCreateModal(info.dateStr); },
        eventClick(info) { openEditModal(info.event); }
    });

    calendar.render();

    // ── Tab switching ──────────────────────────────────────
    document.querySelectorAll('.invite-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.invite-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.invite-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('panel-' + this.dataset.panel).classList.add('active');

            if (this.dataset.panel === 'following' && !followingLoaded) {
                loadFollowing();
            }
        });
    });

    // ── Load following list ────────────────────────────────
    function loadFollowing() {
        followingLoaded = true;
        fetch('search_users.php?mode=following')
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('followingList');
                if (!data.success || !data.users.length) {
                    list.innerHTML = '<div class="user-dropdown-empty">You\'re not following anyone yet.</div>';
                    return;
                }
                list.innerHTML = '';
                data.users.forEach(u => {
                    const item = document.createElement('label');
                    item.className = 'following-item';
                    item.innerHTML = `
                        <input type="checkbox" data-uid="${u.id}" data-username="${u.username}">
                        <span>${escapeHtml(u.username)}</span>
                    `;
                    item.querySelector('input').addEventListener('change', function () {
                        if (this.checked) addInvitee(this.dataset.uid, this.dataset.username);
                        else removeInvitee(this.dataset.uid);
                    });
                    list.appendChild(item);
                });
            });
    }

    // ── User search ────────────────────────────────────────
    let searchTimeout = null;
    const searchInput  = document.getElementById('userSearchInput');
    const userDropdown = document.getElementById('userDropdown');

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (!q) { userDropdown.classList.remove('open'); return; }

        searchTimeout = setTimeout(() => {
            fetch('search_users.php?mode=search&q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    userDropdown.innerHTML = '';
                    if (!data.success || !data.users.length) {
                        userDropdown.innerHTML = '<div class="user-dropdown-empty">No users found.</div>';
                    } else {
                        data.users.forEach(u => {
                            const item = document.createElement('div');
                            item.className = 'user-dropdown-item';
                            item.innerHTML = `
                                <span>${escapeHtml(u.username)}</span>
                                ${u.is_following == 1 ? '<span class="badge-following">Following</span>' : ''}
                            `;
                            item.addEventListener('click', () => {
                                addInvitee(u.id, u.username);
                                searchInput.value = '';
                                userDropdown.classList.remove('open');
                            });
                            userDropdown.appendChild(item);
                        });
                    }
                    userDropdown.classList.add('open');
                });
        }, 280);
    });

    // Close dropdown on outside click
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.user-search-wrap') && !e.target.closest('#userDropdown')) {
            userDropdown.classList.remove('open');
        }
    });

    // ── Circle bulk-add ────────────────────────────────────
    document.getElementById('circleList').addEventListener('click', function (e) {
        const btn = e.target.closest('.circle-toggle-btn');
        if (!btn) return;

        const circleId   = btn.dataset.circleId;
        const circleName = btn.dataset.circleName;
        const isAdded    = btn.classList.contains('added');

        if (isAdded) {
            // Remove all users added via this circle
            const uids = circleUserMap.get(circleId) || [];
            uids.forEach(uid => removeInvitee(uid));
            circleUserMap.delete(circleId);
            btn.textContent = 'Add All';
            btn.classList.remove('added');
        } else {
            // Fetch members and add them
            fetch('search_users.php?mode=circle_members&circle_id=' + encodeURIComponent(circleId))
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const addedNow = [];
                    data.users.forEach(u => {
                        addInvitee(u.id, u.username);
                        addedNow.push(String(u.id));
                    });
                    circleUserMap.set(circleId, addedNow);
                    btn.textContent = 'Remove All';
                    btn.classList.add('added');
                });
        }
    });

    // ── Chip management ───────────────────────────────────
    function addInvitee(uid, username) {
        uid = String(uid);
        if (invitedUsers.has(uid)) return;
        invitedUsers.set(uid, username);
        renderChips();
        syncFollowingCheckboxes();
    }

    function removeInvitee(uid) {
        uid = String(uid);
        invitedUsers.delete(uid);
        renderChips();
        syncFollowingCheckboxes();

        // If this user was part of a circle bulk-add, reflect that the circle is "partial"
        circleUserMap.forEach((uids, circleId) => {
            if (uids.includes(uid)) {
                // Mark button as no longer fully added
                const btn = document.querySelector(`.circle-toggle-btn[data-circle-id="${circleId}"]`);
                if (btn) {
                    btn.textContent = 'Add All';
                    btn.classList.remove('added');
                }
                circleUserMap.delete(circleId);
            }
        });
    }

    function renderChips() {
        const container     = document.getElementById('selectedInvitees');
        const selectedLabel = document.getElementById('selectedLabel');
        container.innerHTML = '';
        if (invitedUsers.size === 0) {
            selectedLabel.style.display = 'none';
            return;
        }
        selectedLabel.style.display = 'block';
        invitedUsers.forEach((username, uid) => {
            const chip = document.createElement('div');
            chip.className   = 'invitee-chip';
            chip.dataset.uid = uid;
            chip.innerHTML   = `
                <span>${escapeHtml(username)}</span>
                <span class="remove-chip" title="Remove">✕</span>
            `;
            chip.querySelector('.remove-chip').addEventListener('click', () => removeInvitee(uid));
            container.appendChild(chip);
        });
    }

    function syncFollowingCheckboxes() {
        document.querySelectorAll('#followingList input[type="checkbox"]').forEach(cb => {
            cb.checked = invitedUsers.has(String(cb.dataset.uid));
        });
    }

    // ── Modal helpers ──────────────────────────────────────
    function openCreateModal(dateStr) {
        selectedDate   = dateStr;
        editingEventId = null;
        resetModal();
        document.getElementById('modalTitle').textContent = 'Create Event';
        document.getElementById('inviteSection').style.display = 'block';
        document.getElementById('saveEvent').style.display     = 'inline-block';
        showModal();
    }

    function openEditModal(event) {
        editingEventId = event.id;
        selectedDate   = event.startStr.split('T')[0];
        resetModal();

        document.getElementById('modalTitle').textContent         = 'Edit Event';
        document.getElementById('eventId').value                  = event.id;
        document.getElementById('eventTitle').value               = event.title;
        document.getElementById('eventTime').value                = event.allDay ? '' : (event.startStr.split('T')[1] || '').substring(0, 5);
        document.getElementById('eventLocation').value            = event.extendedProps.location    || '';
        document.getElementById('eventDescription').value         = event.extendedProps.description || '';

        if (event.extendedProps.isOwner) {
            document.getElementById('saveEvent').style.display   = 'inline-block';
            document.getElementById('deleteEvent').style.display = 'inline-block';
            document.getElementById('inviteSection').style.display = 'block';

            // Pre-populate chips from existing invite list
            (event.extendedProps.inviteList || []).forEach(inv => {
                if (inv.id && inv.username) addInvitee(inv.id, inv.username);
            });

        } else if (event.extendedProps.status === 'pending') {
            document.getElementById('inviteActions').style.display = 'flex';
        }

        showModal();
    }

    function resetModal() {
        document.getElementById('eventId').value          = '';
        document.getElementById('eventTitle').value       = '';
        document.getElementById('eventTime').value        = '';
        document.getElementById('eventLocation').value    = '';
        document.getElementById('eventDescription').value = '';

        invitedUsers.clear();
        circleUserMap.clear();
        renderChips();
        syncFollowingCheckboxes();

        // Reset circle buttons
        document.querySelectorAll('.circle-toggle-btn').forEach(btn => {
            btn.textContent = 'Add All';
            btn.classList.remove('added');
        });

        // Reset search
        document.getElementById('userSearchInput').value = '';
        userDropdown.classList.remove('open');
        userDropdown.innerHTML = '';

        // Reset following checkboxes (will re-sync when loaded)
        followingLoaded = false;
        document.getElementById('followingList').innerHTML = '<div class="user-dropdown-empty">Loading…</div>';

        // Reset tab to Search
        document.querySelectorAll('.invite-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.invite-panel').forEach(p => p.classList.remove('active'));
        document.querySelector('.invite-tab[data-panel="search"]').classList.add('active');
        document.getElementById('panel-search').classList.add('active');

        document.getElementById('saveEvent').style.display    = 'none';
        document.getElementById('deleteEvent').style.display  = 'none';
        document.getElementById('inviteSection').style.display = 'none';
        document.getElementById('inviteActions').style.display = 'none';
    }

    function showModal() {
        document.getElementById('eventModal').style.display   = 'block';
        document.getElementById('modalOverlay').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('eventModal').style.display   = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
    }

    document.getElementById('modalOverlay').addEventListener('click', closeModal);
    document.getElementById('cancelEvent').addEventListener('click', closeModal);

    // ── Save ───────────────────────────────────────────────
    document.getElementById('saveEvent').addEventListener('click', function () {
        const title       = document.getElementById('eventTitle').value.trim();
        const time        = document.getElementById('eventTime').value;
        const location    = document.getElementById('eventLocation').value.trim();
        const description = document.getElementById('eventDescription').value.trim();

        if (!title || !selectedDate) return;

        const invitees = Array.from(invitedUsers.keys());
        const url      = editingEventId ? 'update_event.php' : 'add_event.php';

        fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                id: editingEventId,
                title, date: selectedDate, time,
                location, description,
                invitees,
                circles: [] // circles are already resolved to user IDs client-side
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { calendar.refetchEvents(); closeModal(); }
            else alert(data.error || 'Failed to save event.');
        });
    });

    // ── Delete ─────────────────────────────────────────────
    document.getElementById('deleteEvent').addEventListener('click', function () {
        if (!editingEventId || !confirm('Delete this event?')) return;
        fetch('delete_event.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ id: editingEventId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { calendar.refetchEvents(); closeModal(); }
            else alert(data.error || 'Failed to delete.');
        });
    });

    // ── Accept / Decline invite ────────────────────────────
    document.getElementById('acceptInvite').addEventListener('click', function () {
        fetch('respond_invite.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ id: editingEventId, status: 'accepted' })
        }).then(() => { calendar.refetchEvents(); closeModal(); });
    });

    document.getElementById('declineInvite').addEventListener('click', function () {
        fetch('respond_invite.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ id: editingEventId, status: 'declined' })
        }).then(() => { calendar.refetchEvents(); closeModal(); });
    });

    // ── Utility ────────────────────────────────────────────
    function escapeHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>