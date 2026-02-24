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

</head>

<body>

<div id="calendar"></div>


<div id="modalOverlay"></div>


<div id="eventModal">
    <h3>Create Event</h3>

    <label>Title</label>
    <input type="text" id="eventTitle">

    <label>Time</label>
    <input type="time" id="eventTime">

    <label>Description</label>
    <textarea id="eventDescription"></textarea>

    <button id="saveEvent">Save</button>
    <button id="cancelEvent">Cancel</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    let selectedDate = null;
    let editingEventId = null;
    let isInvite = false;

    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',

        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('load_events.php')
                .then(res => res.json())
                .then(data => {
                    console.log('Events fetched:', data); // debug
                    successCallback(data);
                })
                .catch(err => {
                    console.error('Failed to fetch events:', err);
                    failureCallback(err);
                });
        },

        dateClick: function(info) {
            selectedDate = info.dateStr;
            editingEventId = null;
            isInvite = false;

            // Reset modal
            document.getElementById('eventId').value = "";
            document.getElementById('eventTitle').value = "";
            document.getElementById('eventTime').value = "";
            document.getElementById('eventLocation').value = "";
            document.getElementById('eventDescription').value = "";
            document.getElementById('eventInvitees').selectedIndex = -1;

            document.getElementById('deleteEvent').style.display = 'none';
            document.getElementById('inviteActions').style.display = 'none';
            document.getElementById('saveEvent').style.display = 'inline-block';

            document.getElementById('eventModal').style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        },

        eventClick: function(info) {
            const event = info.event;
            editingEventId = event.id;

            document.getElementById('eventId').value = event.id;
            document.getElementById('eventTitle').value = event.title;
            document.getElementById('eventLocation').value = event.extendedProps.location || "";
            document.getElementById('eventDescription').value = event.extendedProps.description || "";

            // Handle time safely
            if (event.start) {
                let hours = event.start.getHours().toString().padStart(2, '0');
                let minutes = event.start.getMinutes().toString().padStart(2, '0');
                document.getElementById('eventTime').value = (hours === '00' && minutes === '00') ? '' : `${hours}:${minutes}`;
            } else {
                document.getElementById('eventTime').value = '';
            }

            // Check if this is an invite for current user
            if(event.extendedProps.status === 'pending') {
                isInvite = true;
                document.getElementById('inviteActions').style.display = 'block';
                document.getElementById('saveEvent').style.display = 'none';
                document.getElementById('deleteEvent').style.display = 'none';
            } else {
                isInvite = false;
                document.getElementById('inviteActions').style.display = 'none';
                document.getElementById('saveEvent').style.display = 'inline-block';
                document.getElementById('deleteEvent').style.display = 'inline-block';
            }

            document.getElementById('eventModal').style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        }
    });

    calendar.render();

    // Modal functions
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
        const invitees = Array.from(document.getElementById('eventInvitees').selectedOptions).map(o => o.value);

        if (!title || !selectedDate) return;

        const url = editingEventId ? 'update_event.php' : 'add_event.php';

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
            if(data.error){
                alert(data.error);
            } else {
                calendar.refetchEvents();
                closeModal();
            }
        })
        .catch(err => console.error('Save event failed:', err));
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
            if(data.error){
                alert(data.error);
            } else {
                calendar.refetchEvents();
                closeModal();
            }
        })
        .catch(err => console.error('Delete event failed:', err));
    };

    document.getElementById('acceptInvite').onclick = function() {
        fetch('respond_invite.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id: editingEventId, status: 'accepted'})
        })
        .then(() => {
            calendar.refetchEvents();
            closeModal();
        });
    };

    document.getElementById('declineInvite').onclick = function() {
        fetch('respond_invite.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id: editingEventId, status: 'declined'})
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
