<?php
// view_project.php

ob_start();
session_start();

$page_title = "Project Details";
include 'api/db.php'; 

// 1. Get Project ID from URL
$project_id = (int)($_GET['id'] ?? 0);

if ($project_id === 0) {
    $_SESSION['error'] = "Invalid project ID provided.";
    header('Location: projects.php');
    exit();
}

// 2. Build SQL Query to fetch all details, including Manager and Company names
$sql = "
    SELECT 
        p.id, p.title, p.description, p.status, p.progress, p.start_date, p.due_date, 
        p.created_at, p.updated_at,
        u.name AS manager_name,
        c.name AS company_name
    FROM projects p
    LEFT JOIN users u ON p.manager_id = u.id
    LEFT JOIN companies c ON p.company_id = c.id
    WHERE p.id = ?
    LIMIT 1
";

// 3. Prepare and Execute Statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $_SESSION['error'] = 'Database error during preparation: ' . $conn->error;
    header('Location: projects.php');
    exit();
}

$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

$stmt->close();
$conn->close();

// 4. Handle Case: Project Not Found
if (!$project) {
    $_SESSION['error'] = "Project with ID #{$project_id} not found.";
    header('Location: projects.php');
    exit();
}

// 5. Helper variables for display
$progress = (int)$project['progress'];
$progress_color = ($progress >= 100) ? 'bg-green-500' : 
                  (($progress > 50) ? 'bg-blue-500' : 'bg-red-500');

$status_class = match ($project['status']) {
    'In Progress' => 'bg-yellow-100 text-yellow-800',
    'Completed' => 'bg-green-100 text-green-800',
    'On Hold' => 'bg-gray-100 text-gray-800',
    default => 'bg-blue-100 text-blue-800',
};

?>

<div class="max-w-6xl mx-auto">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-1">
                <?= htmlspecialchars($project['title']) ?>
            </h1>
            <p class="text-gray-500">Project ID: <?= $project['id'] ?></p>
        </div>
        
        <div class="mt-4 sm:mt-0 flex space-x-3">
            <a href="edit_project.php?id=<?= $project['id'] ?>" class="px-3 py-2 text-sm font-medium rounded-lg 
                                                                     border border-gray-300 bg-white text-blue-700 hover:bg-blue-50 transition">
                <i data-lucide="pencil" class="w-4 h-4 inline mr-1"></i> Edit Project
            </a>
            <a href="projects.php" class="px-3 py-2 text-sm font-medium rounded-lg 
                                       bg-gray-200 text-gray-700 hover:bg-gray-300 transition">
                <i data-lucide="list-collapse" class="w-4 h-4 inline mr-1"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Project Overview</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex items-center space-x-3">
                <p class="text-gray-500 font-medium w-32">Status:</p>
                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?= $status_class ?>">
                    <?= htmlspecialchars($project['status']) ?>
                </span>
            </div>
            
            <div class="flex items-center space-x-3">
                <p class="text-gray-500 font-medium w-32">Progress:</p>
                <div class="flex-1">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full <?= $progress_color ?>" style="width: <?= $progress ?>%"></div>
                    </div>
                    <span class="text-sm text-gray-600 mt-1 block"><?= $progress ?>% Complete</span>
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                <p class="text-gray-500 font-medium w-32">Manager:</p>
                <span class="text-gray-800 font-medium"><?= htmlspecialchars($project['manager_name'] ?? 'Unassigned') ?></span>
            </div>
            
            <div class="flex items-center space-x-3">
                <p class="text-gray-500 font-medium w-32">Client:</p>
                <span class="text-blue-600 hover:text-blue-800 cursor-pointer"><?= htmlspecialchars($project['company_name'] ?? 'Internal Project') ?></span>
            </div>
            
            <div class="flex items-center space-x-3">
                <p class="text-gray-500 font-medium w-32">Start Date:</p>
                <span class="text-gray-800"><?= $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : 'N/A' ?></span>
            </div>
            
            <div class="flex items-center space-x-3">
                <p class="text-gray-500 font-medium w-32">Due Date:</p>
                <span class="text-gray-800"><?= $project['due_date'] ? date('M d, Y', strtotime($project['due_date'])) : 'N/A' ?></span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Description</h2>
        <div class="text-gray-700 whitespace-pre-wrap">
            <?= htmlspecialchars($project['description'] ?? 'No description provided.') ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 text-sm text-gray-500">
        <p>Created on: <?= date('M d, Y H:i', strtotime($project['created_at'])) ?></p>
        <p>Last Updated: <?= date('M d, Y H:i', strtotime($project['updated_at'])) ?></p>
    </div>

</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php';
?>