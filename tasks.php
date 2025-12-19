<?php 
// tasks.php

// 1. Start capturing the output buffer and session
ob_start(); 
session_start();

// 2. Set the specific page title for the layout
$page_title = "Tasks";

// 3. Include necessary files and database connection
// include 'includes/auth_check.php'; 
include 'api/db.php'; 

/**
 * Helper function to generate a table row for a task.
 */
if (!function_exists('generate_task_row')) {
    function generate_task_row($id, $title, $contact, $due_date, $priority, $status) {
        // Determine priority badge color
        $priority_class = match ($priority) {
            'High' => 'bg-red-100 text-red-800',
            'Medium' => 'bg-yellow-100 text-yellow-800',
            'Low' => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };

        $delete_button = '';
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $delete_button = '
            <a href="api/delete_task_process.php?id=' . htmlspecialchars($id) . '" 
               class="text-red-600 hover:text-red-900"
               onclick="return confirm(\'Are you sure you want to delete this task?\')">
               Delete
            </a>';
        }
        
        // Determine status icon and color
        $status_color = match ($status) {
            'Completed' => 'text-green-500',
            'In Progress' => 'text-blue-500',
            default => 'text-gray-500',
        };
        $status_icon = ($status === 'Completed') ? 
            '<i data-lucide="check-circle" class="w-4 h-4 ' . $status_color . ' inline mr-1"></i>' : 
            '<i data-lucide="clock" class="w-4 h-4 ' . $status_color . ' inline mr-1"></i>';

        return '
            <tr class="hover:bg-gray-50 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">' . htmlspecialchars($title) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 hover:text-blue-800 cursor-pointer">' . htmlspecialchars($contact) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($due_date) . '</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $priority_class . '">'
                        . htmlspecialchars($priority) . '
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    ' . $status_icon . htmlspecialchars($status) . '
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="edit_task.php?id=' . $id . '" class="text-blue-600 hover:text-blue-900 ml-2">Edit</a>
                    ' . $delete_button . '
                </td>
                
            </tr>
        ';
    }
}


// --- 4. DATA FETCHING AND PAGINATION LOGIC ---

// 1. Define pagination variables
$limit = 10; // Tasks per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// 2. Fetch Total Count
$count_result = $conn->query("SELECT COUNT(*) AS total FROM tasks");
$total_tasks = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_tasks / $limit);

// 3. Calculate OFFSET and adjust page if necessary
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;
if ($offset < 0) $offset = 0; // Prevent negative offset if $total_tasks is 0

// 4. Build the SQL query with LIMIT and OFFSET
$tasks = []; 
$task_rows = '';
// Query to fetch tasks, joining with contacts to get the contact name
$sql = "
    SELECT 
        t.id, t.title, t.due_date, t.priority, t.status,
        c.name AS contact_name
    FROM tasks t
    LEFT JOIN contacts c ON t.contact_id = c.id
    ORDER BY t.due_date ASC, t.priority DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($task = $result->fetch_assoc()) {
        $tasks[] = $task; 
        
        $task_rows .= generate_task_row(
            $task['id'],
            $task['title'], 
            $task['contact_name'] ?? 'N/A', 
            $task['due_date'], 
            $task['priority'], 
            $task['status']
        );
    }
}
$conn->close();

// 5. Calculate display range for the summary line
$current_page_count = count($tasks);
$start_item = $current_page_count > 0 ? $offset + 1 : 0;
$end_item = $current_page_count > 0 ? $offset + $current_page_count : 0;

?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Sales Tasks</h1>
        <p class="text-gray-500 mt-1">Manage your to-do list, follow-ups, and sales activities.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <a href="tasks_board.php" class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
    <i data-lucide="kanban" class="w-4 h-4 inline mr-1"></i> Board View
</a>
        <a href="add_task.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                             bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> New Task
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Related Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                if (empty($task_rows)) {
                    // Adjusted colspan to 6
                    echo '<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No tasks found.</td></tr>';
                } else {
                    echo $task_rows; 
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 flex justify-between items-center text-sm text-gray-600 border-t border-gray-200">
        <span>
            <?php if ($total_tasks > 0): ?>
                Showing <?= $start_item ?> to <?= $end_item ?> of <?= $total_tasks ?> tasks
            <?php else: ?>
                No tasks found.
            <?php endif; ?>
        </span>
        
        <div class="space-x-1 flex items-center">
            <?php
            // Previous Button Logic
            $prev_page = $page - 1;
            $prev_class = $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            ?>
            <a href="tasks.php?page=<?= $prev_page ?>" 
               class="px-3 py-1 border rounded-lg <?= $prev_class ?>">
                Previous
            </a>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php 
                $active_class = $i == $page ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100';
                ?>
                <a href="tasks.php?page=<?= $i ?>" 
                   class="px-3 py-1 rounded-lg <?= $active_class ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php
            // Next Button Logic
            $next_page = $page + 1;
            $next_class = $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            ?>
            <a href="tasks.php?page=<?= $next_page ?>" 
               class="px-3 py-1 border rounded-lg <?= $next_class ?>">
                Next
            </a>
        </div>
    </div>
</div>

<?php
// 5. Capture the content
$page_content = ob_get_clean();

// 6. Include the master layout file
include 'includes/layout.php'; 
?>