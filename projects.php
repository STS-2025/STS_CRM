<?php 
// projects.php

// 1. Start capturing the output buffer and session
ob_start(); 
session_start();

// 2. Set the specific page title for the layout
$page_title = "Projects";

// 3. Include necessary files and database connection
// include 'includes/auth_check.php';
include 'api/db.php'; 

/**
 * Helper function to generate a table row for a project.
 */
if (!function_exists('generate_project_row')) {
    function generate_project_row($id, $title, $client, $manager, $status, $progress) {
        // Determine status badge color
        $status_class = match ($status) {
            'In Progress' => 'bg-yellow-100 text-yellow-800',
            'Completed' => 'bg-green-100 text-green-800',
            'On Hold' => 'bg-gray-100 text-gray-800',
            default => 'bg-blue-100 text-blue-800',
        };
        
        // Determine progress bar color based on percentage
        $progress_color = ($progress >= 100) ? 'bg-green-500' : 
                          (($progress > 50) ? 'bg-blue-500' : 'bg-red-500');

        return '
            <tr class="hover:bg-gray-50 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                    <a href="view_project.php?id=' . htmlspecialchars($id) . '" class="text-blue-600 hover:text-blue-800">' . htmlspecialchars($title) . '</a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 hover:text-blue-800 cursor-pointer">' . htmlspecialchars($client) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($manager) . '</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $status_class . '">'
                        . htmlspecialchars($status) . '
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="w-24 bg-gray-200 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full ' . $progress_color . '" style="width: ' . htmlspecialchars($progress) . '%"></div>
                    </div>
                    <span class="text-xs text-gray-600 mt-1">' . htmlspecialchars($progress) . '% Complete</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                    <a href="edit_project.php?id=' . htmlspecialchars($id) . '" class="text-blue-600 hover:text-blue-900">Edit</a>
                    <a href="api/delete_project_process.php?id=' . htmlspecialchars($id) . '" 
                       class="text-red-600 hover:text-red-900" 
                       onclick="return confirm(\'Are you sure you want to delete this project?\')">Delete</a>
                </td>
            </tr>
        ';
    }
}


// --- 4. DATA FETCHING AND PAGINATION LOGIC ---

// 1. Define pagination variables
$limit = 10; // Projects per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// 2. Fetch Total Count
$count_result = $conn->query("SELECT COUNT(*) AS total FROM projects");
$total_projects = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_projects / $limit);

// 3. Calculate OFFSET and adjust page if necessary
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;
if ($offset < 0) $offset = 0; // Prevent negative offset if $total_projects is 0

// 4. Build the SQL query for data fetching
$project_rows = '';
$sql = "
    SELECT 
        p.id, p.title, p.status, p.progress,
        c.name AS company_name, /* <-- CORRECTED ALIAS */
        u.name AS manager_name
    FROM projects p
    LEFT JOIN companies c ON p.company_id = c.id /* <-- CORRECTED: clients -> companies, client_id -> company_id */
    LEFT JOIN users u ON p.manager_id = u.id
    ORDER BY p.created_at DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($project = $result->fetch_assoc()) {
        $project_rows .= generate_project_row(
            $project['id'],
            $project['title'], 
            $project['company_name'] ?? 'N/A', // <-- Use corrected alias
            $project['manager_name'] ?? 'Unassigned',
            $project['status'], 
            $project['progress']
        );
    }
}
$conn->close();

// 5. Calculate display range for the summary line
$current_page_count = $result ? $result->num_rows : 0;
$start_item = $current_page_count > 0 ? $offset + 1 : 0;
$end_item = $current_page_count > 0 ? $offset + $current_page_count : 0;
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Projects</h1>
        <p class="text-gray-500 mt-1">Track internal and client projects from initiation to completion.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <a href="projects_kanban.php" class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
    <i data-lucide="trello" class="w-4 h-4 inline mr-1"></i> Kanban View
</a>
        <a href="create_project.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                                 bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> New Project
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client/Company</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manager</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                if (empty($project_rows)) {
                    echo '<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No projects found.</td></tr>';
                } else {
                    echo $project_rows; 
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 flex justify-between items-center text-sm text-gray-600 border-t border-gray-200">
        <span>
            <?php if ($total_projects > 0): ?>
                Showing <?= $start_item ?> to <?= $end_item ?> of <?= $total_projects ?> projects
            <?php else: ?>
                No projects found.
            <?php endif; ?>
        </span>
        
        <div class="space-x-1 flex items-center">
            <?php
            // Previous Button Logic
            $prev_page = $page - 1;
            $prev_class = $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            ?>
            <a href="projects.php?page=<?= $prev_page ?>" 
               class="px-3 py-1 border rounded-lg <?= $prev_class ?>">
                Previous
            </a>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php 
                $active_class = $i == $page ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100';
                ?>
                <a href="projects.php?page=<?= $i ?>" 
                   class="px-3 py-1 rounded-lg <?= $active_class ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php
            // Next Button Logic
            $next_page = $page + 1;
            $next_class = $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            ?>
            <a href="projects.php?page=<?= $next_page ?>" 
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