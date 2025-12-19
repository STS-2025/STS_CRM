<?php 
// tasks_board.php

ob_start(); 
session_start();

$page_title = "Task Board View";
include 'api/db.php'; 

// --- 1. DATA FETCHING AND CATEGORIZATION ---

$tasks_by_status = [
    'Not Started' => [],
    'In Progress' => [],
    'Completed' => [],
];

// Fetch all necessary task data
$sql = "
    SELECT 
        t.id, t.title, t.due_date, t.priority, t.status,
        c.name AS contact_name
    FROM tasks t
    LEFT JOIN contacts c ON t.contact_id = c.id
    ORDER BY t.priority DESC, t.due_date ASC 
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($task = $result->fetch_assoc()) {
        $status = $task['status'];
        if (isset($tasks_by_status[$status])) {
            $tasks_by_status[$status][] = $task;
        }
    }
}
$conn->close();

/**
 * Helper function to generate a Kanban card.
 */
function generate_kanban_card($task) {
    // Priority Badge Color
    $priority_class = match ($task['priority']) {
        'High' => 'bg-red-500',
        'Medium' => 'bg-yellow-500',
        'Low' => 'bg-blue-500',
        default => 'bg-gray-400',
    };
    
    // Check if task is overdue
    $due_date = new DateTime($task['due_date']);
    $today = new DateTime(date('Y-m-d'));
    $is_overdue = ($due_date < $today && $task['status'] !== 'Completed');

    // Due date display color
    $date_class = $is_overdue ? 'text-red-600 font-semibold' : 'text-gray-500';

    return '
        <div class="bg-white rounded-lg shadow-md p-4 mb-4 border-l-4 border-t-2 border-gray-100 hover:shadow-xl transition duration-300 cursor-grab">
            <div class="flex justify-between items-start">
                <h4 class="font-semibold text-gray-800 text-sm">' . htmlspecialchars($task['title']) . '</h4>
                <div class="w-2 h-2 rounded-full ' . $priority_class . '" title="Priority: ' . htmlspecialchars($task['priority']) . '"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1">Contact: ' . htmlspecialchars($task['contact_name'] ?? 'N/A') . '</p>
            
            <div class="flex justify-between items-center mt-3 pt-2 border-t border-gray-100">
                <span class="text-xs ' . $date_class . '">
                    <i data-lucide="calendar" class="w-3 h-3 inline mr-1"></i> Due: ' . htmlspecialchars($task['due_date']) . '
                </span>
                <a href="edit_task.php?id=' . $task['id'] . '" class="text-xs text-blue-500 hover:text-blue-700">Edit</a>
            </div>
        </div>
    ';
}
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Task Board View</h1>
        <p class="text-gray-500 mt-1">Visualize tasks and drag-and-drop to update status (Drag/Drop functionality requires additional JS).</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <a href="tasks.php" class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="list-collapse" class="w-4 h-4 inline mr-1"></i> Table View
        </a>
        <a href="add_task.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                             bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
            <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> New Task
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    
    <?php foreach ($tasks_by_status as $status => $tasks): 
        $count = count($tasks);
        // Determine column background color for visual distinction
        $col_bg_class = match ($status) {
            'Not Started' => 'border-t-4 border-gray-400',
            'In Progress' => 'border-t-4 border-blue-400',
            'Completed' => 'border-t-4 border-green-400',
            default => 'border-t-4 border-gray-200',
        };
    ?>
    
    <div class="flex flex-col bg-gray-50 rounded-xl shadow-inner p-4 <?= $col_bg_class ?>">
        
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex justify-between items-center">
            <span><?= htmlspecialchars($status) ?></span>
            <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full"><?= $count ?></span>
        </h3>
        
        <div id="column-<?= str_replace(' ', '-', strtolower($status)) ?>" class="space-y-3 min-h-20">
            <?php 
            if ($count > 0) {
                foreach ($tasks as $task) {
                    echo generate_kanban_card($task);
                }
            } else {
                echo '<p class="text-center text-sm text-gray-400 py-6">No tasks in this column.</p>';
            }
            ?>
        </div>
        
    </div>
    
    <?php endforeach; ?>

</div>

<?php
// Capture the content
$page_content = ob_get_clean();

// Include the master layout file
include 'includes/layout.php'; 
?>