<?php
// add_task.php

ob_start(); 
session_start();

$page_title = "Add New Task";
include 'api/db.php'; 

// Fetch all contacts and users for the dropdown menus
$contacts_result = $conn->query("SELECT id, name, company_name FROM contacts ORDER BY name ASC");
$contacts = $contacts_result ? $contacts_result->fetch_all(MYSQLI_ASSOC) : [];

$users_result = $conn->query("SELECT id, name FROM users ORDER BY name ASC");
$users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();

$priorities = ['Low', 'Medium', 'High'];
?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Add New Task</h1>
        <p class="text-gray-500 mt-1">Schedule a new activity or follow-up for a contact.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form action="api/add_task_process.php" method="POST" class="space-y-6">

        <div>
            <label for="title" class="text-sm font-medium text-gray-700 block">Task Title</label>
            <input type="text" id="title" name="title" required
                   placeholder="e.g., Follow up on latest quote"
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>

        <div>
            <label for="description" class="text-sm font-medium text-gray-700 block">Description (Optional)</label>
            <textarea id="description" name="description" rows="3"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div>
                <label for="contact_id" class="text-sm font-medium text-gray-700 block">Associated Contact</label>
                <select id="contact_id" name="contact_id" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">-- Select Contact --</option>
                    <?php foreach($contacts as $c): ?>
                        <option value="<?= $c['id'] ?>">
                            <?= htmlspecialchars($c['name']) . ($c['company_name'] ? " ({$c['company_name']})" : "") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="user_id" class="text-sm font-medium text-gray-700 block">Assigned User</label>
                <select id="user_id" name="user_id" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">-- Select User --</option>
                    <?php foreach($users as $u): ?>
                        <option value="<?= $u['id'] ?>">
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="due_date" class="text-sm font-medium text-gray-700 block">Due Date</label>
                <input type="date" id="due_date" name="due_date" required
                       value="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="priority" class="text-sm font-medium text-gray-700 block">Priority</label>
                <select id="priority" name="priority" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php foreach($priorities as $p): ?>
                        <option value="<?= $p ?>" <?= ($p == 'Medium' ? 'selected' : '') ?>>
                            <?= $p ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div></div>
            <div></div>
        </div>

        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-end">
                <a href="tasks.php" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition">
                    Cancel
                </a>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <i data-lucide="plus-circle" class="w-4 h-4 inline mr-2"></i> Create Task
                </button>
            </div>
        </div>
    </form>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php'; 
?>