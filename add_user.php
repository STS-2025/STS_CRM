<?php
// add_user.php

ob_start();
session_start();

$page_title = "Add New User";
include 'api/db.php';

// --- Fetch necessary foreign key data (e.g., Teams, if they were in a separate table) ---
// Since 'team' is currently a simple VARCHAR field, we will define common roles here.
$available_roles = ['Employee', 'Manager', 'Administrator', 'Sales Rep', 'Marketing Specialist'];
$available_status = ['Active', 'Inactive'];

// You would typically fetch teams from a 'teams' table here if one existed.
// For now, we'll use placeholder team names if you plan to use a dropdown for team:
$available_teams = ['Sales Alpha', 'Marketing', 'Product Development', 'IT', 'Customer Support'];

$conn->close();

// Initialize variables for sticky form (though validation will happen in the process file)
$name = $_SESSION['form_data']['name'] ?? '';
$email = $_SESSION['form_data']['email'] ?? '';
$role = $_SESSION['form_data']['role'] ?? '';
$team = $_SESSION['form_data']['team'] ?? '';

// Clear form data from session
unset($_SESSION['form_data']);
?>

<div class="max-w-3xl mx-auto p-6 bg-white rounded-xl shadow-lg border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Add New User</h1>
        <a href="users.php" class="px-3 py-2 text-sm font-medium rounded-lg 
                                     border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="list-collapse" class="w-4 h-4 inline mr-1"></i> Back to List
        </a>
    </div>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form action="api/add_user_process.php" method="POST">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" required 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                       value="<?= htmlspecialchars($name) ?>" placeholder="Jane Doe">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email" required 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                       value="<?= htmlspecialchars($email) ?>" placeholder="jane.doe@company.com">
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">User Role <span class="text-red-500">*</span></label>
                <select name="role" id="role" required 
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
                    <option value="">Select Role</option>
                    <?php foreach ($available_roles as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>"
                            <?= $role == $r ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="team" class="block text-sm font-medium text-gray-700 mb-1">Team Assignment</label>
                <select name="team" id="team"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
                    <option value="">Select Team (Optional)</option>
                    <?php foreach ($available_teams as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>"
                            <?= $team == $t ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                <input type="password" name="password" id="password" required 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                       placeholder="********">
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                <input type="password" name="confirm_password" id="confirm_password" required 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                       placeholder="********">
            </div>

            <input type="hidden" name="status" value="Active">

        </div>
        
        <div class="pt-6 border-t mt-6 flex justify-end">
            <button type="submit" class="px-6 py-2 text-sm font-medium rounded-lg 
                                                 bg-blue-600 hover:bg-blue-800 text-white transition duration-150 shadow-md">
                <i data-lucide="save" class="w-4 h-4 inline mr-1"></i> Create User
            </button>
        </div>
    </form>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php';
?>