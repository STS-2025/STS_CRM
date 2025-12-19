<?php 
// edit_project.php

ob_start(); 
session_start();

$page_title = "Edit Project";
include 'api/db.php'; 

// 1. Get Project ID from URL
$project_id = (int)($_GET['id'] ?? 0);

if ($project_id === 0) {
    $_SESSION['error'] = "Invalid project ID provided.";
    header('Location: projects.php');
    exit();
}

// --- Fetch necessary foreign key data (Managers, Clients) ---

// Fetch Users (Managers)
$managers = [];
$users_sql = "SELECT id, name FROM users ORDER BY name ASC";
$users_result = $conn->query($users_sql);
if ($users_result) {
    while($user = $users_result->fetch_assoc()) {
        $managers[] = $user;
    }
}

// Fetch Companies (Clients)
$clients = [];
$clients_sql = "SELECT id, name FROM companies ORDER BY name ASC";
$clients_result = $conn->query($clients_sql);
if ($clients_result) {
    while($client = $clients_result->fetch_assoc()) {
        $clients[] = $client;
    }
}

// --- Fetch current Project data ---
$sql = "
    SELECT 
        id, title, description, status, progress, start_date, due_date, company_id, manager_id
    FROM projects
    WHERE id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Handle Case: Project Not Found
if (!$project) {
    $_SESSION['error'] = "Project with ID #{$project_id} not found for editing.";
    header('Location: projects.php');
    exit();
}

$current_status = $project['status'];
$status_options = ['Planning', 'In Progress', 'On Hold', 'Completed', 'Cancelled'];

?>

<div class="max-w-4xl mx-auto p-6 bg-white rounded-xl shadow-lg border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Edit Project: <?= htmlspecialchars($project['title']) ?></h1>
        <a href="view_project.php?id=<?= $project_id ?>" class="px-3 py-2 text-sm font-medium rounded-lg 
                                       border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="eye" class="w-4 h-4 inline mr-1"></i> View Details
        </a>
    </div>

    <form action="api/update_project_process.php" method="POST">
        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Project Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="title" required 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                       value="<?= htmlspecialchars($project['title']) ?>">
            </div>

            <div>
                <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-1">Project Manager <span class="text-red-500">*</span></label>
                <select name="manager_id" id="manager_id" required 
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
                    <option value="">Select Manager</option>
                    <?php foreach ($managers as $manager): ?>
                        <option value="<?= htmlspecialchars($manager['id']) ?>"
                            <?= $project['manager_id'] == $manager['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($manager['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="company_id" class="block text-sm font-medium text-gray-700 mb-1">Client Company (Optional)</label>
                <select name="company_id" id="company_id" 
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
                    <option value="">Internal Project or Select Client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= htmlspecialchars($client['id']) ?>"
                            <?= $project['company_id'] == $client['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select name="status" id="status" required 
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
                    <?php foreach ($status_options as $option): ?>
                        <option value="<?= $option ?>" 
                            <?= $current_status == $option ? 'selected' : '' ?>>
                            <?= $option ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="progress" class="block text-sm font-medium text-gray-700 mb-1">Progress (%)</label>
                <input type="number" name="progress" id="progress" min="0" max="100" 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                       value="<?= htmlspecialchars($project['progress']) ?>">
            </div>

            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" id="start_date" 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2"
                       value="<?= htmlspecialchars($project['start_date']) ?>">
            </div>

            <div class="md:col-span-2"> 
                <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                <input type="date" name="due_date" id="due_date" 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2"
                       value="<?= htmlspecialchars($project['due_date']) ?>">
            </div>
            
        </div>
        
        <div class="mt-6">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" id="description" rows="5" 
                      class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                      placeholder="Detailed scope of work, key deliverables, and project goals."><?= htmlspecialchars($project['description']) ?></textarea>
        </div>

        <div class="pt-6 border-t mt-6 flex justify-end">
            <button type="submit" class="px-6 py-2 text-sm font-medium rounded-lg 
                                             bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
                <i data-lucide="upload" class="w-4 h-4 inline mr-1"></i> Update Project
            </button>
        </div>
    </form>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php'; 
?>