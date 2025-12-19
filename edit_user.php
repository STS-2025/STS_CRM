<?php
// edit_user.php

ob_start();
session_start();

$page_title = "Edit User";
include 'api/db.php';

// --- Data Setup ---
$available_roles = ['Employee', 'Manager', 'Administrator', 'Sales Rep', 'Marketing Specialist'];
$available_status = ['Active', 'Inactive'];
$available_teams = ['Sales Alpha', 'Marketing', 'Product Development', 'IT', 'Customer Support']; // Placeholder teams

// 1. Get User ID from URL
$user_id = (int)($_GET['id'] ?? 0);

if ($user_id <= 0) {
    $_SESSION['error'] = 'Invalid user ID provided for editing.';
    header('Location: users.php');
    exit();
}

// 2. Fetch User Data to pre-fill the form
// Use session data for sticky form on validation failure, otherwise fetch from DB
$user = $_SESSION['form_data'] ?? null;
unset($_SESSION['form_data']); // Clear session data after use

if (!$user) {
    // If no session data, fetch from database
    $sql = "SELECT id, name, email, role, status, team FROM users WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        $_SESSION['error'] = 'Database error during data fetch.';
        header('Location: users.php');
        exit();
    }

    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        $_SESSION['error'] = "User ID #{$user_id} not found.";
        header('Location: users.php');
        exit();
    }
    $stmt->close();
}

$conn->close();

// Set up variables for form fields
$name = $user['name'] ?? '';
$email = $user['email'] ?? '';
$role = $user['role'] ?? '';
$team = $user['team'] ?? '';
$status = $user['status'] ?? '';

// Ensure $user_id is available if fetched from session/DB
$id_to_edit = $user['id'] ?? $user_id;

?>

<div class="max-w-3xl mx-auto p-6 bg-white rounded-xl shadow-lg border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Edit User: <?= htmlspecialchars($name) ?></h1>
        <a href="view_user.php?id=<?= $id_to_edit ?>" class="px-3 py-2 text-sm font-medium rounded-lg 
                                     border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="eye" class="w-4 h-4 inline mr-1"></i> View Details
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

    <form action="api/edit_user_process.php" method="POST">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($id_to_edit) ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" required 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                       value="<?= htmlspecialchars($name) ?>">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email" required 
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                       value="<?= htmlspecialchars($email) ?>">
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
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select name="status" id="status" required 
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2">
                    <?php foreach ($available_status as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"
                            <?= $status == $s ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="md:col-span-2">
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

            <div class="md:col-span-2 pt-4 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Change Password (Optional)</h3>
                <p class="text-sm text-gray-500 mb-4">Leave both fields empty if you do not want to change the password.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" name="password" id="password" 
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                               placeholder="********">
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" 
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2" 
                               placeholder="********">
                    </div>
                </div>
            </div>

        </div>
        
        <div class="pt-6 border-t mt-6 flex justify-end">
            <button type="submit" class="px-6 py-2 text-sm font-medium rounded-lg 
                                                 bg-blue-600 hover:bg-blue-700 text-white transition duration-150 shadow-md">
                <i data-lucide="check-circle" class="w-4 h-4 inline mr-1"></i> Update User
            </button>
        </div>
    </form>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php';
?>