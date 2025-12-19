<?php
// edit_meeting.php

// 1. Start output buffering and session
ob_start(); 
session_start();

// 2. Set the specific page title
$page_title = "Edit Meeting";

// 3. Include necessary files and database connection
// include 'includes/auth_check.php'; 
include 'api/db.php'; 

// Check if meeting ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Error: Meeting ID is missing.";
    header('Location: meetings.php');
    exit();
}

$meeting_id = (int)$_GET['id'];
$meeting_data = null;

// --- 4. DATA FETCHING ---

// Fetch specific meeting data
$stmt = $conn->prepare("
    SELECT id, subject, date_time, type, status, contact_id, user_id 
    FROM meetings 
    WHERE id = ?
");
$stmt->bind_param("i", $meeting_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $meeting_data = $result->fetch_assoc();
    
    // Split DATETIME into DATE and TIME for form fields
    $datetime = new DateTime($meeting_data['date_time']);
    $meeting_data['date'] = $datetime->format('Y-m-d');
    $meeting_data['time'] = $datetime->format('H:i'); // 24-hour format for input type="time"

} else {
    $_SESSION['message'] = "Error: Meeting not found.";
    header('Location: meetings.php');
    exit();
}
$stmt->close();


// Fetch all contacts (for dropdown)
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

// Define meeting options
$meeting_types = ['Phone Call', 'Video Call', 'In-person', 'Email'];
$meeting_statuses = ['Scheduled', 'Completed', 'Canceled'];
?>

<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Edit Meeting #<?= $meeting_id ?></h1>
        <p class="text-gray-500 mt-1">Modify the details or update the status of this interaction.</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl">
    <form action="api/update_meeting_process.php" method="POST" class="space-y-6">
        <input type="hidden" name="id" value="<?= $meeting_data['id'] ?>">

        <div>
            <label for="subject" class="text-sm font-medium text-gray-700 block">Meeting Subject</label>
            <input type="text" id="subject" name="subject" required
                   value="<?= htmlspecialchars($meeting_data['subject']) ?>"
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
                        $selected = ($c['id'] == $meeting_data['contact_id']) ? 'selected' : '';
                        echo "<option value=\"{$c['id']}\" $selected>" . htmlspecialchars($c['name']) . $company_info . "</option>";
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
                        $selected = ($u['id'] == $meeting_data['user_id']) ? 'selected' : '';
                        echo "<option value=\"{$u['id']}\" $selected>" . htmlspecialchars($u['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label for="date" class="text-sm font-medium text-gray-700 block">Date</label>
                <input type="date" id="date" name="date" required
                       value="<?= $meeting_data['date'] ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="time" class="text-sm font-medium text-gray-700 block">Time</label>
                <input type="time" id="time" name="time" required
                       value="<?= $meeting_data['time'] ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="type" class="text-sm font-medium text-gray-700 block">Meeting Type</label>
                <select id="type" name="type" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">-- Select Type --</option>
                    <?php 
                    foreach($meeting_types as $type_option) {
                        $selected = ($type_option === $meeting_data['type']) ? 'selected' : '';
                        echo "<option value=\"$type_option\" $selected>$type_option</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div>
                <label for="status" class="text-sm font-medium text-gray-700 block">Status</label>
                <select id="status" name="status" required
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php 
                    foreach($meeting_statuses as $status_option) {
                        $selected = ($status_option === $meeting_data['status']) ? 'selected' : '';
                        echo "<option value=\"$status_option\" $selected>$status_option</option>";
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
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    <i data-lucide="save" class="w-4 h-4 inline mr-2"></i> Save Changes
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