<?php 
// meetings_calendar.php

// 1. Start output buffering and session
ob_start(); 
session_start();

// 2. Set the specific page title for the layout
$page_title = "Meeting Calendar View";

// 3. Include necessary files and database connection
// include 'includes/auth_check.php'; 
include 'api/db.php'; 

// --- 4. DATA FETCHING AND PREPARATION FOR CALENDAR ---
$meetings_array = [];
$meetings_for_calendar = [];

// Fetch ALL meetings, as a calendar needs all relevant data to display.
// We only fetch essential fields: id, subject, date_time, and status.
$sql = "
    SELECT 
        m.id, m.subject, m.date_time, m.type, m.status
    FROM meetings m
    ORDER BY m.date_time ASC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($meeting = $result->fetch_assoc()) {
        $meetings_array[] = $meeting; 
    }
}
$conn->close();

// 5. Transform PHP data into FullCalendar's required JSON format (Event Objects)
foreach ($meetings_array as $meeting) {
    // Determine color based on status
    $color = match ($meeting['status']) {
        'Completed' => '#10B981', // Green
        'Scheduled' => '#3B82F6', // Blue
        'Canceled' => '#EF4444',  // Red
        default => '#6B7280',     // Gray
    };

    // FullCalendar expects an array of event objects
    $meetings_for_calendar[] = [
        'id' => $meeting['id'],
        'title' => $meeting['subject'] . ' (' . $meeting['type'] . ')',
        'start' => $meeting['date_time'],
        'color' => $color,
        'url' => 'edit_meeting.php?id=' . $meeting['id'] // Link to edit the meeting
    ];
}

// Convert the PHP array to a JSON string for JavaScript
$json_events = json_encode($meetings_for_calendar);
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Meeting Calendar</h1>
        <p class="text-gray-500 mt-1">Visually plan and track all scheduled interactions.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <a href="meetings.php" class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="list-collapse" class="w-4 h-4 inline mr-1"></i> Table View
        </a>
        <a href="schedule_meeting.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                             bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> Schedule Meeting
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8">
    <div id='calendar' class="h-full"></div>
</div>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        // --- CALENDAR CONFIGURATION ---
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        height: 'auto', // Adjusts height dynamically
        editable: true, // Allows dragging and resizing events
        selectable: true,
        eventClick: function(info) {
            // Clicking an event opens the edit page (URL defined in PHP JSON data)
            if (info.event.url) {
                window.open(info.event.url, "_self");
                info.jsEvent.preventDefault(); // prevents browser from following link
            }
        },
        // --- DATA SOURCE ---
        events: <?php echo $json_events; ?>,
        
        // OPTIONAL: Handling event drop (when event is dragged to a new date/time)
        eventDrop: function(info) {
            if (!confirm("Are you sure about this change in date/time?")) {
                info.revert();
            }
            // In a real application, an AJAX call would go here to update the DB:
            // updateMeetingTime(info.event.id, info.event.start);
        }
    });

    calendar.render();
});
</script>


<?php
// 7. Capture the content and store it in a variable
$page_content = ob_get_clean();

// 8. Include the master layout file
include 'includes/layout.php'; 
?>