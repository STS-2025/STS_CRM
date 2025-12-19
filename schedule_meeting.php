<?php
// schedule_meeting.php

// 1. Start output buffering and session
ob_start(); 
session_start();

// 2. Set the specific page title
$page_title = "Schedule New Meeting";

// 3. Include necessary files and database connection
// include 'includes/auth_check.php'; 
include 'api/db.php'; 

// --- 4. DATA FETCHING ---

// Fetch all contacts
$contacts_result = $conn->query("SELECT id, name, company_name FROM contacts ORDER BY name ASC");
$contacts = [];
if ($contacts_result) {
    while ($contact = $contacts_result->fetch_assoc()) {
        $contacts[] = $contact;
    }
}

// Fetch all users (for assigning the meeting organizer/owner)
$users_result = $conn->query("SELECT id, name FROM users ORDER BY name ASC");
$users = [];
if ($users_result) {
    while ($user = $users_result->fetch_assoc()) {
        $users[] = $user;
    }
}

$conn->close();

// Define meeting types
$meeting_types = ['Phone Call', 'Video Call', 'In-person', 'Email'];
$current_user_id = $_SESSION['user_id'] ?? null;
?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Schedule Meeting</h1>
        <p class="text-gray-500 mt-1">Book a new interaction with a contact or lead.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form action="api/schedule_meeting_process.php" method="POST" class="space-y-6">

        <div>
            <label for="subject" class="text-sm font-medium text-gray-700 block">Meeting Subject</label>
            <input type="text" id="subject" name="subject" required
                   placeholder="e.g., Q3 Review Discussion, Follow-up Call"
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="contact_id" class="text-sm font-medium text-gray-700 block">Associated Contact</label>
                <select id="contact_id" name="contact_id" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">-- Select Contact --</option>
                    <?php 
                    foreach($contacts as $c) {
                        $company_info = $c['company_name'] ? " ({$c['company_name']})" : "";
                        echo "<option value=\"{$c['id']}\">" . htmlspecialchars($c['name']) . $company_info . "</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div>
                <label for="user_id" class="text-sm font-medium text-gray-700 block">Meeting Organizer</label>
                <select id="user_id" name="user_id" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">-- Select Organizer --</option>
                    <?php 
                    foreach($users as $u) {
                        $selected = ($u['id'] == $current_user_id) ? 'selected' : '';
                        echo "<option value=\"{$u['id']}\" $selected>" . htmlspecialchars($u['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="date" class="text-sm font-medium text-gray-700 block">Date</label>
                <input type="date" id="date" name="date" required
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="time" class="text-sm font-medium text-gray-700 block">Time</label>
                <input type="time" id="time" name="time" required
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="type" class="text-sm font-medium text-gray-700 block">Meeting Type</label>
                <select id="type" name="type" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">-- Select Type --</option>
                    <?php 
                    foreach($meeting_types as $type_option) {
                        echo "<option value=\"$type_option\">$type_option</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="pt-5 border-t border-gray-200 mt-8">
            <div class="flex justify-end">
                <a href="meetings.php" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 transition">
                    Cancel
                </a>
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <i data-lucide="calendar-plus" class="w-4 h-4 inline mr-2"></i> Save Meeting
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