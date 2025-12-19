<?php 
// meetings.php

// 1. Start output buffering and session
ob_start(); 
session_start();

// 2. Set the specific page title for the layout
$page_title = "Meetings";

// 3. Include necessary files and database connection
// include 'includes/auth_check.php'; 
include 'api/db.php'; 

/**
 * Helper function to generate a table row for a meeting.
 * NOTE: This function should ideally be defined in layout.php to avoid redeclaration errors.
 */
if (!function_exists('generate_meeting_row')) {
    function generate_meeting_row($id, $subject, $contact, $date, $time, $type, $status) {
        // PHP 8.0+ match expression for status colors
        $status_class = match ($status) {
            'Scheduled' => 'bg-blue-100 text-blue-800',
            'Completed' => 'bg-green-100 text-green-800',
            'Canceled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };

        $delete_button = '';
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $delete_button = '
            <a href="api/delete_meeting_process.php?id=' . htmlspecialchars($id) . '" 
               class="text-red-600 hover:text-red-900"
               onclick="return confirm(\'Are you sure you want to delete this meeting?\')">
               Delete
            </a>';
        }

        return '
            <tr class="hover:bg-gray-50 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">' . htmlspecialchars($subject) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 hover:text-blue-800 cursor-pointer">' . htmlspecialchars($contact) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($date) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($time) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($type) . '</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $status_class . '">'
                        . htmlspecialchars($status) . '
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="edit_meeting.php?id=' . $id . '" class="text-blue-600 hover:text-blue-900 ml-2">Edit</a>
                    ' . $delete_button . '
                </td>
            </tr>
        ';
    }
}


// --- 4. DATA FETCHING AND PAGINATION LOGIC ---

// 1. Define pagination variables
$limit = 10; // Meetings per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// 2. Fetch Total Count
$count_result = $conn->query("SELECT COUNT(*) AS total FROM meetings");
$total_meetings = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_meetings / $limit);

// 3. Calculate OFFSET and adjust page if necessary
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;
if ($offset < 0) $offset = 0; // Prevent negative offset if $total_meetings is 0

// 4. Build the SQL query with LIMIT and OFFSET
$meetings = []; 
$meeting_rows = '';
$sql = "
    SELECT 
        m.id, m.subject, m.date_time, m.type, m.status,
        c.name AS contact_name
    FROM meetings m
    LEFT JOIN contacts c ON m.contact_id = c.id
    ORDER BY m.date_time DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($meeting = $result->fetch_assoc()) {
        $meetings[] = $meeting; 
        
        // Format the combined date_time field into separate date and time
        $datetime = new DateTime($meeting['date_time']);
        $date = $datetime->format('Y-m-d');
        $time = $datetime->format('h:i A'); // e.g., 02:30 PM

        $meeting_rows .= generate_meeting_row(
            $meeting['id'],
            $meeting['subject'], 
            $meeting['contact_name'] ?? 'N/A', 
            $date, 
            $time, 
            $meeting['type'], 
            $meeting['status']
        );
    }
}
$conn->close();

// 5. Calculate display range for the summary line
$current_page_count = count($meetings);
$start_item = $current_page_count > 0 ? $offset + 1 : 0;
$end_item = $current_page_count > 0 ? $offset + $current_page_count : 0;

?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Meetings & Calls</h1>
        <p class="text-gray-500 mt-1">View and manage all scheduled interactions with contacts and companies.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <!-- <button class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i> Calendar View
        </button> -->
        <a href="meetings_calendar.php" class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
    <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i> Calendar View
</a>
        <a href="schedule_meeting.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                             bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> Schedule Meeting
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                if (empty($meeting_rows)) {
                    // Corrected colspan to 7
                    echo '<tr><td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No meetings scheduled.</td></tr>';
                } else {
                    echo $meeting_rows; 
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 flex justify-between items-center text-sm text-gray-600 border-t border-gray-200">
        <span>
            <?php if ($total_meetings > 0): ?>
                Showing <?= $start_item ?> to <?= $end_item ?> of <?= $total_meetings ?> meetings
            <?php else: ?>
                No meetings found.
            <?php endif; ?>
        </span>
        
        <div class="space-x-1 flex items-center">
            <?php
            // Previous Button Logic
            $prev_page = $page - 1;
            $prev_class = $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            ?>
            <a href="meetings.php?page=<?= $prev_page ?>" 
               class="px-3 py-1 border rounded-lg <?= $prev_class ?>">
                Previous
            </a>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php 
                $active_class = $i == $page ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100';
                ?>
                <a href="meetings.php?page=<?= $i ?>" 
                   class="px-3 py-1 rounded-lg <?= $active_class ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php
            // Next Button Logic
            $next_page = $page + 1;
            $next_class = $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            ?>
            <a href="meetings.php?page=<?= $next_page ?>" 
               class="px-3 py-1 border rounded-lg <?= $next_class ?>">
                Next
            </a>
        </div>
    </div>
</div>

<?php
// 4. Capture the content and store it in a variable
$page_content = ob_get_clean();

// 5. Include the master layout file
include 'includes/layout.php'; 
?>