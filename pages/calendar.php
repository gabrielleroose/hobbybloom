<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar</title>

    <link href="../css/style.css" rel="stylesheet"> 
    <link href="../css/nav.css" rel="stylesheet">


    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

</head>

<body>

    <?php include 'base.php'; ?>

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
                // Clear previous values when opening a new date
                document.getElementById('eventTitle').value = "";
                document.getElementById('eventTime').value = "";
                document.getElementById('eventDescription').value = "";
                
                document.getElementById('eventModal').style.display = 'block';
                document.getElementById('modalOverlay').style.display = 'block';
            },
            eventClick: function(info){
                alert(info.event.title + "\n\n" + (info.event.extendedProps.description || "No description"));
            }
        });

        calendar.render();

        // Close Modal
        document.getElementById('cancelEvent').onclick = function () {
            document.getElementById('eventModal').style.display = 'none';
            document.getElementById('modalOverlay').style.display = 'none';
        };

        // Save Event
        document.getElementById('saveEvent').onclick = function () {
            // 1. Grab values and trim whitespace
            const titleVal = document.getElementById('eventTitle').value.trim();
            const timeVal = document.getElementById('eventTime').value;
            const descVal = document.getElementById('eventDescription').value.trim();

            // 2. Debugging Check: If you see "" in the console, the HTML ID is wrong
            console.log("Attempting Save -> Title:", titleVal, "Time:", timeVal, "Date:", selectedDate);

            // 3. Specific Validation
            if (!selectedDate) {
                alert("Please click a date on the calendar first!");
                return;
            }
            if (!titleVal || !timeVal) {
                alert("Wait! You forgot to enter a Title or a Time.");
                return;
            }

            // 4. Send to PHP
            fetch('add_event.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title: titleVal,
                    date: selectedDate,
                    time: timeVal,
                    description: descVal
                })
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Server Error');
                return data;
            })
            .then(data => {
                console.log("Saved Successfully:", data);
                calendar.refetchEvents(); // Refresh the calendar view
                
                // Close and reset
                document.getElementById('eventModal').style.display = 'none';
                document.getElementById('modalOverlay').style.display = 'none';
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert("Database Error: " + error.message);
            });
        };
    });
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>


</body>
</html>
