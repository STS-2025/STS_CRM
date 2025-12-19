<?php 
// edit_company.php

// 1. Start capturing the output buffer and session
ob_start(); 
session_start();

// 2. Set the specific page title
$page_title = "Edit Company";

// 3. Include necessary files
// include 'includes/auth_check.php';
include 'api/db.php';

// Get company ID from URL
$company_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($company_id === 0) {
    $_SESSION['message'] = 'Error: Invalid company ID.';
    header('Location: companies.php');
    exit();
}

// 4. Fetch existing company data
$stmt_company = $conn->prepare("SELECT name, industry, phone, owner_id FROM companies WHERE id = ?");
$stmt_company->bind_param("i", $company_id);
$stmt_company->execute();
$result_company = $stmt_company->get_result();
$company = $result_company->fetch_assoc();
$stmt_company->close();

if (!$company) {
    $_SESSION['message'] = 'Error: Company record not found.';
    header('Location: companies.php');
    exit();
}

// 5. Fetch all users for the owner dropdown
$users_result = $conn->query("SELECT id, name FROM users ORDER BY name ASC");
$users = [];
while ($user = $users_result->fetch_assoc()) {
    $users[] = $user;
}
$conn->close();
?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Edit Company: <?php echo htmlspecialchars($company['name']); ?></h1>
        <p class="text-gray-500 mt-1">Update the details for this organizational account.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form action="api/update_company_process.php" method="POST" class="space-y-6">

        <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="text-sm font-medium text-gray-700 block">Company Name</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo htmlspecialchars($company['name']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="phone" class="text-sm font-medium text-gray-700 block">Phone Number</label>
                <input type="text" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($company['phone']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="industry" class="text-sm font-medium text-gray-700 block">Industry</label>
                <input type="text" id="industry" name="industry" 
                       value="<?php echo htmlspecialchars($company['industry']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="e.g., Technology, Finance">
            </div>
            <div>
                <label for="owner_id" class="text-sm font-medium text-gray-700 block">Company Owner</label>
                <select id="owner_id" name="owner_id" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php 
                    foreach($users as $user) {
                        // Check if this user is the current owner
                        $selected = ($user['id'] == $company['owner_id']) ? 'selected' : '';
                        echo "<option value=\"{$user['id']}\" $selected>" . htmlspecialchars($user['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        
        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-end">
                <a href="companies.php" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition">
                    Cancel
                </a>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <i data-lucide="save" class="w-4 h-4 inline mr-2"></i> Update Company
                </button>
            </div>
        </div>
    </form>
</div>

<?php
// 6. Capture the content
$page_content = ob_get_clean();

// 7. Include the master layout file
include 'includes/layout.php'; 
?>