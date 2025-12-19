<?php 
// create_project.php

ob_start(); 
session_start();

$page_title = "Create New Project";
include 'api/db.php'; 

// --- Fetch necessary foreign key data (Managers and Clients) ---

// 1. Fetch Users (Managers)
$managers = [];
$users_sql = "SELECT id, name FROM users ORDER BY name ASC";
$users_result = $conn->query($users_sql);
if ($users_result) {
    while($user = $users_result->fetch_assoc()) {
        $managers[] = $user;
    }
}

// 2. Fetch Companies (Clients)
$clients = [];
$clients_sql = "SELECT id, name FROM companies ORDER BY name ASC";
$clients_result = $conn->query($clients_sql);
if ($clients_result) {
    while($client = $clients_result->fetch_assoc()) {
        $clients[] = $client;
    }
}

// Close connection before capturing content
$conn->close();

?>

<div class="max-w-4xl mx-auto p-6 bg-white rounded-xl shadow-lg border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">New Project</h1>
        <a href="projects.php" class="px-3 py-2 text-sm font-medium rounded-lg 
                                       border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="list-collapse" class="w-4 h-4 inline mr-1"></i> Back to Projects
        </a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            <?= $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form action="api/create_project_process.php" method="POST">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Project Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="title" required 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                       placeholder="e.g., Website Redesign Q4">
            </div>

            <div>
                <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-1">Project Manager <span class="text-red-500">*</span></label>
                <select name="manager_id" id="manager_id" required 
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
                    <option value="">Select Manager</option>
                    <?php foreach ($managers as $manager): ?>
                        <option value="<?= htmlspecialchars($manager['id']) ?>">
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
                        <option value="<?= htmlspecialchars($client['id']) ?>">
                            <?= htmlspecialchars($client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Initial Status <span class="text-red-500">*</span></label>
                <select name="status" id="status" required 
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
                    <option value="Planning" selected>Planning</option>
                    <option value="In Progress">In Progress</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>

            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" id="start_date" 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
            </div>

            <div>
                <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                <input type="date" name="due_date" id="due_date" 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
            </div>
            
        </div>
        
        <div class="mt-6">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" id="description" rows="5" 
                      class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                      placeholder="Detailed scope of work, key deliverables, and project goals."></textarea>
        </div>

        <div class="pt-6 border-t mt-6 flex justify-end">
            <button type="submit" class="px-6 py-2 text-sm font-medium rounded-lg 
                                            bg-blue-600 hover:bg-blue-700 text-white transition duration-150 shadow-md">
                <i data-lucide="save" class="w-4 h-4 inline mr-1"></i> Save Project
            </button>
        </div>
    </form>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php'; 
?>