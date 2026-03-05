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

.user-option,
.circle-option {
    padding: 6px;
    cursor: pointer;
}

.user-option.selected,
.circle-option.selected {
    background: #1f5077;
    color: white;
    border-radius: 4px;
}

#userList,
#circleList {
    max-height: 150px;
    overflow-y: auto;
}

</style>

</head>


<body>

<div id="calendar"></div>
<div id="modalOverlay"></div>


<div id="eventModal">

<h3>Create Event</h3>

<input type="hidden" id="eventId">

<label>Title</label>
<input type="text" id="eventTitle">

<label>Time</label>
<input type="time" id="eventTime">

<label>Description</label>
<textarea id="eventDescription"></textarea>


<div id="inviteSection">

<h4>Invite</h4>

<input type="text" id="userSearch" placeholder="Search users...">


<h5>Users</h5>

<div id="userList">

<?php

$stmt = $conn->prepare("
    SELECT id, username
    FROM users
    WHERE id != ?
");

$stmt->execute([$_SESSION['user']['id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user):
?>

<div class="user-option" data-id="<?= $user['id'] ?>">
    👤 <?= htmlspecialchars($user['username']) ?>
</div>

<?php endforeach; ?>

</div>



<h5>Circles</h5>

<div id="circleList">

<?php

$circles = $conn->prepare("
    SELECT circle_id, name
    FROM circle
    WHERE uid = ?
");

$circles->execute([$_SESSION['user']['id']]);

foreach ($circles as $circle):
?>

<div class="circle-option" data-id="<?= $circle['circle_id'] ?>">
    👥 <?= htmlspecialchars($circle['name']) ?>
</div>

<?php endforeach; ?>

</div>

</div>



<div id="invitedListSection" style="display:none; margin-top:15px;">
    <h4>Invited Users</h4>
    <div id="invitedUsersList"></div>
</div>


<div id="inviteActions" style="display:none;">
    <button id="acceptInvite">Accept</button>
    <button id="declineInvite">Decline</button>
</div>


<button id="saveEvent">Save</button>
<button id="deleteEvent">Delete</button>
<button id="cancelEvent">Cancel</button>

</div>



<script>

document.addEventListener('DOMContentLoaded', function () {

let selectedDate = null;
let editingEventId = null;

let selectedUsers = [];
let selectedCircles = [];

const calendar = new FullCalendar.Calendar(
    document.getElementById('calendar'),
{
    initialView: 'dayGridMonth',
    height: 'auto',
    events: 'load_events.php',


    dateClick: function(info) {

        selectedDate = info.dateStr;
        editingEventId = null;

        resetModal();
        openModal();
    },


    eventClick: function(info) {

        const event = info.event;

        editingEventId = event.id;
        selectedDate = event.startStr.split('T')[0];

        resetModal();

        document.getElementById('eventId').value = event.id;
        document.getElementById('eventTitle').value = event.title;
        document.getElementById('eventDescription').value =
            event.extendedProps.description || '';

        if (event.start) {

            let hours = event.start.getHours().toString().padStart(2, '0');
            let minutes = event.start.getMinutes().toString().padStart(2, '0');

            document.getElementById('eventTime').value =
                (hours === '00' && minutes === '00')
                ? ''
                : `${hours}:${minutes}`;
        }


        if (event.extendedProps.isOwner) {

            const invitedSection =
                document.getElementById('invitedListSection');

            const list =
                document.getElementById('invitedUsersList');

            list.innerHTML = '';

            const invites = event.extendedProps.inviteList || [];

            if (invites.length === 0) {

                list.innerHTML = "<div>No one invited yet.</div>";

            } else {

                invites.forEach(invite => {

                    list.innerHTML += `
                        <div>
                            ${invite.username}
                            <span class="status-${invite.status}">
                                (${invite.status})
                            </span>
                        </div>
                    `;

                });

            }

            invitedSection.style.display = 'block';
        }


        if (event.extendedProps.status === 'pending') {

            document.getElementById('inviteActions').style.display = 'block';
            document.getElementById('saveEvent').style.display = 'none';
            document.getElementById('deleteEvent').style.display = 'none';

        } else {

            document.getElementById('inviteActions').style.display = 'none';
            document.getElementById('saveEvent').style.display = 'inline-block';
            document.getElementById('deleteEvent').style.display = 'inline-block';
        }

        openModal();
    }

});

calendar.render();



document.querySelectorAll('.user-option').forEach(el => {

    el.addEventListener('click', function () {

        const id = this.dataset.id;

        if (this.classList.contains('selected')) {

            this.classList.remove('selected');
            selectedUsers = selectedUsers.filter(u => u !== id);

        } else {

            this.classList.add('selected');
            selectedUsers.push(id);
        }

    });

});



document.querySelectorAll('.circle-option').forEach(el => {

    el.addEventListener('click', function () {

        const id = this.dataset.id;

        if (this.classList.contains('selected')) {

            this.classList.remove('selected');
            selectedCircles = selectedCircles.filter(c => c !== id);

        } else {

            this.classList.add('selected');
            selectedCircles.push(id);
        }

    });

});



document.getElementById('userSearch').addEventListener('keyup', function () {

    const search = this.value.toLowerCase();

    document.querySelectorAll('#userList .user-option').forEach(user => {

        const name = user.textContent.toLowerCase();

        if (name.includes(search)) {
            user.style.display = 'block';
        } else {
            user.style.display = 'none';
        }

    });

});



function resetModal() {

    document.getElementById('eventTitle').value = '';
    document.getElementById('eventTime').value = '';
    document.getElementById('eventDescription').value = '';

    document.getElementById('invitedListSection').style.display = 'none';
    document.getElementById('invitedUsersList').innerHTML = '';
    document.getElementById('inviteActions').style.display = 'none';

    selectedUsers = [];
    selectedCircles = [];

    document.querySelectorAll('.user-option')
        .forEach(el => el.classList.remove('selected'));

    document.querySelectorAll('.circle-option')
        .forEach(el => el.classList.remove('selected'));
}



function openModal() {

    document.getElementById('eventModal').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';

}


function closeModal() {

    document.getElementById('eventModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';

}



document.getElementById('cancelEvent').onclick = closeModal;



document.getElementById('saveEvent').onclick = function () {

    const title =
        document.getElementById('eventTitle').value.trim();

    const time =
        document.getElementById('eventTime').value;

    const description =
        document.getElementById('eventDescription').value.trim();

    if (!title || !selectedDate) return;

    const url =
        editingEventId
        ? 'update_event.php'
        : 'add_event.php';


    fetch(url, {

        method: 'POST',

        headers: {
            'Content-Type': 'application/json'
        },

        body: JSON.stringify({
            id: editingEventId,
            title,
            date: selectedDate,
            time,
            description,
            invitees: selectedUsers,
            circles: selectedCircles
        })

    })

    .then(res => res.json())
    .then(() => {

        calendar.refetchEvents();
        closeModal();

    });

};



document.getElementById('deleteEvent').onclick = function () {

    if (!editingEventId) return;

    if (!confirm("Delete this event?")) return;

    fetch('delete_event.php', {

        method: 'POST',

        headers: {
            'Content-Type': 'application/json'
        },

        body: JSON.stringify({
            id: editingEventId
        })

    })

    .then(() => {

        calendar.refetchEvents();
        closeModal();

    });

};



document.getElementById('acceptInvite').onclick = function () {

    fetch('respond_invite.php', {

        method: 'POST',

        headers: {
            'Content-Type': 'application/json'
        },

        body: JSON.stringify({
            id: editingEventId,
            status: 'accepted'
        })

    })

    .then(() => {

        calendar.refetchEvents();
        closeModal();

    });

};



document.getElementById('declineInvite').onclick = function () {

    fetch('respond_invite.php', {

        method: 'POST',

        headers: {
            'Content-Type': 'application/json'
        },

        body: JSON.stringify({
            id: editingEventId,
            status: 'declined'
        })

    })

    .then(() => {

        calendar.refetchEvents();
        closeModal();

    });

};

});

</script>


<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>