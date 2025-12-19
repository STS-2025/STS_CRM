<?php 
// projects_kanban.php

ob_start(); 
session_start();

$page_title = "Projects - Kanban Board";
include 'api/db.php'; 

// Define the statuses that will become the Kanban columns
$kanban_statuses = ['Planning', 'In Progress', 'On Hold', 'Completed', 'Cancelled'];

// Initialize the array to hold projects, grouped by status
$grouped_projects = array_fill_keys($kanban_statuses, []);

// --- DATA FETCHING ---
// Fetch ALL projects with associated names (no pagination for a full board view)
$sql = "
    SELECT 
        p.id, p.title, p.status, p.progress, p.due_date,
        c.name AS company_name,
        u.name AS manager_name
    FROM projects p
    LEFT JOIN companies c ON p.company_id = c.id
    LEFT JOIN users u ON p.manager_id = u.id
    ORDER BY p.due_date ASC, p.status ASC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($project = $result->fetch_assoc()) {
        $status = $project['status'];
        // Only group projects that match our defined columns
        if (in_array($status, $kanban_statuses)) {
            $grouped_projects[$status][] = $project;
        }
    }
}
$conn->close();

/**
 * Helper function to render a single project card within the Kanban column.
 */
if (!function_exists('generate_kanban_card')) {
    function generate_kanban_card($project) {
        $progress = (int)$project['progress'];
        $progress_color = ($progress >= 100) ? 'bg-green-500' : 
                          (($progress > 50) ? 'bg-blue-500' : 'bg-red-500');
        
        // Due Date logic
        $due_date_html = 'N/A';
        $due_date_class = 'text-gray-500';

        if ($project['due_date']) {
            $due_date = new DateTime($project['due_date']);
            $today = new DateTime();
            $due_date_html = $due_date->format('M d');

            if ($progress < 100 && $due_date < $today) {
                // Past due and not complete
                $due_date_class = 'text-red-600 font-semibold';
            } elseif ($progress < 100 && $due_date <= (new DateTime())->modify('+7 days')) {
                // Due within 7 days and not complete
                $due_date_class = 'text-yellow-600';
            }
        }

        return '
            <div class="bg-white p-4 mb-4 rounded-lg shadow-md border border-gray-100 hover:shadow-lg transition cursor-grab">
                <a href="view_project.php?id=' . htmlspecialchars($project['id']) . '" class="text-sm font-semibold text-gray-900 hover:text-blue-600 block mb-2">
                    ' . htmlspecialchars($project['title']) . '
                </a>
                
                <div class="text-xs text-gray-500 mb-2">
                    <p class="mb-1"><i data-lucide="users" class="w-3 h-3 inline mr-1"></i> Client: ' . htmlspecialchars($project['company_name'] ?? 'Internal') . '</p>
                    <p><i data-lucide="user" class="w-3 h-3 inline mr-1"></i> Manager: ' . htmlspecialchars($project['manager_name'] ?? 'Unassigned') . '</p>
                </div>
                
                <div class="flex justify-between items-center mt-3">
                    <span class="' . $due_date_class . ' text-xs flex items-center">
                        <i data-lucide="calendar" class="w-3 h-3 inline mr-1"></i> Due: ' . $due_date_html . '
                    </span>
                    <span class="text-xs text-gray-600">' . $progress . '%</span>
                </div>
                
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                    <div class="h-1.5 rounded-full ' . $progress_color . '" style="width: ' . $progress . '%"></div>
                </div>
            </div>
        ';
    }
}
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Project Kanban Board</h1>
        <p class="text-gray-500 mt-1">Visual overview of all active projects, grouped by status.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <a href="projects.php" class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="list-collapse" class="w-4 h-4 inline mr-1"></i> List View
        </a>
        <a href="create_project.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                                 bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> New Project
        </a>
    </div>
</div>

<div class="flex overflow-x-auto space-x-6 pb-4">
    
    <?php foreach ($kanban_statuses as $status): 
        $count = count($grouped_projects[$status]);
        
        // Define column header color based on status
        $header_color = match ($status) {
            'In Progress' => 'bg-yellow-600',
            'Completed' => 'bg-green-600',
            'On Hold' => 'bg-gray-600',
            'Cancelled' => 'bg-red-600',
            default => 'bg-blue-600', // Planning
        };
    ?>
    
        <div class="w-72 flex-shrink-0">
            <div class="rounded-xl shadow-lg border border-gray-100 overflow-hidden bg-gray-50">
                <div class="p-3 text-white <?= $header_color ?> flex justify-between items-center">
                    <h2 class="font-bold text-sm"><?= htmlspecialchars($status) ?></h2>
                    <span class="px-2 py-0.5 text-xs rounded-full bg-white bg-opacity-30 font-medium"><?= $count ?></span>
                </div>
                
                <div class="p-3 min-h-[50px] space-y-3">
                    <?php 
                    if (!empty($grouped_projects[$status])) {
                        foreach ($grouped_projects[$status] as $project) {
                            echo generate_kanban_card($project);
                        }
                    } else {
                        echo '<p class="text-xs text-gray-400 text-center pt-2">No projects in this stage.</p>';
                    }
                    ?>
                </div>
                
            </div>
        </div>
        <?php endforeach; ?>

</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php'; 
?>