<?php
// view_user.php

ob_start(); 
session_start();

$page_title = "View User Details";
include 'api/db.php'; 

// 1. Get User ID from URL
$user_id = (int)($_GET['id'] ?? 0);

if ($user_id <= 0) {
    // Redirect if no valid ID is provided
    $_SESSION['error'] = 'No user ID provided.';
    header('Location: users.php');
    exit();
}

$user = null;

// 2. Fetch User Data
// Note: We deliberately exclude the 'password' hash for security.
$sql = "SELECT id, name, email, role, status, team, created_at FROM users WHERE id = ? LIMIT 1";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // Handle database preparation error
    $_SESSION['error'] = 'Database error: Could not prepare statement.';
    header('Location: users.php');
    exit();
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    // User not found
    $_SESSION['error'] = "User ID #{$user_id} not found.";
    header('Location: users.php');
    exit();
}

$stmt->close();
$conn->close();

// Helper variables for display
$status_class = ($user['status'] === 'Active') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
$created_date = (new DateTime($user['created_at']))->format('M d, Y');

?>

<div class="max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($user['name']) ?></h1>
            <p class="text-gray-500 mt-1 flex items-center">
                <i data-lucide="mail" class="w-4 h-4 inline mr-1"></i> 
                <?= htmlspecialchars($user['email']) ?>
            </p>
        </div>

        <div class="mt-4 sm:mt-0 flex space-x-3">
            <a href="edit_user.php?id=<?= $user['id'] ?>" class="px-4 py-2 text-sm font-medium rounded-lg 
                                                 border border-blue-600 text-blue-600 hover:bg-blue-50 transition">
                <i data-lucide="pencil" class="w-4 h-4 inline mr-1"></i> Edit User
            </a>
            <a href="users.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                     border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                <i data-lucide="list-collapse" class="w-4 h-4 inline mr-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden mb-6">
        <div class="p-6 border-b border-gray-100 bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-700 flex items-center">
                <i data-lucide="user-square" class="w-5 h-5 mr-2"></i> Account Details
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-12">
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Role</p>
                    <p class="mt-1 text-lg font-medium text-gray-900"><?= htmlspecialchars($user['role']) ?></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Status</p>
                    <p class="mt-1">
                        <span class="px-3 py-1 text-sm font-semibold rounded-full <?= $status_class ?>">
                            <?= htmlspecialchars($user['status']) ?>
                        </span>
                    </p>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500">Team</p>
                    <p class="mt-1 text-lg text-gray-900"><?= htmlspecialchars($user['team'] ?? 'N/A') ?></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Member Since</p>
                    <p class="mt-1 text-lg text-gray-900"><?= $created_date ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-700 flex items-center mb-4">
                <i data-lucide="check-square" class="w-5 h-5 mr-2"></i> Recent Tasks
            </h3>
            <p class="text-sm text-gray-500">Integration with the `tasks` table will show active tasks assigned to this user here.</p>
        </div>

        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-700 flex items-center mb-4">
                <i data-lucide="dollar-sign" class="w-5 h-5 mr-2"></i> Deals in Pipeline
            </h3>
            <p class="text-sm text-gray-500">Integration with the `deals` table will show deals owned by this user here.</p>
        </div>
        
    </div>
</div>

<?php
$page_content = ob_get_clean();
include 'includes/layout.php';
?>