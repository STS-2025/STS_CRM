<?php
// edit_task.php

ob_start(); 
session_start();

$page_title = "Edit Task";
include 'api/db.php'; 

// --- 1. Fetch Task Data ---
$task_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($task_id <= 0) {
    $_SESSION['message'] = "Error: Invalid Task ID provided.";
    header('Location: tasks.php');
    exit();
}

// SQL to fetch the specific task details
$task_sql = "
    SELECT 
        t.id, t.title, t.description, t.due_date, t.priority, t.status, t.contact_id, t.user_id,
        c.name AS contact_name
    FROM tasks t
    LEFT JOIN contacts c ON t.contact_id = c.id
    WHERE t.id = ?
";
$stmt_task = $conn->prepare($task_sql);
$stmt_task->bind_param("i", $task_id);
$stmt_task->execute();
$task_result = $stmt_task->get_result();
$task = $task_result->fetch_assoc();
$stmt_task->close();

if (!$task) {
    $_SESSION['message'] = "Error: Task with ID {$task_id} not found.";
    header('Location: tasks.php');
    exit();
}

// --- 2. Fetch Contacts and Users for Dropdowns ---
$contacts_result = $conn->query("SELECT id, name, company_name FROM contacts ORDER BY name ASC");
$contacts = $contacts_result ? $contacts_result->fetch_all(MYSQLI_ASSOC) : [];

$users_result = $conn->query("SELECT id, name FROM users ORDER BY name ASC");
$users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();

$priorities = ['Low', 'Medium', 'High'];
$statuses = ['Not Started', 'In Progress', 'Completed'];

// --- 3. Form Submission Handling (Self-Processing) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_update'])) {
    
    // Get and sanitize form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description'] ?? '');
    $contact_id = (int)$_POST['contact_id'];
    $user_id = (int)$_POST['user_id'];
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $task_id_to_update = (int)$_POST['id'];

    // Input validation (basic)
    if (empty($title) || $contact_id <= 0 || $user_id <= 0 || $task_id_to_update !== $task_id) {
        $_SESSION['message'] = 'Error: Invalid data submitted.';
        header('Location: edit_task.php?id=' . $task_id);
        exit();
    }

    // Prepare update statement
    include 'api/db.php'; // Re-open connection for update
    $update_sql = "
        UPDATE tasks 
        SET title=?, description=?, due_date=?, priority=?, status=?, contact_id=?, user_id=?
        WHERE id=?
    ";
    
    $stmt_update = $conn->prepare($update_sql);
    // sssssiii: title, description, due_date, priority, status, contact_id, user_id, id
    $stmt_update->bind_param("sssssiii", $title, $description, $due_date, $priority, $status, $contact_id, $user_id, $task_id_to_update);

    if ($stmt_update->execute()) {
        $_SESSION['message'] = "Task #{$task_id} updated successfully!";
        header('Location: tasks.php'); // Redirect to list view after success
        exit();
    } else {
        $_SESSION['message'] = "Database Error: Could not update task. " . $stmt_update->error;
        header('Location: edit_task.php?id=' . $task_id);
        exit();
    }

    $stmt_update->close();
    $conn->close();
}

// Re-fetch data for display if the script hasn't exited (in case of GET or error on POST)
// NOTE: Since the main $task array was fetched successfully, we use that for the form values.
?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Edit Task #<?= $task['id'] ?></h1>
        <p class="text-gray-500 mt-1">Modifying: **<?= htmlspecialchars($task['title']) ?>**</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form action="edit_task.php?id=<?= $task_id ?>" method="POST" class="space-y-6">
        <input type="hidden" name="id" value="<?= $task['id'] ?>">
        <input type="hidden" name="task_update" value="1">

        <div>
            <label for="title" class="text-sm font-medium text-gray-700 block">Task Title</label>
            <input type="text" id="title" name="title" required
                   value="<?= htmlspecialchars($task['title']) ?>"
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>

        <div>
            <label for="description" class="text-sm font-medium text-gray-700 block">Description (Optional)</label>
            <textarea id="description" name="description" rows="3"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?= htmlspecialchars($task['description']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div>
                <label for="contact_id" class="text-sm font-medium text-gray-700 block">Associated Contact</label>
                <select id="contact_id" name="contact_id" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">-- Select Contact --</option>
                    <?php foreach($contacts as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($c['id'] == $task['contact_id'] ? 'selected' : '') ?>>
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
                        <option value="<?= $u['id'] ?>" <?= ($u['id'] == $task['user_id'] ? 'selected' : '') ?>>
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="due_date" class="text-sm font-medium text-gray-700 block">Due Date</label>
                <input type="date" id="due_date" name="due_date" required
                       value="<?= htmlspecialchars($task['due_date']) ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="priority" class="text-sm font-medium text-gray-700 block">Priority</label>
                <select id="priority" name="priority" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php foreach($priorities as $p): ?>
                        <option value="<?= $p ?>" <?= ($p == $task['priority'] ? 'selected' : '') ?>>
                            <?= $p ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="status" class="text-sm font-medium text-gray-700 block">Status</label>
                <select id="status" name="status" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php foreach($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($s == $task['status'] ? 'selected' : '') ?>>
                            <?= $s ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div></div>
        </div>

        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-end">
                <a href="tasks.php" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition">
                    Cancel
                </a>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <i data-lucide="save" class="w-4 h-4 inline mr-2"></i> Save Changes
                </button>
            </div>
        </div>
    </form>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php'; 
?>