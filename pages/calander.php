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

    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: 'load_events.php',

        dateClick: function(info) {
            selectedDate = info.dateStr;
            document.getElementById('eventModal').style.display = 'block';
            document.getElementById('modalOverlay').style.display = 'block';
        },

        eventClick: function(info){
            if (confirm("Delete this event?")) {

                fetch('delete_event.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: info.event.id
                    })
                })
                .then(() => {
                    info.event.remove(); // removes from calendar instantly
                });
            }
        }
        
    });

    calendar.render();

  
    document.getElementById('cancelEvent').onclick = function () {
        document.getElementById('eventModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
    };

   
    document.getElementById('saveEvent').onclick = function () {

        const title = document.getElementById('eventTitle').value;
        const time = document.getElementById('eventTime').value;
        const description = document.getElementById('eventDescription').value;

        if (!title) return;

        fetch('add_event.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                title: title,
                date: selectedDate,
                time: time,
                description: description
            })
        }).then(() => {
            calendar.refetchEvents();

            document.getElementById('eventModal').style.display = 'none';
            document.getElementById('modalOverlay').style.display = 'none';


            document.getElementById('eventTitle').value = "";
            document.getElementById('eventTime').value = "";
            document.getElementById('eventDescription').value = "";
        });
    };

});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
