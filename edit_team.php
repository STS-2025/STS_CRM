<?php
// edit_team.php

ob_start();
session_start();

$page_title = "Edit Team";
include 'api/db.php'; 

// --- Setup ---
// Get Team ID from URL
$team_id = (int)($_GET['id'] ?? 0);

if ($team_id <= 0) {
    $_SESSION['error'] = 'Invalid Team ID provided for editing.';
    header('Location: manage_teams.php');
    exit();
}

$team = null;

// Use session data for sticky form on validation failure, otherwise fetch from DB
$form_data = $_SESSION['form_data'] ?? null;
unset($_SESSION['form_data']); // Clear session data after use

if ($form_data && (isset($form_data['id']) && $form_data['id'] == $team_id)) {
    // Use sticky data if available and matches the ID
    $team = $form_data;
} else {
    // 1. Fetch current Team Data from DB
    $sql_team = "SELECT id, name, description, manager_id FROM teams WHERE id = ? LIMIT 1";
    $stmt_team = $conn->prepare($sql_team);
    
    if ($stmt_team === false) {
        $_SESSION['error'] = 'Database error during data fetch.';
        header('Location: manage_teams.php');
        exit();
    }
    
    $stmt_team->bind_param('i', $team_id);
    $stmt_team->execute();
    $result_team = $stmt_team->get_result();

    if ($result_team->num_rows === 1) {
        $team = $result_team->fetch_assoc();
    } else {
        $_SESSION['error'] = "Team ID #{$team_id} not found.";
        header('Location: manage_teams.php');
        exit();
    }
    $stmt_team->close();
}

// 2. Fetch all Active Users for the Manager dropdown
$users_options = '';
$sql_users = "SELECT id, name FROM users WHERE status = 'Active' ORDER BY name ASC";
$result_users = $conn->query($sql_users);

// Set selected manager ID based on fetched data or sticky data
$selected_manager_id = $team['manager_id'] ?? null;

if ($result_users && $result_users->num_rows > 0) {
    $users_options .= '<option value="">-- Select Manager (Optional) --</option>';
    while($user = $result_users->fetch_assoc()) {
        $selected = ($user['id'] == $selected_manager_id) ? 'selected' : '';
        $users_options .= '<option value="' . $user['id'] . '" ' . $selected . '>' . htmlspecialchars($user['name']) . '</option>';
    }
} else {
    $users_options .= '<option value="">No Active Users Found</option>';
}

$conn->close();

?>

<div class="max-w-3xl mx-auto p-6 bg-white rounded-xl shadow-lg border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Edit Team: <?= htmlspecialchars($team['name']) ?></h1>
        <a href="manage_teams.php" class="px-3 py-2 text-sm font-medium rounded-lg 
                                     border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="list-collapse" class="w-4 h-4 inline mr-1"></i> Back to Teams
        </a>
    </div>
    
    <?php 
    if (isset($_SESSION['error'])): ?>
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; 
    
    if (isset($_SESSION['message'])): ?>
        <div class="p-4 mb-4 text-sm text-blue-700 bg-blue-100 rounded-lg" role="alert">
            <?= $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <form action="api/edit_team_process.php" method="POST" class="space-y-6">
        <input type="hidden" name="team_id" value="<?= htmlspecialchars($team['id']) ?>">
        
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Team Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name" required 
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                   value="<?= htmlspecialchars($team['name']) ?>">
        </div>

        <div>
            <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-1">Team Manager</label>
            <select name="manager_id" id="manager_id" 
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
                <?= $users_options ?>
            </select>
        </div>
        
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" id="description" rows="4" 
                      class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                      placeholder="Briefly describe the team's purpose and focus."><?= htmlspecialchars($team['description'] ?? '') ?></textarea>
        </div>

        <div class="pt-6 border-t flex justify-end">
            <button type="submit" class="px-6 py-2 text-sm font-medium rounded-lg 
                                         bg-blue-600 hover:bg-blue-700 text-white transition duration-150 shadow-md">
                <i data-lucide="check-circle" class="w-4 h-4 inline mr-1"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php';
?>