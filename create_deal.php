<?php 
// create_deal.php

// 1. Start output buffering and session
ob_start(); 
session_start();

// 2. Set the specific page title
$page_title = "Create New Deal";
$current_date = date('Y-m-d');
$two_days_later = date('Y-m-d', strtotime('+2 days'));

// 3. Include necessary files and database connection
// include 'includes/auth_check.php'; // Uncomment when ready to enforce login
include 'api/db.php'; 

// Fetch all companies for the dropdown
$companies_result = $conn->query("SELECT id, name FROM companies ORDER BY name ASC");
$companies = [];
if ($companies_result) {
    while ($company = $companies_result->fetch_assoc()) {
        $companies[] = $company;
    }
}

// Fetch all users (potential deal owners) for the dropdown
$users_result = $conn->query("SELECT id, name FROM users ORDER BY name ASC");
$users = [];
if ($users_result) {
    while ($user = $users_result->fetch_assoc()) {
        $users[] = $user;
    }
}
$conn->close();

?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Create New Deal</h1>
        <p class="text-gray-500 mt-1">Enter the details for a new sales opportunity.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form action="api/create_deal_process.php" method="POST" class="space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="deal_name" class="text-sm font-medium text-gray-700 block">Deal Name</label>
                <input type="text" id="deal_name" name="deal_name" required
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="e.g., Q1 Service Contract">
            </div>
            <div>
                <label for="amount" class="text-sm font-medium text-gray-700 block">Deal Amount (₹)</label>
                <input type="number" id="amount" name="amount" step="0.01" required
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="15000.00">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="company_id" class="text-sm font-medium text-gray-700 block">Associated Company</label>
                <select id="company_id" name="company_id" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">-- Select Company --</option>
                    <?php 
                    foreach($companies as $c) {
                        echo "<option value=\"{$c['id']}\">" . htmlspecialchars($c['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="owner_id" class="text-sm font-medium text-gray-700 block">Deal Incharge</label>
                <select id="owner_id" name="owner_id" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">-- Select Incharge --</option>
                    <?php 
                    foreach($users as $u) {
                        // Optional: Pre-select the logged-in user if you implement auth_check fully
                        $selected = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $u['id']) ? 'selected' : '';
                        echo "<option value=\"{$u['id']}\" $selected>" . htmlspecialchars($u['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="opening_date" class="text-sm font-medium text-gray-700 block">Opening Date</label>
                <input type="date" id="opening_date" name="opening_date" value="<?php echo $current_date; ?>" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
           
            <div>
                <label for="close_date" class="text-sm font-medium text-gray-700 block">Expected Close Date</label>
                <input type="date" id="close_date" name="close_date" value="<?php echo $two_days_later; ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="stage" class="text-sm font-medium text-gray-700 block">Pipeline Stage</label>
                <select id="stage" name="stage" required
                         class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="New">New</option>
                    <option value="Qualification">Qualification</option>
                    <option value="Proposal Sent">Proposal Sent</option>
                    <option value="Negotiation">Negotiation</option>
                    <option value="Closed Won">Closed Won</option>
                    <option value="Closed Lost">Closed Lost</option>
                </select>
            </div>
            
        </div>

        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-end">
                <a href="deals.php" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition">
                    Cancel
                </a>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <i data-lucide="send" class="w-4 h-4 inline mr-2"></i> Create Deal
                </button>
            </div>
        </div>
    </form>
</div>

<?php
// 4. Capture the content
$page_content = ob_get_clean();

// 5. Include the master layout file
include 'includes/layout.php'; 
?>