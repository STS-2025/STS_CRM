<?php 
// users.php

// 1. Start capturing the output buffer and session
ob_start(); 
session_start();

// 2. Set the specific page title for the layout
$page_title = "Users & Teams";

// 3. Include necessary files and database connection
// include 'includes/auth_check.php';
include 'api/db.php'; 

/**
 * Helper function to generate a table row for a user.
 */
if (!function_exists('generate_user_row')) {
    function generate_user_row($id, $name, $email, $role, $status, $team) {
        // Status badge color logic
        $status_class = ($status === 'Active') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';

        // Placeholder image path
        $avatar_path = './assets/images/user-avatar.png';

        return '
            <tr class="hover:bg-gray-50 transition duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 flex items-center">
                    <!-- <img src="' . $avatar_path . '" class="h-8 w-8 rounded-full mr-3 object-cover" alt="Avatar"> -->
                    <a href="view_user.php?id=' . htmlspecialchars($id) . '" class="text-blue-600 hover:text-blue-800">' . htmlspecialchars($name) . '</a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($email) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-medium">' . htmlspecialchars($role) . '</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $status_class . '">'
                        . htmlspecialchars($status) . '
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($team) . '</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="edit_user.php?id=' . htmlspecialchars($id) . '" class="text-blue-600 hover:text-blue-900 ml-2">Edit</a>
                    <a href="api/delete_user_process.php?id=' . htmlspecialchars($id) . '" 
                       class="text-red-600 hover:text-red-900 ml-2"
                       onclick="return confirm(\'Are you sure you want to delete this user?\')">Delete</a>
                </td>
            </tr>
        ';
    }
}


// --- 4. DATA FETCHING AND PAGINATION LOGIC ---

// 1. Define pagination variables
$limit = 10; // Users per page
// Use null coalescing operator and cast to int for safety
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// 2. Fetch Total Count
$count_result = $conn->query("SELECT COUNT(*) AS total FROM users");
$total_users = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_users / $limit);

// 3. Calculate OFFSET and adjust page if necessary
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;
if ($offset < 0) $offset = 0; // Prevent negative offset

// 4. Build the SQL query for data fetching
$user_rows = '';
$sql = "
    SELECT id, name, email, role, status, team
    FROM users
    ORDER BY name ASC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($user = $result->fetch_assoc()) {
        $user_rows .= generate_user_row(
            $user['id'],
            $user['name'], 
            $user['email'], 
            $user['role'], 
            $user['status'], 
            $user['team'] ?? 'N/A' // Handle NULL team value safely
        );
    }
}
// Close connection after fetching data
$conn->close();

// 5. Calculate display range for the summary line
$current_page_count = $result ? $result->num_rows : 0;
$start_item = $current_page_count > 0 ? $offset + 1 : 0;
$end_item = $current_page_count > 0 ? $offset + $current_page_count : 0;
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Users & Teams</h1>
        <p class="text-gray-500 mt-1">Manage employee accounts, roles, permissions, and team assignments.</p>
    </div>

    <div class="mt-4 sm:mt-0 flex space-x-2">
        <!-- <button class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
            <i data-lucide="users" class="w-4 h-4 inline mr-1"></i> Manage Teams
        </button> -->
        <a href="manage_teams.php" class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
    <i data-lucide="users" class="w-4 h-4 inline mr-1"></i> Manage Teams
</a>
        <a href="add_user.php" class="px-4 py-2 text-sm font-medium rounded-lg 
                                                 bg-blue-600 hover:bg-blue-700 text-white transition duration-150 shadow-md">
            <i data-lucide="user-plus" class="w-4 h-4 inline mr-1"></i> Add User
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                if (empty($user_rows)) {
                    echo '<tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No users found.</td></tr>';
                } else {
                    echo $user_rows; 
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="p-4 flex justify-between items-center text-sm text-gray-600 border-t border-gray-200">
        <span>
            <?php if ($total_users > 0): ?>
                Showing <?= $start_item ?> to <?= $end_item ?> of <?= $total_users ?> users
            <?php else: ?>
                No users found.
            <?php endif; ?>
        </span>
        
        <div class="space-x-1 flex items-center">
            <?php
            // Previous Button Logic
            $prev_page = $page - 1;
            $prev_class = $page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            ?>
            <a href="users.php?page=<?= $prev_page ?>" 
               class="px-3 py-1 border rounded-lg <?= $prev_class ?>">
                Previous
            </a>
            
            <?php 
            // Pagination buttons (display current page, two before, and two after)
            for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): 
                $active_class = $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'border border-gray-300 hover:bg-gray-100';
            ?>
                <a href="users.php?page=<?= $i ?>" 
                   class="px-3 py-1 rounded-lg <?= $active_class ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php
            // Next Button Logic
            $next_page = $page + 1;
            $next_class = $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100';
            ?>
            <a href="users.php?page=<?= $next_page ?>" 
               class="px-3 py-1 border rounded-lg <?= $next_class ?>">
                Next
            </a>
        </div>
    </div>
</div>

<?php
// 4. Capture the content
$page_content = ob_get_clean();

// 5. Include the master layout file
include 'includes/layout.php'; 
?>