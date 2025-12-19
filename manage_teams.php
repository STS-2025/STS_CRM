<?php 
// manage_teams.php - Main Teams CRUD Interface

ob_start(); 
session_start();

$page_title = "Team Management";
include 'api/db.php'; 

// --- Data Fetching ---

// 1. Fetch all teams and their managers (joining with users table)
$team_rows = '';
$sql_teams = "
    SELECT 
        t.id, 
        t.name, 
        t.description, 
        t.created_at,
        u.name AS manager_name
    FROM teams t
    LEFT JOIN users u ON t.manager_id = u.id
    ORDER BY t.name ASC
";

$result_teams = $conn->query($sql_teams);

if ($result_teams && $result_teams->num_rows > 0) {
    while($team = $result_teams->fetch_assoc()) {
        $team_rows .= '
            <tr class="hover:bg-gray-50 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">' . htmlspecialchars($team['name']) . '</td>
                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">' . htmlspecialchars($team['description'] ?? 'No description.') . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($team['manager_name'] ?? 'Unassigned') . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . (new DateTime($team['created_at']))->format('M d, Y') . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="edit_team.php?id=' . $team['id'] . '" class="text-blue-600 hover:text-blue-900">Edit</a>
                    <a href="api/delete_team_process.php?id=' . $team['id'] . '" 
                       class="text-red-600 hover:text-red-900 ml-2"
                       onclick="return confirm(\'Are you sure you want to delete the team: ' . addslashes($team['name']) . '?\')">Delete</a>
                </td>
            </tr>
        ';
    }
}

// 2. Fetch Users for the Manager dropdown in the 'Add Team' form
$users_options = '<option value="" selected>-- Select Manager (Optional) --</option>';
$sql_users = "SELECT id, name FROM users WHERE status = 'Active' ORDER BY name ASC";
$result_users = $conn->query($sql_users);

if ($result_users && $result_users->num_rows > 0) {
    while($user = $result_users->fetch_assoc()) {
        $users_options .= '<option value="' . $user['id'] . '">' . htmlspecialchars($user['name']) . '</option>';
    }
}
$conn->close();

// Sticky form data
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

?>

<div class="max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Team Management</h1>
            <p class="text-gray-500 mt-1">Define and organize teams, assign managers, and track membership.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="users.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                     border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition shadow-sm">
                <i data-lucide="users" class="w-4 h-4 inline mr-1"></i> Back to Users
            </a>
        </div>
    </div>
    
    <!-- Messages (Success or Error) -->
    <?php 
    if (isset($_SESSION['error'])): ?>
        <div class="p-4 mb-6 text-sm text-red-700 bg-red-100 rounded-lg shadow-md" role="alert">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; 
    
    if (isset($_SESSION['message'])): ?>
        <div class="p-4 mb-6 text-sm text-green-700 bg-green-100 rounded-lg shadow-md" role="alert">
            <?= $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1 & 2: Teams List -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-700">Existing Teams (<?= $result_teams->num_rows ?? 0 ?>)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manager</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        if (empty($team_rows)) {
                            echo '<tr><td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No teams have been defined yet.</td></tr>';
                        } else {
                            echo $team_rows; 
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Column 3: Add New Team Form -->
        <div class="lg:col-span-1 bg-white rounded-xl shadow-lg border border-gray-100 p-6 h-fit">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i data-lucide="plus-square" class="w-5 h-5 mr-2"></i> Add New Team
            </h2>
            
            <form action="api/add_team_process.php" method="POST" class="space-y-4">
                
                <!-- Team Name -->
                <div>
                    <label for="team_name" class="block text-sm font-medium text-gray-700 mb-1">Team Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="team_name" required 
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                           value="<?= htmlspecialchars($form_data['name'] ?? '') ?>" placeholder="e.g., Sales Alpha">
                </div>

                <!-- Manager -->
                <div>
                    <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-1">Team Manager</label>
                    <select name="manager_id" id="manager_id" 
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
                        <?= $users_options ?>
                    </select>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" rows="3" 
                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                              placeholder="Briefly describe the team's purpose and focus."><?= htmlspecialchars($form_data['description'] ?? '') ?></textarea>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full px-4 py-2 text-sm font-medium rounded-lg 
                                                 bg-blue-600 hover:bg-blue-700 text-white transition duration-150 shadow-md">
                    <i data-lucide="save" class="w-4 h-4 inline mr-1"></i> Save Team
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php';
?>