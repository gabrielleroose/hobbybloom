<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>

    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/nav.css" rel="stylesheet">

    <?php include 'base.php'; ?>

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
</head>

<body class="calendar-body">
<?php
$gcConnected = false;
if (isset($_SESSION['user']['id'])) {
    $gcStmt = $conn->prepare("SELECT gc_access_token FROM users WHERE id = ?");
    $gcStmt->execute([$_SESSION['user']['id']]);
    $gcRow = $gcStmt->fetch();
    $gcConnected = !empty($gcRow['gc_access_token']);
}
?>


<div id="gcToolbar">
    <?php if ($gcConnected): ?>
        <span class="gc-status-connected">&#128197; Google Calendar connected</span>
        <button id="gcSyncBtn" class="gc-btn gc-btn-sync">&#8635; Sync Now</button>
        <span id="gcSyncMsg" class="gc-sync-msg"></span>
    <?php else: ?>
        <span class="gc-status-disconnected">&#128197; Google Calendar not connected</span>
        <a href="Google_calendar_connect.php" class="gc-btn gc-btn-connect">Connect Google Calendar</a>
        <?php if (isset($_GET['gc_error'])): ?>
            <span class="gc-sync-msg gc-error">Connection failed: <?= htmlspecialchars($_GET['gc_error']) ?></span>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (isset($_GET['gc_connected'])): ?>
        <span class="gc-sync-msg gc-success">&#10003; Connected successfully!</span>
    <?php endif; ?>
