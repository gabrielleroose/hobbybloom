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




<script>
document.addEventListener('DOMContentLoaded', function() {

    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    events: 'load_events.php',

    dateClick: function(info) {

        let title = prompt("Event Title:");
        let time = prompt("Event Time (HH:MM):");
        let description = prompt("Description:");


        if(title){

            fetch('add_event.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({
                    title: title,
                    date: info.dateStr,
                    time: time,
                    description: description
                })

            })
            .then(() => calendar.refetchEvents());
        }
    },

    eventClick: function(info){
    alert(info.event.extendedProps.description);
    }
});


    calendar.render();
});
</script>

</body>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</html>
