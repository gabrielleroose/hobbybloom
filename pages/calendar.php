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

    <label>Invite Users</label>
    <select id="eventInvitees" multiple>
        <?php
        $users = $conn->query("SELECT id, username FROM users WHERE id != ".$_SESSION['user']['id'])->fetchAll(PDO::FETCH_ASSOC);
        foreach($users as $u){
            echo "<option value='".$u['id']."'>".$u['username']."</option>";
        }
        ?>
    </select>

    <button id="saveEvent">Save</button>
    <button id="deleteEvent" style="display:none;">Delete</button>
    <button id="cancelEvent">Cancel</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    let selectedDate = null;
    let editingEventId = null;

    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: 'load_events.php',

        dateClick: function(info) {
            selectedDate = info.dateStr;
            editingEventId = null;

            document.getElementById('eventId').value = "";
            document.getElementById('eventTitle').value = "";
            document.getElementById('eventTime').value = "";
            document.getElementById('eventDescription').value = "";
            document.getElementById('eventLocation').value = "";
            document.getElementById('eventInvitees').selectedIndex = -1;

            document.getElementById('deleteEvent').style.display = 'none';
            document.getElementById('eventModal').style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        },

        eventClick: function(info) {
            const event = info.event;
            editingEventId = event.id;
            selectedDate = event.startStr.split('T')[0];

            document.getElementById('eventId').value = event.id;
            document.getElementById('eventTitle').value = event.title;
            document.getElementById('eventTime').value = event.startStr.includes('T') ? event.startStr.split('T')[1].substring(0,5) : "";
            document.getElementById('eventDescription').value = event.extendedProps.description || "";
            document.getElementById('eventLocation').value = event.extendedProps.location || "";

            // Populate invitees if extendedProps.invitees exists
            const inviteesSelect = document.getElementById('eventInvitees');
            if(event.extendedProps.invitees){
                for(let i=0; i<inviteesSelect.options.length; i++){
                    inviteesSelect.options[i].selected = event.extendedProps.invitees.includes(parseInt(inviteesSelect.options[i].value));
                }
            } else {
                inviteesSelect.selectedIndex = -1;
            }

            document.getElementById('deleteEvent').style.display = 'inline-block';
            document.getElementById('eventModal').style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        }
    });

    calendar.render();

    const closeModal = () => {
        document.getElementById('eventModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
    };

    document.getElementById('cancelEvent').onclick = closeModal;

    document.getElementById('saveEvent').onclick = function() {
        const title = document.getElementById('eventTitle').value.trim();
        const time = document.getElementById('eventTime').value;
        const description = document.getElementById('eventDescription').value.trim();
        const location = document.getElementById('eventLocation').value.trim();

        const inviteesSelect = document.getElementById('eventInvitees');
        const invitees = Array.from(inviteesSelect.selectedOptions).map(opt => parseInt(opt.value));

        if(!title || !selectedDate) return;

        const url = editingEventId ? 'update_event.php' : 'add_event.php';

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: editingEventId,
                title,
                date: selectedDate,
                time,
                description,
                location,
                invitees
            })
        }).then(() => {
            calendar.refetchEvents();
            closeModal();
        });
    };

    document.getElementById('deleteEvent').onclick = function() {
        if(!editingEventId) return;
        if(!confirm("Delete this event?")) return;

        fetch('delete_event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: editingEventId })
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