</div>

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

    <div id="inviteSection">

        <label style="margin-top:18px;">Invite People</label>

        <div class="invite-section">

            <div class="invite-tabs">
                <button class="invite-tab active" data-panel="search">Search</button>
                <button class="invite-tab" data-panel="following">Following</button>
                <button class="invite-tab" data-panel="circles">Circles</button>
            </div>

            <div class="invite-panel active" id="panel-search">
                <div class="user-search-wrap">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="userSearchInput" placeholder="Search by username…" autocomplete="off">
                </div>
                <div class="user-dropdown" id="userDropdown"></div>
            </div>

            <div class="invite-panel" id="panel-following">
                <div class="following-list" id="followingList">
                    <div class="user-dropdown-empty">Loading…</div>
                </div>
            </div>

            <div class="invite-panel" id="panel-circles">
                <div class="circle-list" id="circleList">
                    <?php
                    try {
                        $ownedCircles = $conn->prepare("SELECT circle_id, name, color FROM circle WHERE uid = ?");
                        $ownedCircles->execute([$_SESSION['user']['id']]);
                        $owned = $ownedCircles->fetchAll(PDO::FETCH_ASSOC);

                        // Pull joined circles from user_profiles.hobbies instead of circle_members
                        $profileStmt = $conn->prepare("SELECT hobbies FROM user_profiles WHERE user_id = ?");
                        $profileStmt->execute([$_SESSION['user']['id']]);
                        $hobbiesStr = $profileStmt->fetchColumn();
                        $hobbiesArr = $hobbiesStr ? array_map('trim', explode(',', $hobbiesStr)) : [];

                        $joined = [];
                        if (!empty($hobbiesArr)) {
                            $placeholders = str_repeat('?,', count($hobbiesArr) - 1) . '?';
                            $joinedStmt = $conn->prepare("SELECT circle_id, name, color FROM circle WHERE name IN ($placeholders)");
                            $joinedStmt->execute($hobbiesArr);
                            $joined = $joinedStmt->fetchAll(PDO::FETCH_ASSOC);
                        }

                        $allCircles = [];
                        $addedIds = [];
                        foreach (array_merge($owned, $joined) as $c) {
                            if (!in_array($c['circle_id'], $addedIds)) {
                                $allCircles[] = $c;
                                $addedIds[] = $c['circle_id'];
                            }
                        }

                        foreach ($allCircles as $c) {
                            $color = htmlspecialchars($c['color'] ?? '#1f5077');
                            echo "
                            <div class='circle-item' data-circle-id='{$c['circle_id']}'>
                                <div class='circle-item-header'>
                                    <span class='circle-dot' style='background:{$color}'></span>
                                    <span class='circle-name'>" . htmlspecialchars($c['name']) . "</span>
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

        <span class="selected-label" id="selectedLabel" style="display:none;">Invited</span>
        <div class="selected-invitees" id="selectedInvitees"></div>

    </div>

    <div id="inviteActions" style="display:none;">
        <button class="btn btn-success" id="acceptInvite">✓ Accept</button>
        <button class="btn btn-danger" id="declineInvite">✗ Decline</button>
    </div>

    <div class="modal-actions">
        <button class="btn btn-primary" id="saveEvent" style="display:none;">Save Event</button>
        <button class="btn btn-danger" id="deleteEvent" style="display:none;">Delete</button>
        <button class="btn btn-ghost" id="cancelEvent">Cancel</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    let selectedDate    = null;
    let editingEventId  = null;
    let followingLoaded = false;

    const invitedUsers = new Map();
    const circleUserMap = new Map();

    const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        events: 'load_events.php',
        dateClick(info)  { openCreateModal(info.dateStr); },
        eventClick(info) { openEditModal(info.event); }
    });

    calendar.render();

    const gcSyncBtn = document.getElementById('gcSyncBtn');
    let googleEvents = [];

    if (gcSyncBtn) {
        gcSyncBtn.addEventListener('click', function () {
            gcSyncBtn.disabled = true;
            gcSyncBtn.textContent = '⟳ Syncing…';
            const msg = document.getElementById('gcSyncMsg');
            msg.textContent = '';
            msg.className = 'gc-sync-msg';

            fetch('Google_calendar_sync.php')
                .then(r => r.json())
                .then(data => {
                    gcSyncBtn.disabled = false;
                    gcSyncBtn.textContent = '⟳ Sync Now';
                    if (data.success) {
                        googleEvents = data.pulled || [];
                        gcSource.remove();
                        gcSource = calendar.addEventSource(googleEvents);
                        calendar.refetchEvents();
                        const total = (data.pushed || 0) + (data.updated || 0);
                        msg.className = 'gc-sync-msg gc-success';
                        msg.textContent = `✓ Synced! ${total} pushed, ${googleEvents.length} pulled.`;
                    } else if (data.error === 'not_connected') {
                        msg.className = 'gc-sync-msg gc-error';
                        msg.textContent = 'Not connected to Google Calendar.';
                    } else {
                        msg.className = 'gc-sync-msg gc-error';
                        msg.textContent = 'Sync failed: ' + (data.error || 'unknown error');
                    }
                })
                .catch(() => {
                    gcSyncBtn.disabled = false;
                    gcSyncBtn.textContent = '⟳ Sync Now';
                    document.getElementById('gcSyncMsg').textContent = 'Network error during sync.';
                });
        });
    }

    let gcSource = calendar.addEventSource([]);

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

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.user-search-wrap') && !e.target.closest('#userDropdown')) {
            userDropdown.classList.remove('open');
        }
    });

    document.getElementById('circleList').addEventListener('click', function (e) {
        const btn = e.target.closest('.circle-toggle-btn');
        if (!btn) return;

        const circleId   = btn.dataset.circleId;
        const circleName = btn.dataset.circleName;
        const isAdded    = btn.classList.contains('added');

        if (isAdded) {
            const uids = circleUserMap.get(circleId) || [];
            uids.forEach(uid => removeInvitee(uid));
            circleUserMap.delete(circleId);
            btn.textContent = 'Add All';
            btn.classList.remove('added');
        } else {
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

        circleUserMap.forEach((uids, circleId) => {
            if (uids.includes(uid)) {
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

        document.querySelectorAll('.circle-toggle-btn').forEach(btn => {
            btn.textContent = 'Add All';
            btn.classList.remove('added');
        });

        document.getElementById('userSearchInput').value = '';
        userDropdown.classList.remove('open');
        userDropdown.innerHTML = '';

        followingLoaded = false;
        document.getElementById('followingList').innerHTML = '<div class="user-dropdown-empty">Loading…</div>';

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
                circles: []
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { calendar.refetchEvents(); closeModal(); }
            else alert(data.error || 'Failed to save event.');
        });
    });

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

    function escapeHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>