<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>

    <link href="../css/style.css" rel="stylesheet"> 
    <link href="../css/nav.css" rel="stylesheet">

    <?php include 'base.php';?>

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
</head>

<body class="calendar-body">

<div id="calendar"></div>

<div id="modalOverlay" style="display:none;"></div>

<div id="eventModal" style="display:none;">
    <h3>Create / Edit Event</h3>

    <input type="hidden" id="eventId">

    <label>Title</label>
    <input type="text" id="eventTitle">

    <label>Time</label>
    <input type="time" id="eventTime">

    <label>Location</label>
    <input type="text" id="eventLocation">

    <label>Description</label>
    <textarea id="eventDescription"></textarea>

    <!-- INVITE SECTION -->
    <div id="inviteSection">
        <label>Invite Users</label>
        <select id="eventInvitees" multiple>
            <?php
            $users = $conn->query(
                "SELECT id, username FROM users 
                 WHERE id != ".$_SESSION['user']['id']
            )->fetchAll(PDO::FETCH_ASSOC);

            foreach($users as $u){
                echo "<option value='".$u['id']."'>".$u['username']."</option>";
            }
            ?>
        </select>
    </div>

    <div id="inviteActions" style="display:none;">
        <button id="acceptInvite">Accept</button>
        <button id="declineInvite">Decline</button>
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
document.addEventListener('DOMContentLoaded', function() {

let selectedDate = null;
let editingEventId = null;

const inviteSelect = document.getElementById('eventInvitees');

inviteSelect.addEventListener('mousedown', function(e) {
    e.preventDefault();

    const option = e.target;

    if (option.tagName === 'OPTION') {
        option.selected = !option.selected;
    }
});

const calendar = new FullCalendar.Calendar(
    document.getElementById('calendar'),
    {
        initialView: 'dayGridMonth',
        events: 'load_events.php',

        dateClick(info) {
            openCreateModal(info.dateStr);
        },

        eventClick(info) {
            openEditModal(info.event);
        }
    }
);

calendar.render();


function openCreateModal(dateStr) {

    selectedDate = dateStr;
    editingEventId = null;

    resetModal();

    document.getElementById('inviteSection').style.display = 'block';
    document.getElementById('saveEvent').style.display = 'inline-block';

    showModal();
}

function openEditModal(event) {

    editingEventId = event.id;
    selectedDate = event.startStr.split('T')[0];

    resetModal();

    document.getElementById('eventId').value = event.id;
    document.getElementById('eventTitle').value = event.title;
    document.getElementById('eventTime').value = event.allDay 
        ? "" 
        : event.startStr.split('T')[1].substring(0,5);

    document.getElementById('eventLocation').value =
        event.extendedProps.location || "";

    document.getElementById('eventDescription').value =
        event.extendedProps.description || "";

    if (event.extendedProps.isOwner) {

        document.getElementById('saveEvent').style.display = 'inline-block';
        document.getElementById('deleteEvent').style.display = 'inline-block';
        document.getElementById('inviteSection').style.display = 'block';

    } 
    else if (event.extendedProps.status === 'pending') {

        document.getElementById('inviteActions').style.display = 'block';
        document.getElementById('inviteSection').style.display = 'none';

    } 
    else {

        document.getElementById('inviteSection').style.display = 'none';
    }

    showModal();
}

function resetModal() {

    document.getElementById('eventId').value = "";
    document.getElementById('eventTitle').value = "";
    document.getElementById('eventTime').value = "";
    document.getElementById('eventLocation').value = "";
    document.getElementById('eventDescription').value = "";
    document.getElementById('eventInvitees').selectedIndex = -1;

    document.getElementById('saveEvent').style.display = 'none';
    document.getElementById('deleteEvent').style.display = 'none';
    document.getElementById('inviteActions').style.display = 'none';
}

function showModal() {
    document.getElementById('eventModal').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
}

function closeModal() {
    document.getElementById('eventModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}

document.getElementById('cancelEvent').onclick = closeModal;


document.getElementById('saveEvent').onclick = function() {

    const title = document.getElementById('eventTitle').value.trim();
    const time = document.getElementById('eventTime').value;
    const location = document.getElementById('eventLocation').value.trim();
    const description = document.getElementById('eventDescription').value.trim();
    const invitees = Array.from(
        document.getElementById('eventInvitees').selectedOptions
    ).map(o => o.value);

    if (!title || !selectedDate) return;

    const url = editingEventId 
        ? 'update_event.php' 
        : 'add_event.php';

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: editingEventId,
            title,
            date: selectedDate,
            time,
            location,
            description,
            invitees
        })
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {
            calendar.refetchEvents();
            closeModal();
        } else {
            alert(data.error || "Failed to save event.");
        }

    });
};

document.getElementById('deleteEvent').onclick = function() {

    if (!editingEventId) return;
    if (!confirm("Delete this event?")) return;

    fetch('delete_event.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: editingEventId })
    })
    .then(res => res.json())
    .then(data => {

        if (data.success) {
            calendar.refetchEvents();
            closeModal();
        } else {
            alert(data.error || "Failed to delete event.");
        }

    });
};

document.getElementById('acceptInvite').onclick = function() {

    fetch('respond_invite.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
            id: editingEventId,
            status: 'accepted'
        })
    }).then(() => {
        calendar.refetchEvents();
        closeModal();
    });
};

document.getElementById('declineInvite').onclick = function() {

    fetch('respond_invite.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
            id: editingEventId,
            status: 'declined'
        })
    }).then(() => {
        calendar.refetchEvents();
        closeModal();
    });
};

});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>