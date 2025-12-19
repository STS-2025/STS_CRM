<?php 
// create_company.php

// 1. Start capturing the output buffer
ob_start(); 
session_start();

// 2. Set the specific page title
$page_title = "Add New Company";

// 3. Include necessary files and fetch users for dropdown
// include 'includes/auth_check.php';
include 'api/db.php';

// Fetch all users to populate the owner dropdown
$users_result = $conn->query("SELECT id, name FROM users ORDER BY name ASC");
$users = [];
while ($user = $users_result->fetch_assoc()) {
    $users[] = $user;
}
$conn->close();

// Default owner to the logged-in user's ID
// $current_user_id = $_SESSION['user_id'] ?? 1; // Assuming default ID 1 if not logged in
$default_owner_id = $_SESSION['user_id'] ?? 1;
?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Add New Company</h1>
        <p class="text-gray-500 mt-1">Enter the organizational details to create a new company record.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form action="api/create_company_process.php" method="POST" class="space-y-6">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="name" class="text-sm font-medium text-gray-700 block">Company Name</label>
            <input type="text" id="name" name="name" required
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
       
        <div>
        <label for="client_ceo_name" class="text-sm font-medium text-gray-700 block">Client Company Owner/CEO Name</label>
        <input type="text" id="client_ceo_name" name="client_ceo_name" 
               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="CEO's Full Name">
    </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="phone" class="text-sm font-medium text-gray-700 block">Phone Number (Primary)</label>
            <input type="text" id="phone" name="phone"
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
         <div>
            <label for="phone_secondary" class="text-sm font-medium text-gray-700 block">Phone Number (Secondary/Alternate)</label>
            <input type="text" id="phone_secondary" name="phone_secondary"
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
        
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
       <div>
            <label for="email" class="text-sm font-medium text-gray-700 block">Company Email ID</label>
            <input type="email" id="email" name="email"
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="contact@companyname.com">
        </div>
        <div>
            <label for="industry" class="text-sm font-medium text-gray-700 block">Industry Type</label>
            <input type="text" id="industry" name="industry"
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="e.g., Technology, Finance">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="incharge" class="text-sm font-medium text-gray-700 block">Company Incharge (Client Contact Name)</label>
            <input type="text" id="incharge" name="incharge"
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Contact Person's Name">
        </div>
        <div>
            <label for="team_member_id" class="text-sm font-medium text-gray-700 block">Our Team Member/Manager (Internal)</label>
            <select id="team_member_id" name="team_member_id" required
                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">-- Select Team Member --</option>
                <?php 
                foreach($users as $user) {
                    $selected = ($user['id'] == $default_owner_id) ? 'selected' : '';
                    echo "<option value=\"{$user['id']}\" $selected>" . htmlspecialchars($user['name']) . "</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="address" class="text-sm font-medium text-gray-700 block">Company Address</label>
            <textarea id="address" name="address" rows="3"
                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
        </div>
        <div>
    <label for="starting_deal_date" class="text-sm font-medium text-gray-700 block">Starting Deal Date</label>
    <input type="date" id="starting_deal_date" name="starting_deal_date"
           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
</div>
    </div>
    
    <div class="pt-5 border-t border-gray-200 mt-8">
        <div class="flex justify-end">
            <a href="companies.php" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition">
                Cancel
            </a>
            <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i> Save Company
            </button>
        </div>
    </div>
</form>
</div>

<?php
// 5. Capture the content
$page_content = ob_get_clean();

// 6. Include the master layout file
include 'includes/layout.php'; 
?>